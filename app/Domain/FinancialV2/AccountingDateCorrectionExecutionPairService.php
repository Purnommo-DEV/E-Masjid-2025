<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionApproval;
use App\Models\FinancialV2\AccountingDateCorrectionExecutionPair;
use App\Models\FinancialV2\AccountingDateCorrectionLineage;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\ReversalConfigurationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Staging contract only. It never creates or posts a financial transaction. */
final class AccountingDateCorrectionExecutionPairService
{
    public function __construct(
        private readonly FinancialV2TransactionRunner $transactions,
        private readonly AccountingDateCorrectionWorkflowService $workflow,
        private readonly AuditTrailService $audit,
        private readonly AccountingDateCorrectionGovernancePolicy $governance,
        private readonly AccountingDateCorrectionApprovedPlanResolver $approvedPlans,
    ){}

    public function prepare(string$entityId,string$requestId,string$idempotencyKey,int$actor):AccountingDateCorrectionExecutionPair
    {
        $this->assertUuid($idempotencyKey,'idempotency_key');
        return$this->transactions->run(function()use($entityId,$requestId,$idempotencyKey,$actor):AccountingDateCorrectionExecutionPair{
            $this->assertActor($actor);$this->lockEntity($entityId);
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($requestId);
            if(!in_array($request->status,['governance_approved','execution_ready'],true))throw new FinancialDomainException('E-PAIR-GOVERNANCE','Execution pair hanya dapat dipersiapkan dari request governance_approved atau dilanjutkan secara idempoten setelah gate execution_ready.');
            $approval=AccountingDateCorrectionApproval::query()->where('correction_request_id',$request->id)->where('decision','approved')->lockForUpdate()->firstOrFail();
            $lineage=AccountingDateCorrectionLineage::query()->where('correction_request_id',$request->id)->lockForUpdate()->firstOrFail();
            $transaction=FinancialTransaction::query()->with(['type','splits','category'])->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($request->original_transaction_id);
            $journal=Journal::query()->where('accounting_entity_id',$entityId)->where('transaction_id',$transaction->id)->where('journal_status','posted')->lockForUpdate()->firstOrFail();
            if($lineage->original_transaction_id!==$transaction->id||$lineage->original_journal_id!==$journal->id||$request->original_journal_id!==$journal->id)throw new FinancialDomainException('E-PAIR-LINEAGE','Lineage sumber execution pair tidak konsisten.');

            $snapshot=$this->currentSnapshot($request,$transaction,$actor);
            $approvedPlan=$this->approvedPlans->resolve($request);
            $approvedFingerprint=(string)$approvedPlan['fingerprint'];
            if(!hash_equals($approvedFingerprint,$snapshot['fingerprint']))throw new FinancialDomainException('E-PAIR-APPROVAL-DRIFT','Plan canonical tidak lagi cocok dengan fingerprint approval.');
            $identity=$this->identityFingerprint($request,$transaction,$journal,$approvedFingerprint);
            $retry=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->where('idempotency_key',$idempotencyKey)->first();
            if($retry){if($retry->correction_request_id!==$request->id||!hash_equals($retry->source_fingerprint,$identity))throw new FinancialDomainException('E-PAIR-IDEMPOTENCY','Idempotency key dipakai untuk execution pair berbeda.');return$retry;}
            if($request->status!=='governance_approved')throw new FinancialDomainException('E-PAIR-GOVERNANCE','Request execution_ready hanya boleh melanjutkan execution pair yang sudah ada dengan idempotency key yang sama.');
            if(AccountingDateCorrectionExecutionPair::query()->where('active_original_guard',$transaction->id)->lockForUpdate()->exists())throw new FinancialDomainException('E-PAIR-ACTIVE','Transaksi original telah mempunyai execution pair aktif.');

            $rev=$this->activeRevConfiguration($entityId);
            $blockers=[];
            if(!$rev)$blockers[]=['code'=>'REV_CONFIGURATION_INACTIVE','message'=>'Konfigurasi REV aktif belum tersedia.'];
            if(($snapshot['snapshot']['blocking_reasons']??[])!==[])$blockers[]=['code'=>'CANONICAL_PLAN_BLOCKED','message'=>'Plan canonical masih mempunyai blocker.','reasons'=>$snapshot['snapshot']['blocking_reasons']];
            $id=(string)Str::uuid();
            DB::table((new AccountingDateCorrectionExecutionPair)->getTable())->insert([
                'id'=>$id,'accounting_entity_id'=>$entityId,'correction_request_id'=>$request->id,'active_original_guard'=>$transaction->id,
                'original_transaction_id'=>$transaction->id,'original_journal_id'=>$journal->id,'target_accounting_date'=>$request->target_accounting_date,
                'reversal_configuration_request_id'=>$rev?->id,'reason_code_id'=>$rev?->reason_code_id,'status'=>'prepared','approved_plan_snapshot'=>json_encode($approvedPlan['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
                'approved_plan_fingerprint'=>$approvedFingerprint,'source_fingerprint'=>$identity,'validation_snapshot'=>json_encode($snapshot['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
                'validation_fingerprint'=>$snapshot['fingerprint'],'blockers'=>$blockers?json_encode($blockers,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES):null,
                'idempotency_key'=>$idempotencyKey,'prepared_by_user_id'=>$actor,'created_at'=>now(),'updated_at'=>now(),
            ]);
            $pair=AccountingDateCorrectionExecutionPair::findOrFail($id);$this->audit($pair,'accounting_date_correction_execution_pair_prepared',$actor);
            return$pair;
        });
    }

    public function validate(string$entityId,string$id,int$actor):AccountingDateCorrectionExecutionPair
    {
        return$this->transactions->run(function()use($entityId,$id,$actor):AccountingDateCorrectionExecutionPair{
            $this->assertActor($actor);$this->lockEntity($entityId);$pair=$this->locked($entityId,$id,'prepared');
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($pair->correction_request_id);
            $transaction=FinancialTransaction::query()->with(['type','splits','category'])->lockForUpdate()->findOrFail($pair->original_transaction_id);
            $snapshot=$this->currentSnapshot($request,$transaction,$actor);$blockers=[];
            if(!hash_equals($pair->approved_plan_fingerprint,$snapshot['fingerprint']))$blockers[]=['code'=>'APPROVED_PLAN_DRIFT','message'=>'Plan canonical berubah sejak approval.'];
            if(!$this->activeRevConfiguration($entityId))$blockers[]=['code'=>'REV_CONFIGURATION_INACTIVE','message'=>'Konfigurasi REV aktif belum tersedia.'];
            if(($snapshot['snapshot']['blocking_reasons']??[])!==[])$blockers[]=['code'=>'CANONICAL_PLAN_BLOCKED','message'=>'Plan canonical masih mempunyai blocker.','reasons'=>$snapshot['snapshot']['blocking_reasons']];
            DB::table($pair->getTable())->where('id',$pair->id)->update(['validation_snapshot'=>json_encode($snapshot['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'validation_fingerprint'=>$snapshot['fingerprint'],'blockers'=>$blockers?json_encode($blockers,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES):null,'updated_at'=>now()]);
            $pair->refresh();
            if($blockers!==[])return$pair;
            AccountingDateCorrectionExecutionPairStateGuard::transition('prepared','validated',fn()=>$pair->update(['status'=>'validated','validated_by_user_id'=>$actor,'validated_at'=>now()]));
            $this->audit($pair,'accounting_date_correction_execution_pair_validated',$actor);return$pair->fresh();
        });
    }

    public function stage(string$entityId,string$id,int$actor):AccountingDateCorrectionExecutionPair
    {
        return $this->transactions->run(function()use($entityId,$id,$actor):AccountingDateCorrectionExecutionPair{
            $this->assertActor($actor);$this->lockEntity($entityId);$pair=$this->locked($entityId,$id,'validated');
            if(!$pair->reversal_configuration_request_id||!$this->activeRevConfiguration($entityId))throw new FinancialDomainException('E-PAIR-REV-INACTIVE','Staging ditolak karena konfigurasi REV aktif tidak tersedia.');
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($pair->correction_request_id);
            $approval=AccountingDateCorrectionApproval::query()->where('correction_request_id',$request->id)->where('decision','approved')->lockForUpdate()->firstOrFail();
            $approvedPlan=$this->approvedPlans->resolve($request);
            $override=$this->governance->authorizeSuperAdminOverride((int)$request->requested_by_user_id,(int)$approval->decided_by_user_id,$actor,$this->isSuperAdmin($actor),$pair->original_transaction_id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);
            if(($pair->blockers??[])!==[])throw new FinancialDomainException('E-PAIR-BLOCKED','Execution pair masih mempunyai blocker canonical.',['blockers'=>$pair->blockers]);
            if(!hash_equals((string)$approvedPlan['fingerprint'],(string)$pair->validation_fingerprint)||!hash_equals((string)$pair->approved_plan_fingerprint,(string)$pair->validation_fingerprint))throw new FinancialDomainException('E-PAIR-APPROVAL-DRIFT','Fingerprint approval/reauthorization dan validasi execution pair tidak cocok.');
            AccountingDateCorrectionExecutionPairStateGuard::transition('validated','staged',fn()=>$pair->update(['status'=>'staged','staged_at'=>now()]));
            $this->audit->record($entityId,'accounting_date_correction_superadmin_override_used','accounting_date_correction_execution_pair',$pair->id,$request->id,$actor,null,$override+['recorded_at'=>now()->toIso8601String()]);
            $this->audit($pair,'accounting_date_correction_execution_pair_staged',$actor);
            return $pair->fresh();
        });
    }

    public function bindTransactionsAndMarkReady(string $entityId,string $id,string $reversalTransactionId,string $replacementTransactionId,int $actor):AccountingDateCorrectionExecutionPair
    {
        return $this->transactions->run(function()use($entityId,$id,$reversalTransactionId,$replacementTransactionId,$actor):AccountingDateCorrectionExecutionPair{
            $this->assertActor($actor);$this->lockEntity($entityId);$pair=$this->locked($entityId,$id,'staged');
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($pair->correction_request_id);
            $approval=AccountingDateCorrectionApproval::query()->where('correction_request_id',$request->id)->where('decision','approved')->lockForUpdate()->firstOrFail();
            $this->governance->authorizeSuperAdminOverride((int)$request->requested_by_user_id,(int)$approval->decided_by_user_id,$actor,$this->isSuperAdmin($actor),$pair->original_transaction_id,null,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);
            $reversal=FinancialTransaction::query()->with('type')->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($reversalTransactionId);
            $replacement=FinancialTransaction::query()->with('type')->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($replacementTransactionId);
            if($reversal->status!=='approved'||$reversal->type?->code!=='REV'||$reversal->related_transaction_id!==$pair->original_transaction_id)throw new FinancialDomainException('E-PAIR-REVERSAL','Reversal transaction belum approved atau lineage original tidak cocok.');
            $original=FinancialTransaction::query()->with('type')->findOrFail($pair->original_transaction_id);
            if($replacement->status!=='approved'||$replacement->type?->code!==$original->type?->code||!$replacement->accounting_date->isSameDay($pair->target_accounting_date))throw new FinancialDomainException('E-PAIR-REPLACEMENT','Replacement transaction belum approved atau tanggal/tipe tidak cocok.');
            AccountingDateCorrectionExecutionPairStateGuard::transition('staged','execution_ready',function()use($pair,$reversal,$replacement,$actor):void{$pair->update(['status'=>'execution_ready','reversal_transaction_id'=>$reversal->id,'replacement_transaction_id'=>$replacement->id,'execution_ready_at'=>now()]);});
            $this->audit($pair,'accounting_date_correction_execution_pair_ready',$actor);return$pair->fresh();
        });
    }

    /** Revalidates an existing pair and permanently invalidates stale approval facts. */
    public function revalidate(string$entityId,string$id,int$actor):AccountingDateCorrectionExecutionPair
    {
        return$this->transactions->run(function()use($entityId,$id,$actor):AccountingDateCorrectionExecutionPair{
            $this->assertActor($actor);$this->lockEntity($entityId);$pair=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($id);
            if(!in_array($pair->status,['prepared','validated','staged','execution_ready'],true))throw new FinancialDomainException('E-PAIR-STATE','Execution pair tidak berada pada status yang dapat direvalidasi.');
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($pair->correction_request_id);$transaction=FinancialTransaction::query()->with(['type','splits','category'])->lockForUpdate()->findOrFail($pair->original_transaction_id);$current=$this->currentSnapshot($request,$transaction,$actor);
            $approved=$this->approvedPlans->resolve($request);
            if(hash_equals((string)$approved['fingerprint'],$current['fingerprint'])){
                if(hash_equals($pair->approved_plan_fingerprint,$current['fingerprint'])&&hash_equals($pair->validation_fingerprint,$current['fingerprint']))return$pair;
                if($pair->reversal_transaction_id&&Journal::query()->where('transaction_id',$pair->reversal_transaction_id)->exists())throw new FinancialDomainException('E-PAIR-REAUTH-POSTED','Execution pair yang sudah menghasilkan jurnal tidak dapat diotorisasi ulang.');
                if($pair->replacement_transaction_id&&Journal::query()->where('transaction_id',$pair->replacement_transaction_id)->exists())throw new FinancialDomainException('E-PAIR-REAUTH-POSTED','Execution pair yang sudah menghasilkan jurnal tidak dapat diotorisasi ulang.');
                DB::table($pair->getTable())->where('id',$pair->id)->where('status',$pair->status)->update([
                    'approved_plan_snapshot'=>json_encode($approved['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
                    'approved_plan_fingerprint'=>$approved['fingerprint'],
                    'validation_snapshot'=>json_encode($current['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),
                    'validation_fingerprint'=>$current['fingerprint'],
                    'blockers'=>null,
                    'updated_at'=>now(),
                ]);
                $pair->refresh();
                $this->audit->record($entityId,'accounting_date_correction_execution_pair_reauthorized','accounting_date_correction_execution_pair',$pair->id,$request->id,$actor,null,['status'=>$pair->status,'approved_plan_fingerprint'=>$approved['fingerprint'],'authorization_source'=>$approved['source'],'financial_facts_written'=>false]);
                return$pair;
            }
            $blockers=[['code'=>'APPROVED_PLAN_DRIFT','message'=>'Plan canonical berubah sejak approval; pair lama tidak dapat digunakan.']];
            if($pair->status==='prepared'){DB::table($pair->getTable())->where('id',$pair->id)->update(['validation_snapshot'=>json_encode($current['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'validation_fingerprint'=>$current['fingerprint'],'blockers'=>json_encode($blockers,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'updated_at'=>now()]);return$pair->fresh();}
            $from=$pair->status;AccountingDateCorrectionExecutionPairStateGuard::transition($from,'invalidated',fn()=>$pair->update(['status'=>'invalidated','active_original_guard'=>null,'validation_snapshot'=>$current['snapshot'],'validation_fingerprint'=>$current['fingerprint'],'blockers'=>$blockers,'invalidated_at'=>now()]));$this->audit($pair,'accounting_date_correction_execution_pair_invalidated',$actor);return$pair->fresh();
        });
    }

    private function currentSnapshot($request,$transaction,int$actor):array
    {
        $snapshot=$this->workflow->preview($transaction,['target_accounting_date'=>$request->target_accounting_date->toDateString(),'attachment_id'=>$request->evidence_attachment_id],$actor,$request->id);
        return['snapshot'=>$snapshot,'fingerprint'=>$this->hash($snapshot)];
    }
    private function identityFingerprint($request,$transaction,$journal,string$plan):string{return$this->hash(['request_id'=>$request->id,'entity_id'=>$request->accounting_entity_id,'original_transaction_id'=>$transaction->id,'original_journal_id'=>$journal->id,'target_accounting_date'=>$request->target_accounting_date->toDateString(),'approved_plan_fingerprint'=>$plan]);}
    private function activeRevConfiguration(string$entityId):?ReversalConfigurationRequest{return ReversalConfigurationRequest::query()->where('accounting_entity_id',$entityId)->where('status','activated')->lockForUpdate()->first();}
    private function locked(string$entityId,string$id,string$status):AccountingDateCorrectionExecutionPair{$pair=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->find($id);if(!$pair||$pair->status!==$status)throw new FinancialDomainException('E-PAIR-STATE','Status atau Entity execution pair tidak sesuai.');return$pair;}
    private function lockEntity(string$id):void{if(!DB::table('financial_v2_accounting_entities')->where('id',$id)->where('status','active')->lockForUpdate()->exists())throw new FinancialDomainException('E-PAIR-ENTITY','Entity aktif tidak tersedia.');}
    private function audit($pair,string$event,int$actor):void{$this->audit->record($pair->accounting_entity_id,$event,'accounting_date_correction_execution_pair',$pair->id,$pair->correction_request_id,$actor,null,['status'=>$pair->status,'source_fingerprint'=>$pair->source_fingerprint,'execution_ready'=>false]);}
    private function assertActor(int$actor):void{if($actor<=0)throw new FinancialDomainException('E-PAIR-ACTOR','Actor tidak valid.');}
    private function isSuperAdmin(int$actor):bool{$user=User::query()->find($actor);return$user!==null&&$user->hasRole('SuperAdmin');}
    private function assertUuid(string$value,string$field):void{if(!Str::isUuid($value))throw new FinancialDomainException('E-PAIR-UUID',"{$field} harus UUID.");}
    private function hash(array$value):string{return hash('sha256',json_encode($this->canonicalize($value),JSON_THROW_ON_ERROR));}
    private function canonicalize(mixed$value):mixed{if(!is_array($value))return$value;if(array_is_list($value))return array_map(fn($v)=>$this->canonicalize($v),$value);ksort($value);foreach($value as$k=>$v)$value[$k]=$this->canonicalize($v);return$value;}
}
