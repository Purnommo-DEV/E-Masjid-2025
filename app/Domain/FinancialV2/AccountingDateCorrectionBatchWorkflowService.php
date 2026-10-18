<?php
namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionBatch;
use App\Models\FinancialV2\AccountingDateCorrectionBatchApproval;
use App\Models\FinancialV2\AccountingDateCorrectionBatchItem;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\Reconciliation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Governance-only batch workflow. It has no correction-request or posting method. */
final class AccountingDateCorrectionBatchWorkflowService
{
    public function __construct(private readonly AccountingDateCorrectionCandidateEvaluator $evaluator, private readonly AuditTrailService $audit, private readonly AccountingDateCorrectionGovernancePolicy $governance) {}

    public function createDraft(string $entityId,string $correlationId,string $targetDate,string $idempotencyKey,int $actor,int $expectedMemberCount=5):AccountingDateCorrectionBatch
    {
        return DB::transaction(function()use($entityId,$correlationId,$targetDate,$idempotencyKey,$actor,$expectedMemberCount){
            $this->lockEntity($entityId);$this->assertUuid($correlationId,'correlation_id');$this->assertUuid($idempotencyKey,'idempotency_key');
            if($expectedMemberCount<1)throw new FinancialDomainException('E-ACCT-BATCH-MEMBERSHIP','Jumlah anggota yang disahkan harus lebih dari nol.');
            $evaluation=$this->evaluateLocked($entityId,$correlationId,$targetDate,$expectedMemberCount);
            $retry=AccountingDateCorrectionBatch::query()->where('accounting_entity_id',$entityId)->where('idempotency_key',$idempotencyKey)->first();
            if($retry){if(!hash_equals($retry->snapshot_fingerprint,$evaluation['fingerprint']))throw new FinancialDomainException('E-ACCT-BATCH-IDEMPOTENCY','Idempotency key digunakan untuk batch berbeda.');return$retry;}
            if(AccountingDateCorrectionBatch::query()->where('active_group_guard',$correlationId)->lockForUpdate()->exists())throw new FinancialDomainException('E-ACCT-BATCH-ACTIVE','Correlation group masih mempunyai workflow aktif.');
            $id=(string)Str::uuid();DB::table((new AccountingDateCorrectionBatch)->getTable())->insert(['id'=>$id,'accounting_entity_id'=>$entityId,'correlation_id'=>$correlationId,'active_group_guard'=>$correlationId,'target_accounting_date'=>$targetDate,'status'=>'draft','batch_snapshot'=>json_encode($evaluation['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'snapshot_fingerprint'=>$evaluation['fingerprint'],'batch_blockers'=>json_encode($evaluation['batch_blockers'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'net_effect'=>$evaluation['net_effect'],'idempotency_key'=>$idempotencyKey,'created_by_user_id'=>$actor,'created_at'=>now(),'updated_at'=>now()]);
            foreach($evaluation['items'] as$i=>$row)DB::table((new AccountingDateCorrectionBatchItem)->getTable())->insert(['id'=>(string)Str::uuid(),'batch_id'=>$id,'transaction_id'=>$row['transaction_id'],'source_journal_id'=>$row['source_journal_id'],'item_no'=>$i+1,'item_snapshot'=>json_encode($row['snapshot'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'item_fingerprint'=>$row['fingerprint'],'blockers'=>json_encode($row['blockers'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'created_at'=>now(),'updated_at'=>now()]);
            $batch=AccountingDateCorrectionBatch::with('items')->findOrFail($id);$this->audit($batch,'accounting_date_correction_batch_draft_created',$actor);return$batch;
        },3);
    }
    public function submit(string$entityId,string$id,int$actor):AccountingDateCorrectionBatch{return$this->transition($entityId,$id,'draft','pending_review','submitted',$actor,null);}
    public function approve(string$entityId,string$id,int$actor,?string$note=null):AccountingDateCorrectionBatch{return DB::transaction(function()use($entityId,$id,$actor,$note){$this->lockEntity($entityId);$b=$this->locked($entityId,$id,'pending_review');if((int)$b->created_by_user_id===$actor){$user=User::query()->find($actor);$this->governance->authorizeSuperAdminOverride($actor,$actor,$actor,$user?->hasRole('SuperAdmin')===true,null,$b->correlation_id,AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON);}$this->assertCurrent($b);$this->persist($b,'pending_review','approved',['approved_at'=>now(),'approved_by_user_id'=>$actor]);$this->decision($b,'approved',$actor,$note);$this->audit($b,'accounting_date_correction_batch_approved',$actor);return$b->fresh(['items','approvals']);},3);}
    public function reject(string$entityId,string$id,int$actor,string$note):AccountingDateCorrectionBatch{return DB::transaction(function()use($entityId,$id,$actor,$note){$this->lockEntity($entityId);$b=$this->locked($entityId,$id,'pending_review');if((int)$b->created_by_user_id===$actor)throw new FinancialDomainException('E-ACCT-BATCH-MAKER-CHECKER','Maker tidak boleh menolak batch sendiri.');$this->persist($b,'pending_review','rejected',['active_group_guard'=>null]);$this->decision($b,'rejected',$actor,$note);$this->audit($b,'accounting_date_correction_batch_rejected',$actor);return$b->fresh('approvals');},3);}
    public function cancel(string$entityId,string$id,int$actor,string$reason):AccountingDateCorrectionBatch{return DB::transaction(function()use($entityId,$id,$actor,$reason){$this->lockEntity($entityId);$b=AccountingDateCorrectionBatch::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->find($id);if(!$b||!in_array($b->status,['draft','pending_review','approved'],true)||blank(trim($reason)))throw new FinancialDomainException('E-ACCT-BATCH-CANCEL','Batch tidak dapat dibatalkan.');$from=$b->status;$this->persist($b,$from,'cancelled',['active_group_guard'=>null,'cancelled_at'=>now(),'cancelled_by_user_id'=>$actor,'cancellation_reason'=>trim($reason)]);$this->decision($b,'cancelled',$actor,$reason);$this->audit($b,'accounting_date_correction_batch_cancelled',$actor);return$b->fresh('approvals');},3);}
    public function executionReadiness(AccountingDateCorrectionBatch $batch):array{$blockers=array_values(array_unique(array_column($batch->batch_blockers??[],'code')));return['execution_ready'=>$blockers===[],'blockers'=>$blockers];}

    /**
     * Revalidates the approved aggregate while holding the same locks used by
     * lifecycle decisions. This is deliberately a read-only execution gate;
     * it cannot post a subset of the correlation group.
     *
     * @return array{execution_ready:false,blockers:array<int,string>}
     */
    public function revalidateForExecution(string $entityId,string $id):array
    {
        return DB::transaction(function()use($entityId,$id):array{
            $this->lockEntity($entityId);
            $batch=$this->locked($entityId,$id,'approved');
            $this->assertCurrent($batch);

            return $this->executionReadiness($batch);
        },3);
    }

    private function evaluateLocked(string$entityId,string$correlationId,string$target,int$expectedMemberCount):array
    {
        // A correction draft deliberately inherits the source correlation ID so
        // its lineage remains traceable.  It is not, however, an authoritative
        // member of the already-posted bank batch.  Counting every non-cancelled
        // transaction therefore makes staging self-invalidating as soon as the
        // first reversal/replacement draft is created.  The governed membership
        // contract is the set of original posted transactions only.
        $members=FinancialTransaction::query()->where('accounting_entity_id',$entityId)->where('correlation_id',$correlationId)->where('status','posted')->with(['type','splits'=>fn($q)=>$q->orderBy('id'),'counterparty'])->orderBy('id')->lockForUpdate()->get();
        if($members->isEmpty())throw new FinancialDomainException('E-ACCT-BATCH-EMPTY','Tidak ada anggota authoritative pada correlation group.');
        if($members->count()!==$expectedMemberCount)throw new FinancialDomainException('E-ACCT-BATCH-MEMBERSHIP',"Correlation group berisi {$members->count()} anggota; snapshot governance mensyaratkan tepat {$expectedMemberCount} anggota.");
        $items=[];$net='0.00';$batchBlockers=[];
        foreach($members as$tx){$journals=Journal::query()->where('transaction_id',$tx->id)->where('journal_status','posted')->orderBy('id')->lockForUpdate()->get();$journal=$journals->count()===1?$journals->first():null;$evidence=AttachmentLink::query()->where('accounting_entity_id',$entityId)->where('target_type','transaction')->where('target_id',$tx->id)->where('status','active')->orderBy('id')->get(['id','attachment_id','evidence_type','status'])->toArray();$recon=Reconciliation::query()->where('accounting_entity_id',$entityId)->where('financial_account_id',$tx->primary_financial_account_id)->orderBy('id')->get(['id','status'])->toArray();try{$assessment=$this->evaluator->evaluate($tx->id,$target);}catch(\Throwable$e){$assessment=['status'=>'blocked','blockers'=>[['code'=>'CANDIDATE_EVALUATION_FAILED','message'=>'Evaluator canonical tidak dapat menyelesaikan evaluasi.']]];}
            // Once a governed request exists, its latest immutable evaluation is
            // authoritative for scoped policy exceptions. The raw evaluator is
            // still retained in the snapshot, but must not reintroduce master
            // blockers already resolved and fingerprinted by that request.
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->where('original_transaction_id',$tx->id)->whereDate('target_accounting_date',$target)->whereIn('status',['blocked','pending_approval','governance_approved'])->with(['evaluations'=>fn($q)=>$q->orderByDesc('evaluated_at')->orderByDesc('id')])->lockForUpdate()->first();
            if(!$request){$assessment['blockers'][]=['code'=>'CORRECTION_REQUEST_INSTANCE_MISSING','message'=>'Setiap anggota batch wajib mempunyai Correction Request resmi.'];}
            elseif($request->evaluations->isNotEmpty()){$latestEvaluation=$request->evaluations->sortByDesc('evaluated_at')->first();$assessment['request_id']=$request->id;$assessment['request_status']=$request->status;$assessment['blockers']=$latestEvaluation->evaluation_snapshot['blocking_reasons']??[];}
            // The aggregate itself resolves the bootstrap blockers below. They
            // must not make its own immutable snapshot change merely because
            // the batch moved from absent -> draft -> approved.
            $bootstrap=['BANK_BATCH_INSTANCE_MISSING','BANK_BATCH_NOT_APPROVED','BANK_BATCH_NOT_EXECUTION_READY','BATCH_EXECUTOR_UNAVAILABLE','BANK_BATCH_GROUP_WORKFLOW_REQUIRED','ACTIVE_CORRECTION_REQUEST_EXISTS','CANONICAL_PLAN_NOT_EXECUTION_READY'];
            $assessment['blockers']=array_values(array_filter($assessment['blockers']??[],fn(array$row):bool=>!in_array($row['code']??'',$bootstrap,true)));
            if(isset($assessment['facts']['bank_batch']['workflow_state']))unset($assessment['facts']['bank_batch']['workflow_state']);
            foreach($assessment['blockers'] as$blocker)$batchBlockers[]=['code'=>$blocker['code'],'transaction_id'=>$tx->id];
            $signed=$tx->type?->code==='RCV'?DecimalAmount::normalize($tx->gross_amount):($tx->type?->code==='PAY'?DecimalAmount::negate($tx->gross_amount):'0.00');$net=DecimalAmount::add($net,$signed);$snapshot=['transaction_id'=>$tx->id,'status'=>$tx->status,'type'=>$tx->type?->code,'amount'=>DecimalAmount::normalize($tx->gross_amount),'accounting_date'=>$tx->accounting_date?->toDateString(),'business_date'=>$tx->business_date?->toDateString(),'counterparty'=>['id'=>$tx->counterparty_id,'status'=>$tx->counterparty?->status??'missing'],'financial_account_id'=>$tx->primary_financial_account_id,'splits'=>$tx->splits->map(fn($s)=>['id'=>$s->id,'account_id'=>$s->account_id,'fund_id'=>$s->fund_id,'program_id'=>$s->program_id,'amount'=>DecimalAmount::normalize($s->split_amount)])->all(),'source_journal'=>$journal?['id'=>$journal->id,'status'=>$journal->journal_status,'posting_sequence'=>$journal->posting_sequence]:null,'posted_journal_count'=>$journals->count(),'evidence'=>$evidence,'reconciliations'=>$recon,'candidate_assessment'=>$assessment];$items[]=['transaction_id'=>$tx->id,'source_journal_id'=>$journal?->id,'snapshot'=>$snapshot,'fingerprint'=>$this->hash($snapshot),'blockers'=>$assessment['blockers']??[]];}
        if($members->pluck('accounting_entity_id')->unique()->count()!==1)$batchBlockers[]=['code'=>'BATCH_ENTITY_MISMATCH'];
        if($members->pluck('status')->contains(fn($s)=>$s!=='posted'))$batchBlockers[]=['code'=>'BATCH_MEMBER_NOT_POSTED'];
        $snapshot=['accounting_entity_id'=>$entityId,'correlation_id'=>$correlationId,'target_accounting_date'=>$target,'expected_member_count'=>$expectedMemberCount,'member_count'=>$members->count(),'member_fingerprints'=>collect($items)->sortBy('transaction_id')->map(fn($x)=>['transaction_id'=>$x['transaction_id'],'fingerprint'=>$x['fingerprint']])->values()->all(),'net_effect'=>$net,'batch_blockers'=>$batchBlockers];return['snapshot'=>$snapshot,'fingerprint'=>$this->hash($snapshot),'items'=>$items,'batch_blockers'=>$batchBlockers,'net_effect'=>$net];
    }
    private function assertCurrent(AccountingDateCorrectionBatch$b):void
    {
        $expected=(int)($b->batch_snapshot['expected_member_count']??0);
        if($expected<1)throw new FinancialDomainException('E-ACCT-BATCH-DRIFT','Snapshot batch tidak memuat kontrak jumlah anggota yang sah.');
        $e=$this->evaluateLocked($b->accounting_entity_id,$b->correlation_id,$b->target_accounting_date->toDateString(),$expected);
        if(hash_equals($b->snapshot_fingerprint,$e['fingerprint']))return;

        // Request evaluations are append-only and may be re-authorized after
        // the batch approval. Their diagnostic candidate_assessment is not a
        // financial fact of the bank group. Compare every immutable financial
        // and evidence field, membership, target, and net effect while letting
        // the current approved request/pair gate validate its own fingerprint.
        $stored=$b->items()->lockForUpdate()->get()->keyBy('transaction_id');
        if($stored->count()!==count($e['items'])||($e['batch_blockers']??[])!==[])throw new FinancialDomainException('E-ACCT-BATCH-DRIFT','Anggota atau fakta batch berubah; evaluasi lama tidak berlaku.');
        foreach($e['items']as$current){
            $prior=$stored->get($current['transaction_id']);
            if(!$prior||!hash_equals($this->hash($this->stableItemSnapshot($prior->item_snapshot)),$this->hash($this->stableItemSnapshot($current['snapshot']))))throw new FinancialDomainException('E-ACCT-BATCH-DRIFT','Anggota atau fakta batch berubah; evaluasi lama tidak berlaku.');
        }
        $snapshot=$b->batch_snapshot;
        if(($snapshot['member_count']??null)!==count($e['items'])||($snapshot['target_accounting_date']??null)!==$b->target_accounting_date->toDateString()||DecimalAmount::normalize($snapshot['net_effect']??'0')!==DecimalAmount::normalize($e['net_effect']))throw new FinancialDomainException('E-ACCT-BATCH-DRIFT','Anggota atau fakta batch berubah; evaluasi lama tidak berlaku.');
    }

    private function stableItemSnapshot(array$snapshot):array
    {
        unset($snapshot['candidate_assessment']);
        return$snapshot;
    }
    private function transition(string$entityId,string$id,string$from,string$to,string$decision,int$actor,?string$note):AccountingDateCorrectionBatch{return DB::transaction(function()use($entityId,$id,$from,$to,$decision,$actor,$note){$this->lockEntity($entityId);$b=$this->locked($entityId,$id,$from);if($decision==='submitted'&&(int)$b->created_by_user_id!==$actor)throw new FinancialDomainException('E-ACCT-BATCH-MAKER','Hanya maker dapat submit.');$this->assertCurrent($b);$this->persist($b,$from,$to,['submitted_at'=>now()]);$this->decision($b,$decision,$actor,$note);$this->audit($b,'accounting_date_correction_batch_'.$decision,$actor);return$b->fresh(['items','approvals']);},3);}
    private function persist($b,$from,$to,$values):void{$allowed=['draft'=>['pending_review','cancelled'],'pending_review'=>['approved','rejected','cancelled'],'approved'=>['cancelled']];if(!in_array($to,$allowed[$from]??[],true))throw new FinancialDomainException('E-ACCT-BATCH-STATE','Transisi batch tidak sah.');$n=DB::table($b->getTable())->where('id',$b->id)->where('status',$from)->update(array_merge($values,['status'=>$to,'updated_at'=>now()]));if($n!==1)throw new FinancialDomainException('E-ACCT-BATCH-RACE','Batch berubah saat transisi.');$b->refresh();}
    private function decision($b,$d,$a,$n):void{DB::table((new AccountingDateCorrectionBatchApproval)->getTable())->insert(['id'=>(string)Str::uuid(),'batch_id'=>$b->id,'decision'=>$d,'batch_snapshot'=>json_encode($b->batch_snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'snapshot_fingerprint'=>$b->snapshot_fingerprint,'note'=>$n,'actor_user_id'=>$a,'decided_at'=>now(),'created_at'=>now()]);}
    private function locked($e,$id,$s){$b=AccountingDateCorrectionBatch::query()->where('accounting_entity_id',$e)->lockForUpdate()->find($id);if(!$b||$b->status!==$s)throw new FinancialDomainException('E-ACCT-BATCH-STATE','Status atau Entity batch tidak sesuai.');return$b;}
    private function lockEntity($e):void{if(!DB::table('financial_v2_accounting_entities')->where('id',$e)->where('status','active')->lockForUpdate()->exists())throw new FinancialDomainException('E-ACCT-BATCH-ENTITY','Entity aktif tidak tersedia.');}
    private function decisionCodes($x):array{return array_column($x,'code');}private function audit($b,$event,$actor):void{$this->audit->record($b->accounting_entity_id,$event,'accounting_date_correction_batch',$b->id,$b->correlation_id,$actor,null,['status'=>$b->status,'fingerprint'=>$b->snapshot_fingerprint,'execution_ready'=>false]);}private function hash(array$x):string{return hash('sha256',json_encode(Arr::sortRecursive($x),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));}private function assertUuid($x,$n):void{if(!Str::isUuid($x))throw new FinancialDomainException('E-ACCT-BATCH-UUID',"{$n} harus UUID.");}
}
