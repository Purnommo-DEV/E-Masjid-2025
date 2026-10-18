<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionApproval;
use App\Models\FinancialV2\AccountingDateCorrectionBatch;
use App\Models\FinancialV2\AccountingDateCorrectionBatchItemExecutionPair;
use App\Models\FinancialV2\AccountingDateCorrectionExecutionPair;
use App\Models\FinancialV2\AccountingDateCorrectionLineage;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\ReplacementRealizationRequest;
use App\Models\FinancialV2\ReversalConfigurationRequest;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Atomic execution boundary for Accounting Date Correction.
 *
 * Governance approval has been confirmed, but the selected actor matrix for
 * `three_distinct` is not represented as executable policy and no canonical
 * factory stages the REV/replacement transaction pair. This
 * orchestrator therefore implements the complete locked revalidation gate but
 * intentionally exposes no alternative fact writer. PostingEngine remains the
 * sole eventual writer and execute*() stays fail-closed until those contracts
 * exist in the approved snapshot.
 */
final class AccountingDateCorrectionExecutionOrchestrator
{
    public function __construct(
        private readonly FinancialV2TransactionRunner $transactions,
        private readonly AccountingDateCorrectionWorkflowService $workflow,
        private readonly AccountingDateCorrectionBatchWorkflowService $batches,
        private readonly AccountingDateCorrectionGovernancePolicy $governance,
        private readonly GovernedAccountingDateCorrectionTransactionFactory $factory,
        private readonly AccountingDateCorrectionExecutionPairService $pairs,
        private readonly AccountingDateCorrectionBatchItemWorkflow $batchItems,
        private readonly ReplacementRealizationWorkflowService $replacementRealizations,
        private readonly PostingEngine $posting,
        private readonly AuditTrailService $audit,
        private readonly AccountingDateCorrectionApprovedPlanResolver $approvedPlans,
    ) {}

    /** @return array{execution_ready:false,mode:string,blockers:array<int,array<string,mixed>>,fingerprint:?string} */
    public function assessIndividual(string $entityId,string $requestId,int $actorUserId):array
    {
        return $this->transactions->run(function()use($entityId,$requestId,$actorUserId):array{
            $this->assertActor($actorUserId);
            $probe=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->findOrFail($requestId);
            $this->lockEntity($entityId);
            $transaction=FinancialTransaction::query()->with(['type','splits','category'])
                ->where('accounting_entity_id',$entityId)
                ->lockForUpdate()
                ->findOrFail($probe->original_transaction_id);
            $request=AccountingDateCorrectionRequest::query()
                ->where('accounting_entity_id',$entityId)
                ->lockForUpdate()
                ->findOrFail($requestId);
            $lineage=AccountingDateCorrectionLineage::query()
                ->where('correction_request_id',$request->id)
                ->lockForUpdate()
                ->firstOrFail();
            $approval=AccountingDateCorrectionApproval::query()
                ->where('correction_request_id',$request->id)
                ->where('decision','approved')
                ->lockForUpdate()
                ->first();

            $blockers=[];
            if($request->status!=='governance_approved')$blockers[]=$this->blocker('REQUEST_NOT_GOVERNANCE_APPROVED','Request belum governance_approved.');
            if(!$approval)$blockers[]=$this->blocker('APPROVAL_MISSING','Approval append-only yang sah tidak ditemukan.');
            $governanceOverride=null;
            if($approval){
                try{$this->governance->assertThreeDistinct((int)$request->requested_by_user_id,(int)$approval->decided_by_user_id,$actorUserId);}
                catch(FinancialDomainException){
                    try{$governanceOverride=$this->governance->authorizeSuperAdminOverride((int)$request->requested_by_user_id,(int)$approval->decided_by_user_id,$actorUserId,$this->isSuperAdmin($actorUserId),$transaction->id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);}
                    catch(FinancialDomainException $exception){$blockers[]=$this->blocker($exception->failureCode,$exception->getMessage(),$exception->details);}
                }
            }
            if($transaction->status!=='posted')$blockers[]=$this->blocker('ORIGINAL_NOT_POSTED','Transaksi original tidak lagi posted.');
            if($lineage->original_transaction_id!==$transaction->id||$lineage->original_journal_id!==$request->original_journal_id)$blockers[]=$this->blocker('ORIGINAL_LINEAGE_DRIFT','Lineage original tidak cocok dengan request.');

            $current=null;$currentFingerprint=null;
            try{
                $current=$this->workflow->preview($transaction,[
                    'target_accounting_date'=>$request->target_accounting_date->toDateString(),
                    'attachment_id'=>$request->evidence_attachment_id,
                ],$actorUserId,$request->id);
                $currentFingerprint=$this->hash($current);
                if(($current['blocking_reasons']??[])!==[])$blockers[]=$this->blocker('CANONICAL_REVALIDATION_BLOCKED','Planner/workflow canonical masih menemukan blocker.',['reasons'=>$current['blocking_reasons']]);
                if($approval){$approvedPlan=$this->approvedPlans->resolve($request);if(!hash_equals((string)$approvedPlan['fingerprint'],$currentFingerprint))$blockers[]=$this->blocker('APPROVED_FINGERPRINT_DRIFT','Snapshot canonical terkunci tidak lagi cocok dengan snapshot approval/reauthorization append-only.');}
            }catch(\Throwable $exception){
                $blockers[]=$this->blocker('CANONICAL_REVALIDATION_FAILED','Revalidasi canonical gagal secara fail-closed.',['exception'=>get_class($exception)]);
            }

            $replacement=ReplacementRealizationRequest::query()
                ->where('correction_request_id',$request->id)
                ->lockForUpdate()
                ->first();
            if($lineage->original_realization_id&&(!$replacement||$replacement->status!=='approved'))$blockers[]=$this->blocker('REPLACEMENT_REALIZATION_NOT_APPROVED','Replacement Realization belum approved dan belum siap dimaterialisasi atomik setelah posting.');
            if(!$this->activeRevConfiguration($entityId))$blockers[]=$this->blocker('REV_CONFIGURATION_INACTIVE','Konfigurasi REV aktif yang sah tidak tersedia.');
            $pair=Schema::hasTable('financial_v2_accounting_date_correction_execution_pairs')?AccountingDateCorrectionExecutionPair::query()->where('correction_request_id',$request->id)->lockForUpdate()->first():null;
            if(!$pair||$pair->status!=='execution_ready')$blockers[]=$this->blocker('EXECUTION_PAIR_NOT_READY','Execution pair canonical belum berstatus execution_ready.');
            elseif(!isset($approvedPlan)||!hash_equals((string)$pair->approved_plan_fingerprint,(string)$approvedPlan['fingerprint'])||!hash_equals((string)$pair->validation_fingerprint,(string)$currentFingerprint))$blockers[]=$this->blocker('EXECUTION_PAIR_DRIFT','Fingerprint execution pair tidak cocok dengan approval/reauthorization dan revalidasi canonical.');
            return['execution_ready'=>false,'mode'=>'individual','blockers'=>$this->unique($blockers),'fingerprint'=>$currentFingerprint,'governance_override'=>$governanceOverride];
        });
    }

    /** @return array{execution_ready:false,mode:string,blockers:array<int,array<string,mixed>>,fingerprint:string} */
    public function assessBatch(string $entityId,string $batchId,int $actorUserId):array
    {
        return $this->transactions->run(function()use($entityId,$batchId,$actorUserId):array{
            $this->assertActor($actorUserId);
            $gate=$this->batches->revalidateForExecution($entityId,$batchId);
            $batch=AccountingDateCorrectionBatch::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($batchId);
            $blockers=array_map(fn(string$code)=>$this->blocker($code,'Batch execution gate masih diblokir.'),$gate['blockers']);
            $governanceOverride=null;
            try{$this->governance->assertThreeDistinct((int)$batch->created_by_user_id,(int)$batch->approved_by_user_id,$actorUserId);}
            catch(FinancialDomainException){
                try{$governanceOverride=$this->governance->authorizeSuperAdminOverride((int)$batch->created_by_user_id,(int)$batch->approved_by_user_id,$actorUserId,$this->isSuperAdmin($actorUserId),null,$batch->correlation_id,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);}
                catch(FinancialDomainException $exception){$blockers[]=$this->blocker($exception->failureCode,$exception->getMessage(),$exception->details);}
            }
            if(!Schema::hasTable('financial_v2_acct_date_corr_batch_item_pairs'))$blockers[]=$this->blocker('BATCH_EXECUTION_PAIR_SCHEMA_PENDING','Migration execution pair belum diterapkan pada database ini.');
            else{
                $items=$batch->items()->count();$links=AccountingDateCorrectionBatchItemExecutionPair::query()->with('executionPair')->where('batch_id',$batch->id)->lockForUpdate()->get();
                if($links->count()!==$items||!$links->every(fn($link)=>$link->executionPair&&$link->executionPair->status==='execution_ready'))$blockers[]=$this->blocker('BATCH_EXECUTION_LINEAGE_INCOMPLETE','Tidak seluruh item batch mempunyai execution pair immutable yang execution_ready.');
            }
            if(!$this->activeRevConfiguration($entityId))$blockers[]=$this->blocker('REV_CONFIGURATION_INACTIVE','Konfigurasi REV aktif yang sah tidak tersedia.');

            return['execution_ready'=>false,'mode'=>'bank_batch','blockers'=>$this->unique($blockers),'fingerprint'=>$batch->snapshot_fingerprint,'governance_override'=>$governanceOverride];
        });
    }

    /** @return array<string,mixed> */
    public function stageIndividual(string $entityId,string $requestId,string $idempotencyKey,int $actorUserId):array
    {
        $this->assertIdempotency($idempotencyKey);
        $created=$this->factory->createApprovedPair($entityId,$requestId,$idempotencyKey,$actorUserId);
        $pair=$this->pairs->prepare($entityId,$requestId,$idempotencyKey,$actorUserId);
        if($pair->status==='prepared')$pair=$this->pairs->validate($entityId,$pair->id,$actorUserId);
        if($pair->status==='validated')$pair=$this->pairs->stage($entityId,$pair->id,$actorUserId);
        if($pair->status==='staged')$pair=$this->pairs->bindTransactionsAndMarkReady($entityId,$pair->id,$created['reversal']->id,$created['replacement']->id,$actorUserId);
        if($pair->status!=='execution_ready')throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-NOT-EXECUTION-READY','Execution pair tidak mencapai execution_ready.',['pair_status'=>$pair->status,'blockers'=>$pair->blockers]);

        return ['request_id'=>$requestId,'execution_pair_id'=>$pair->id,'pair_status'=>$pair->status,'reversal_transaction_id'=>$created['reversal']->id,'replacement_transaction_id'=>$created['replacement']->id,'fingerprint'=>$created['fingerprint']];
    }

    /** @return array<string,mixed> */
    public function executeIndividual(string $entityId,string $requestId,string $idempotencyKey,int $actorUserId):array
    {
        $staged=$this->stageIndividual($entityId,$requestId,$idempotencyKey,$actorUserId);
        $created=$this->factory->createApprovedPair($entityId,$requestId,$idempotencyKey,$actorUserId);
        $pair=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->findOrFail($staged['execution_pair_id']);

        $reversalFingerprint=hash('sha256',$created['fingerprint'].'|REV');$replacementFingerprint=hash('sha256',$created['fingerprint'].'|REPLACEMENT');
        $result=$this->posting->postAccountingDateCorrectionPairAtomically($created['reversal']->id,$idempotencyKey.':REV',$reversalFingerprint,$created['replacement']->id,$idempotencyKey.':REPLACEMENT',$replacementFingerprint,$actorUserId,function($reversal,$replacement)use($entityId,$requestId,$pair,$actorUserId,$created):array{
            $lockedRequest=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($requestId);
            $lockedPair=AccountingDateCorrectionExecutionPair::query()->lockForUpdate()->findOrFail($pair->id);
            $lineage=AccountingDateCorrectionLineage::query()->where('correction_request_id',$requestId)->lockForUpdate()->firstOrFail();
            $replacementWorkflow=ReplacementRealizationRequest::query()->where('correction_request_id',$requestId)->where('status','approved')->lockForUpdate()->first();
            if($lineage->original_realization_id){
                if(!$replacementWorkflow)throw new FinancialDomainException('E-REPL-REAL-NOT-APPROVED','Replacement Realization workflow belum approved saat posting akan difinalisasi.');
                $this->replacementRealizations->materialize($entityId,$replacementWorkflow->id,$actorUserId);
            }
            if($lockedRequest->status==='governance_approved')AccountingDateCorrectionStateGuard::execution('governance_approved','execution_ready',fn()=>$lockedRequest->update(['status'=>'execution_ready']));
            AccountingDateCorrectionStateGuard::execution('execution_ready','executing',fn()=>$lockedRequest->update(['status'=>'executing','execution_started_at'=>now()]));
            AccountingDateCorrectionExecutionPairStateGuard::transition('execution_ready','executing',fn()=>$lockedPair->update(['status'=>'executing','execution_started_at'=>now()]));
            AccountingDateCorrectionLineageGuard::execution(fn()=>$lineage->update(['reversal_transaction_id'=>$created['reversal']->id,'replacement_transaction_id'=>$created['replacement']->id,'reversal_journal_id'=>$reversal->journalId,'replacement_journal_id'=>$replacement->journalId]));
            AccountingDateCorrectionExecutionPairStateGuard::transition('executing','completed',fn()=>$lockedPair->update(['status'=>'completed','completed_at'=>now()]));
            AccountingDateCorrectionStateGuard::execution('executing','completed',fn()=>$lockedRequest->update(['status'=>'completed','active_transaction_guard'=>null,'completed_at'=>now()]));
            $this->audit->record($entityId,'accounting_date_correction_superadmin_override_executed','accounting_date_correction_request',$requestId,$requestId,$actorUserId,null,['override'=>AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,'override_fingerprint'=>$created['fingerprint'],'reversal_transaction_id'=>$created['reversal']->id,'replacement_transaction_id'=>$created['replacement']->id,'reversal_journal_id'=>$reversal->journalId,'replacement_journal_id'=>$replacement->journalId]);
            return['request_id'=>$requestId,'execution_pair_id'=>$lockedPair->id];
        },$created['policy_exception']);
        return['request_id'=>$requestId,'execution_pair_id'=>$pair->id,'reversal'=>['transaction_id'=>$result['reversal']->transactionId,'journal_id'=>$result['reversal']->journalId,'voucher_id'=>$result['reversal']->voucherId],'replacement'=>['transaction_id'=>$result['replacement']->transactionId,'journal_id'=>$result['replacement']->journalId,'voucher_id'=>$result['replacement']->voucherId],'fingerprint'=>$created['fingerprint']];
    }

    /** @return array<string,mixed> */
    public function executeBatch(string $entityId,string $batchId,string $idempotencyKey,int $actorUserId):array
    {
        $this->assertIdempotency($idempotencyKey);
        // Reuse the canonical staging path so stale pairs are invalidated and
        // replaced from the latest append-only reauthorization before any
        // posting transaction begins.
        $this->stageBatch($entityId,$batchId,$idempotencyKey,$actorUserId);
        $batch=AccountingDateCorrectionBatch::query()->with(['items'=>fn($query)=>$query->orderBy('item_no')])->where('accounting_entity_id',$entityId)->findOrFail($batchId);
        if($batch->status!=='approved')throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-NOT-APPROVED','Batch harus approved sebelum staging eksekusi.');
        $this->governance->authorizeSuperAdminOverride((int)$batch->created_by_user_id,(int)$batch->approved_by_user_id,$actorUserId,$this->isSuperAdmin($actorUserId),null,$batch->correlation_id,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);
        $gate=$this->batches->revalidateForExecution($entityId,$batchId);
        $businessBlockers=array_values(array_filter($gate['blockers'],fn(string$code):bool=>!in_array($code,['BATCH_EXECUTOR_UNAVAILABLE','BATCH_EXECUTION_CONTRACT_INCOMPLETE'],true)));
        if($businessBlockers!==[])throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-BLOCKED','Batch masih mempunyai blocker bisnis canonical.',['blockers'=>$businessBlockers]);

        $prepared=[];
        foreach($batch->items as$item){
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->where('original_transaction_id',$item->transaction_id)->where('target_accounting_date',$batch->target_accounting_date->toDateString())->where('status','governance_approved')->first();
            if(!$request)throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-REQUEST','Setiap anggota batch wajib mempunyai Correction Request governance_approved.',['transaction_id'=>$item->transaction_id]);
            $pair=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->where('active_original_guard',$item->transaction_id)->where('correction_request_id',$request->id)->firstOrFail();
            $memberKey=$pair->idempotency_key;
            $created=$this->factory->createApprovedPair($entityId,$request->id,$memberKey,$actorUserId);
            if($pair->status!=='execution_ready')throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-PAIR','Anggota batch tidak mencapai execution_ready.',['transaction_id'=>$item->transaction_id,'pair_status'=>$pair->status]);
            $this->batchItems->link($entityId,$batchId,$item->id,$request->id,$pair->id,$actorUserId);
            $prepared[]=['item'=>$item,'request'=>$request,'pair'=>$pair,'created'=>$created,'reversal_transaction_id'=>$created['reversal']->id,'replacement_transaction_id'=>$created['replacement']->id,'reversal_idempotency_key'=>$memberKey.':REV','replacement_idempotency_key'=>$memberKey.':REPLACEMENT','reversal_fingerprint'=>hash('sha256',$created['fingerprint'].'|REV'),'replacement_fingerprint'=>hash('sha256',$created['fingerprint'].'|REPLACEMENT'),'policy_exception'=>$created['policy_exception']];
        }
        if(count($prepared)!==$batch->items->count())throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-MEMBERSHIP','Tidak seluruh anggota batch berhasil distage.');

        $postingPairs=array_map(fn(array$row):array=>Arr::only($row,['reversal_transaction_id','replacement_transaction_id','reversal_idempotency_key','replacement_idempotency_key','reversal_fingerprint','replacement_fingerprint','policy_exception']),$prepared);
        $result=$this->posting->postAccountingDateCorrectionBatchAtomically($postingPairs,$actorUserId,function(array$postingResults)use($entityId,$batch,$prepared,$actorUserId):array{
            $lineages=[];
            foreach($prepared as$index=>$row){
                $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($row['request']->id);
                $pair=AccountingDateCorrectionExecutionPair::query()->lockForUpdate()->findOrFail($row['pair']->id);
                $lineage=AccountingDateCorrectionLineage::query()->where('correction_request_id',$request->id)->lockForUpdate()->firstOrFail();
                $replacementWorkflow=ReplacementRealizationRequest::query()->where('correction_request_id',$request->id)->where('status','approved')->lockForUpdate()->first();
                if($lineage->original_realization_id){
                    if(!$replacementWorkflow)throw new FinancialDomainException('E-REPL-REAL-NOT-APPROVED','Replacement Realization anggota batch belum approved.',['transaction_id'=>$row['item']->transaction_id]);
                    $this->replacementRealizations->materialize($entityId,$replacementWorkflow->id,$actorUserId);
                }
                AccountingDateCorrectionStateGuard::execution('governance_approved','execution_ready',fn()=>$request->update(['status'=>'execution_ready']));
                AccountingDateCorrectionStateGuard::execution('execution_ready','executing',fn()=>$request->update(['status'=>'executing','execution_started_at'=>now()]));
                AccountingDateCorrectionExecutionPairStateGuard::transition('execution_ready','executing',fn()=>$pair->update(['status'=>'executing','execution_started_at'=>now()]));
                $posted=$postingResults[$index];
                AccountingDateCorrectionLineageGuard::execution(fn()=>$lineage->update(['reversal_transaction_id'=>$row['created']['reversal']->id,'replacement_transaction_id'=>$row['created']['replacement']->id,'reversal_journal_id'=>$posted['reversal']->journalId,'replacement_journal_id'=>$posted['replacement']->journalId]));
                AccountingDateCorrectionExecutionPairStateGuard::transition('executing','completed',fn()=>$pair->update(['status'=>'completed','completed_at'=>now()]));
                AccountingDateCorrectionStateGuard::execution('executing','completed',fn()=>$request->update(['status'=>'completed','active_transaction_guard'=>null,'completed_at'=>now()]));
                $lineages[]=['request_id'=>$request->id,'execution_pair_id'=>$pair->id];
            }
            $this->audit->record($entityId,'accounting_date_correction_bank_batch_executed','accounting_date_correction_batch',$batch->id,$batch->correlation_id,$actorUserId,null,['member_count'=>count($prepared),'batch_fingerprint'=>$batch->snapshot_fingerprint,'override'=>AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON]);
            return$lineages;
        });
        return['batch_id'=>$batch->id,'correlation_id'=>$batch->correlation_id,'items'=>$result['items'],'lineage'=>$result['lineage'],'atomic'=>true];
    }

    /** @return array<string,mixed> */
    public function stageBatch(string $entityId,string $batchId,string $idempotencyKey,int $actorUserId):array
    {
        $this->assertIdempotency($idempotencyKey);
        return DB::transaction(function()use($entityId,$batchId,$idempotencyKey,$actorUserId):array{
        $batch=AccountingDateCorrectionBatch::query()->with(['items'=>fn($query)=>$query->orderBy('item_no')])->where('accounting_entity_id',$entityId)->findOrFail($batchId);
        if($batch->status!=='approved')throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-NOT-APPROVED','Batch harus approved sebelum staging eksekusi.');
        $this->governance->authorizeSuperAdminOverride((int)$batch->created_by_user_id,(int)$batch->approved_by_user_id,$actorUserId,$this->isSuperAdmin($actorUserId),null,$batch->correlation_id,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);
        $gate=$this->batches->revalidateForExecution($entityId,$batchId);
        $businessBlockers=array_values(array_filter($gate['blockers'],fn(string$code):bool=>!in_array($code,['BATCH_EXECUTOR_UNAVAILABLE','BATCH_EXECUTION_CONTRACT_INCOMPLETE'],true)));
        if($businessBlockers!==[])throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-BLOCKED','Batch masih mempunyai blocker bisnis canonical.',['blockers'=>$businessBlockers]);
        $staged=[];
        foreach($batch->items as$item){
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->where('original_transaction_id',$item->transaction_id)->where('target_accounting_date',$batch->target_accounting_date->toDateString())->where('status','governance_approved')->first();
            if(!$request)throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-REQUEST','Setiap anggota batch wajib mempunyai Correction Request governance_approved.',['transaction_id'=>$item->transaction_id]);
            // Member identity must survive a retried HTTP staging command. The
            // batch/request tuple is canonical; the outer key only identifies
            // this command invocation. Reuse a previously staged member after
            // an interrupted pre-atomic implementation instead of duplicating
            // approved drafts or failing on active_original_guard.
            $memberKey=\Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL,$batch->id.'|'.$request->id)->toString();
            $pair=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->where('active_original_guard',$item->transaction_id)->lockForUpdate()->first();
            if($pair){
                if($pair->correction_request_id!==$request->id||!$pair->reversal_transaction_id||!$pair->replacement_transaction_id)throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-PAIR','Execution pair aktif tidak cocok dengan anggota batch.',['transaction_id'=>$item->transaction_id]);
                $pair=$this->pairs->revalidate($entityId,$pair->id,$actorUserId);
                if($pair->status==='execution_ready'){
                    $created=['reversal'=>FinancialTransaction::findOrFail($pair->reversal_transaction_id),'replacement'=>FinancialTransaction::findOrFail($pair->replacement_transaction_id)];
                }else{
                    $pair=null;
                }
            }
            if(!$pair){
                $approvedPlan=$this->approvedPlans->resolve($request);
                $memberKey=\Ramsey\Uuid\Uuid::uuid5(\Ramsey\Uuid\Uuid::NAMESPACE_URL,$batch->id.'|'.$request->id.'|'.$approvedPlan['fingerprint'])->toString();
                $created=$this->factory->createApprovedPair($entityId,$request->id,$memberKey,$actorUserId);
                $pair=$this->pairs->prepare($entityId,$request->id,$memberKey,$actorUserId);
            }
            if($pair->status==='prepared')$pair=$this->pairs->validate($entityId,$pair->id,$actorUserId);
            if($pair->status==='validated')$pair=$this->pairs->stage($entityId,$pair->id,$actorUserId);
            if($pair->status==='staged')$pair=$this->pairs->bindTransactionsAndMarkReady($entityId,$pair->id,$created['reversal']->id,$created['replacement']->id,$actorUserId);
            if($pair->status!=='execution_ready')throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-PAIR','Anggota batch tidak mencapai execution_ready.',['transaction_id'=>$item->transaction_id,'pair_status'=>$pair->status]);
            $link=$this->batchItems->link($entityId,$batchId,$item->id,$request->id,$pair->id,$actorUserId);
            $staged[]=['transaction_id'=>$item->transaction_id,'request_id'=>$request->id,'execution_pair_id'=>$pair->id,'batch_link_id'=>$link->id,'reversal_transaction_id'=>$created['reversal']->id,'replacement_transaction_id'=>$created['replacement']->id];
        }
        if(count($staged)!==$batch->items->count())throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-MEMBERSHIP','Tidak seluruh anggota batch berhasil distage.');
        $assessment=$this->assessBatch($entityId,$batchId,$actorUserId);
        if(collect($assessment['blockers'])->contains(fn(array$blocker):bool=>$blocker['code']==='BATCH_EXECUTION_LINEAGE_INCOMPLETE'))throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-BATCH-LINKAGE','Linkage batch belum lengkap setelah staging.');
        $this->audit->record($entityId,'accounting_date_correction_bank_batch_staged','accounting_date_correction_batch',$batch->id,$batch->correlation_id,$actorUserId,null,['member_count'=>count($staged),'batch_fingerprint'=>$batch->snapshot_fingerprint,'override'=>AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON]);
        return['batch_id'=>$batch->id,'correlation_id'=>$batch->correlation_id,'items'=>$staged,'atomic_scope_complete'=>true,'posted'=>false];
        },3);
    }

    private function activeRevConfiguration(string $entityId):bool
    {
        return ReversalConfigurationRequest::query()->where('accounting_entity_id',$entityId)->where('status','activated')->exists();
    }

    private function lockEntity(string $entityId):void
    {
        if(!DB::table('financial_v2_accounting_entities')->where('id',$entityId)->where('status','active')->lockForUpdate()->exists())throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-ENTITY','Entity aktif tidak tersedia.');
    }

    private function assertActor(int $actor):void
    {
        if($actor<=0)throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-ACTOR','Actor eksekusi tidak valid.');
    }

    private function isSuperAdmin(int $actor):bool
    {
        $user=User::query()->find($actor);
        return $user!==null&&$user->hasRole('SuperAdmin');
    }

    private function assertIdempotency(string $key):void
    {
        if(!Str::isUuid($key))throw new FinancialDomainException('E-ACCOUNTING-CORRECTION-IDEMPOTENCY','Idempotency key eksekusi harus UUID.');
    }

    /** @param array<int,array<string,mixed>> $blockers @return array<int,array<string,mixed>> */
    private function unique(array $blockers):array
    {
        return collect($blockers)->unique('code')->values()->all();
    }

    /** @return array<string,mixed> */
    private function blocker(string $code,string $message,array $context=[]):array
    {
        return['code'=>$code,'message'=>$message]+$context;
    }

    private function hash(array $snapshot):string
    {
        // Must match AccountingDateCorrectionWorkflowService::conditionFingerprint().
        return hash('sha256',json_encode($this->canonicalize($snapshot),JSON_THROW_ON_ERROR));
    }

    private function canonicalize(mixed $value):mixed
    {
        if(!is_array($value))return$value;
        if(array_is_list($value))return array_map(fn(mixed$item)=>$this->canonicalize($item),$value);
        ksort($value);
        foreach($value as$key=>$item)$value[$key]=$this->canonicalize($item);
        return$value;
    }
}
