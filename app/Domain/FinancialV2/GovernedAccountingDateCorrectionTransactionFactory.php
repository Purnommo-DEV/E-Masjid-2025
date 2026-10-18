<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionApproval;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\ReversalConfigurationRequest;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Creates the approved transaction pair but never posts it. */
final class GovernedAccountingDateCorrectionTransactionFactory
{
    public function __construct(
        private readonly FinancialV2TransactionRunner $transactions,
        private readonly FinancialTransactionLifecycleService $lifecycle,
        private readonly EvidenceService $evidence,
        private readonly AccountingDateCorrectionGovernancePolicy $governance,
        private readonly AuditTrailService $audit,
        private readonly AccountingDateCorrectionPolicyException $policyExceptions,
        private readonly ReplacementRealizationWorkflowService $replacementRealizations,
        private readonly AccountingDateCorrectionApprovedPlanResolver $approvedPlans,
    ) {}

    /** @return array{reversal:FinancialTransaction,replacement:FinancialTransaction,snapshot:array<string,mixed>,fingerprint:string} */
    public function createApprovedPair(string $entityId, string $requestId, string $idempotencyKey, int $actorUserId): array
    {
        return $this->transactions->run(function () use ($entityId, $requestId, $idempotencyKey, $actorUserId): array {
            $request = AccountingDateCorrectionRequest::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->findOrFail($requestId);
            if (! in_array($request->status, ['governance_approved', 'execution_ready'], true)) throw new FinancialDomainException('E-CORRECTION-FACTORY-REQUEST', 'Correction Request harus governance_approved atau execution_ready pada retry atomik yang belum menghasilkan jurnal.');
            $approval = AccountingDateCorrectionApproval::query()->where('correction_request_id', $request->id)->where('decision', 'approved')->lockForUpdate()->firstOrFail();
            $original = FinancialTransaction::query()->with(['type', 'splits'])->where('accounting_entity_id', $entityId)->lockForUpdate()->findOrFail($request->original_transaction_id);
            $journal = Journal::query()->where('accounting_entity_id', $entityId)->where('transaction_id', $original->id)->where('journal_status', 'posted')->lockForUpdate()->sole();
            if ($original->status !== 'posted' || $journal->id !== $request->original_journal_id) throw new FinancialDomainException('E-CORRECTION-FACTORY-SOURCE', 'Original transaction/journal tidak lagi cocok dengan approval.');
            $this->governance->authorizeSuperAdminOverride((int)$request->requested_by_user_id, (int)$approval->decided_by_user_id, $actorUserId, $this->isSuperAdmin($actorUserId), $original->id, null, AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);

            $configuration = ReversalConfigurationRequest::query()->where('accounting_entity_id', $entityId)->where('status', 'activated')->lockForUpdate()->first();
            if (! $configuration) throw new FinancialDomainException('E-CORRECTION-FACTORY-REV', 'Reversal Configuration aktif tidak tersedia.');
            $reversalType = TransactionType::query()->where('accounting_entity_id', $entityId)->where('code', 'REV')->where('status', 'active')->lockForUpdate()->first();
            if (! $reversalType) throw new FinancialDomainException('E-CORRECTION-FACTORY-REV', 'Transaction Type REV aktif tidak tersedia.');
            $evidence = $this->validatedEvidence($entityId, $original->id, $request->evidence_attachment_id);
            $attachment = $evidence['attachment'];
            $approvedPlan=$this->approvedPlans->resolve($request);
            $policyException=$approvedPlan['snapshot']['accounting_date_policy_exception']??null;
            if(!is_array($policyException))throw new FinancialDomainException('E-CORRECTION-FACTORY-POLICY','Approval tidak memuat Accounting Date Correction policy exception.');
            $this->policyExceptions->assertMatches($policyException,$original,$journal,$request->target_accounting_date->toDateString());

            $snapshot = $this->snapshot($request, $approvedPlan, $original, $journal, $configuration, $attachment);
            $fingerprint = $this->hash($snapshot);
            $prefix = 'ADC:'.$request->id.':';
            $existing = FinancialTransaction::query()->where('accounting_entity_id', $entityId)->whereIn('source_reference', [$prefix.'REV', $prefix.'REPLACEMENT'])->lockForUpdate()->get()->keyBy('source_reference');
            if ($existing->isNotEmpty()) {
                if ($existing->count() !== 2) throw new FinancialDomainException('E-CORRECTION-FACTORY-PARTIAL', 'Factory pair ditemukan parsial; recovery manual diperlukan.');
                $this->ensureReversalGovernanceEvidence($entityId, $existing[$prefix.'REV'], $configuration, $actorUserId);
                return ['reversal'=>$existing[$prefix.'REV']->load('splits'),'replacement'=>$existing[$prefix.'REPLACEMENT']->load('splits'),'snapshot'=>$snapshot,'fingerprint'=>$fingerprint,'policy_exception'=>$policyException];
            }

            $splits = $original->splits->map(fn ($split): array => Arr::only($split->toArray(), ['account_id','split_amount','fund_id','financial_account_id','program_id','cost_center_id','counterparty_id','category_id','purpose_note','source_reference']))->all();
            $replacementSplits=array_map(function(array$split)use($policyException):array{$split['counterparty_id']=$policyException['counterparty']['replacement_id']??$split['counterparty_id'];return$split;},$splits);
            $base = ['accounting_entity_id'=>$entityId,'business_date'=>$original->business_date->toDateString(),'gross_amount'=>$original->gross_amount,'primary_financial_account_id'=>$original->primary_financial_account_id,'counterparty_id'=>$original->counterparty_id,'currency_code'=>$original->currency_code,'policy_version_ref'=>$original->policy_version_ref,'correlation_id'=>$original->correlation_id];
            $reversal = $this->lifecycle->createAccountingDateCorrectionDraft(TransactionTypeCode::Reversal, $base + ['transaction_type_id'=>$reversalType->id,'accounting_date'=>$original->accounting_date->toDateString(),'source_reference'=>$prefix.'REV','description'=>'Reversal koreksi tanggal akuntansi '.$original->id,'reason_code_id'=>$configuration->reason_code_id,'related_transaction_id'=>$original->id,'idempotency_key'=>$idempotencyKey.':REV'], $splits, $actorUserId);

            $replacementInput = array_merge($base,['counterparty_id'=>$policyException['counterparty']['replacement_id']??$original->counterparty_id,'transaction_type_id'=>$original->transaction_type_id,'accounting_date'=>$request->target_accounting_date->toDateString(),'source_reference'=>$prefix.'REPLACEMENT','description'=>$original->description,'category_id'=>$original->category_id,'idempotency_key'=>$idempotencyKey.':REPLACEMENT']);
            $originalRealization = FundRealization::query()->where('transaction_id', $original->id)->lockForUpdate()->first();
            $replacement = $originalRealization
                ? $this->lifecycle->createRealization($replacementInput, $replacementSplits, $originalRealization->budget_allocation_version_id, $actorUserId)
                : $this->lifecycle->createAccountingDateCorrectionDraft(TransactionTypeCode::from($original->type->code), $replacementInput, $replacementSplits, $actorUserId);

            foreach ([$reversal, $replacement] as $transaction) {
                $this->evidence->attachToTransaction($entityId, $transaction->id, $attachment->original_filename, $attachment->media_type, (int)$attachment->byte_size, $attachment->content_hash, $attachment->storage_reference, $evidence['evidence_type'], $actorUserId, ['source_media_type'=>$attachment->source_media_type,'source_byte_size'=>(int)$attachment->source_byte_size,'image_width'=>$attachment->image_width,'image_height'=>$attachment->image_height]);
                if ($transaction->id === $replacement->id) {
                    $this->lifecycle->submitAccountingDateCorrectionReplacement($transaction->id, $policyException, $actorUserId);
                } else {
                    $this->lifecycle->submit($transaction->id, $actorUserId);
                }
                $this->lifecycle->verify($transaction->id, $actorUserId);
                $this->lifecycle->recordApprovalDecision($transaction->id, 1, 'approved', $actorUserId, 'SuperAdmin Override scoped: '.$request->id);
                if ($transaction->id === $replacement->id) {
                    $this->lifecycle->approveAccountingDateCorrectionReplacement($transaction->id, $policyException, $actorUserId);
                } else {
                    $this->lifecycle->approve($transaction->id, $actorUserId);
                }
            }
            $this->ensureReversalGovernanceEvidence($entityId, $reversal, $configuration, $actorUserId);
            if ($originalRealization) {
                $replacementWorkflow = $this->replacementRealizations->createDraft($entityId, $request->id, [
                    'replacement_transaction_id' => $replacement->id,
                    'evidence_attachment_id' => $attachment->id,
                    'separation_policy' => 'superadmin_override',
                    'idempotency_key' => \Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL, $idempotencyKey.'|replacement-realization')->toString(),
                ], $actorUserId);
                if ($replacementWorkflow->status === 'draft') $replacementWorkflow = $this->replacementRealizations->submit($entityId, $replacementWorkflow->id, $actorUserId);
                if ($replacementWorkflow->status === 'pending_review') $this->replacementRealizations->approve($entityId, $replacementWorkflow->id, $actorUserId, 'SuperAdmin Override scoped: '.$request->id);
            }
            $this->audit->record($entityId, 'accounting_date_correction_pair_factory_completed', 'accounting_date_correction_request', $request->id, $request->id, $actorUserId, null, ['reversal_transaction_id'=>$reversal->id,'replacement_transaction_id'=>$replacement->id,'factory_fingerprint'=>$fingerprint,'override'=>'superadmin_override']);

            return ['reversal'=>$reversal->fresh('splits'),'replacement'=>$replacement->fresh('splits'),'snapshot'=>$snapshot,'fingerprint'=>$fingerprint,'policy_exception'=>$policyException];
        });
    }

    /** @return array{attachment:Attachment,evidence_type:string} */
    private function validatedEvidence(string $entityId, string $transactionId, string $attachmentId): array
    {
        $link = AttachmentLink::query()->where('accounting_entity_id',$entityId)->where('target_type','transaction')->where('target_id',$transactionId)->where('attachment_id',$attachmentId)->where('status','active')->first();
        $attachment = $link ? Attachment::query()->where('accounting_entity_id',$entityId)->where('status','active')->find($attachmentId) : null;
        if (! $attachment || ! Storage::disk('local')->exists($attachment->storage_reference) || ! hash_equals($attachment->content_hash, hash_file('sha256', Storage::disk('local')->path($attachment->storage_reference)))) throw new FinancialDomainException('E-CORRECTION-FACTORY-EVIDENCE','Evidence aktif tidak tersedia atau hash file tidak cocok.');
        return ['attachment'=>$attachment,'evidence_type'=>$link->evidence_type];
    }

    private function snapshot($request,array$approvedPlan,$original,$journal,$configuration,$attachment):array
    {
        return ['request_id'=>$request->id,'approval_fingerprint'=>$approvedPlan['fingerprint'],'approval_source'=>$approvedPlan['source'],'approval_actor_user_id'=>$approvedPlan['actor_user_id'],'entity_id'=>$request->accounting_entity_id,'original_transaction_id'=>$original->id,'original_journal_id'=>$journal->id,'original_posting_sequence'=>$journal->posting_sequence,'original_accounting_date'=>$original->accounting_date->toDateString(),'target_accounting_date'=>$request->target_accounting_date->toDateString(),'gross_amount'=>DecimalAmount::normalize($original->gross_amount),'transaction_type'=>$original->type->code,'dimensions'=>$original->splits->map(fn($s)=>[$s->account_id,$s->fund_id,$s->program_id,$s->financial_account_id,DecimalAmount::normalize($s->split_amount)])->sort()->values()->all(),'reversal_configuration_request_id'=>$configuration->id,'reason_code_id'=>$configuration->reason_code_id,'evidence'=>['id'=>$attachment->id,'hash'=>$attachment->content_hash,'status'=>$attachment->status]];
    }
    private function ensureReversalGovernanceEvidence(string $entityId, FinancialTransaction $reversal, ReversalConfigurationRequest $configuration, int $actorUserId): void
    {
        $attachment = Attachment::query()
            ->where('accounting_entity_id', $entityId)
            ->where('status', 'active')
            ->find($configuration->governance_attachment_id);
        if (! $attachment || ! Storage::disk('local')->exists($attachment->storage_reference)
            || ! hash_equals((string) $attachment->content_hash, hash_file('sha256', Storage::disk('local')->path($attachment->storage_reference)))) {
            throw new FinancialDomainException('E-CORRECTION-FACTORY-GOVERNANCE-EVIDENCE', 'Dokumen governance REV aktif tidak tersedia atau hash file tidak cocok.');
        }
        $this->evidence->attachToTransaction(
            $entityId,
            $reversal->id,
            $attachment->original_filename,
            $attachment->media_type,
            (int) $attachment->byte_size,
            $attachment->content_hash,
            $attachment->storage_reference,
            'policy',
            $actorUserId,
            [
                'source_media_type' => $attachment->source_media_type,
                'source_byte_size' => (int) $attachment->source_byte_size,
                'image_width' => $attachment->image_width,
                'image_height' => $attachment->image_height,
            ],
        );
    }
    private function hash(array $value):string{return hash('sha256',json_encode(Arr::sortRecursive($value),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));}
    private function isSuperAdmin(int $actor):bool{$user=User::query()->find($actor);return$user!==null&&$user->hasRole('SuperAdmin');}
}
