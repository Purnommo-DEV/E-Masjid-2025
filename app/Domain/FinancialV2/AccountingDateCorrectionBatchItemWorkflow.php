<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionBatch;
use App\Models\FinancialV2\AccountingDateCorrectionBatchItem;
use App\Models\FinancialV2\AccountingDateCorrectionBatchItemExecutionPair;
use App\Models\FinancialV2\AccountingDateCorrectionExecutionPair;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Immutable aggregate linkage. This service has no per-item execution API. */
final class AccountingDateCorrectionBatchItemWorkflow
{
    public function __construct(private readonly FinancialV2TransactionRunner$transactions,private readonly AccountingDateCorrectionBatchWorkflowService$batches,private readonly AuditTrailService$audit){}

    public function link(string$entityId,string$batchId,string$itemId,string$requestId,string$pairId,int$actor):AccountingDateCorrectionBatchItemExecutionPair
    {
        return$this->transactions->run(function()use($entityId,$batchId,$itemId,$requestId,$pairId,$actor):AccountingDateCorrectionBatchItemExecutionPair{
            $gate=$this->batches->revalidateForExecution($entityId,$batchId);
            if(($gate['blockers']??[])!==[])throw new FinancialDomainException('E-BATCH-PAIR-BLOCKED','Batch masih mempunyai blocker canonical.',['blockers'=>$gate['blockers']]);
            $batch=AccountingDateCorrectionBatch::query()->where('accounting_entity_id',$entityId)->where('status','approved')->lockForUpdate()->findOrFail($batchId);
            $item=AccountingDateCorrectionBatchItem::query()->where('batch_id',$batch->id)->lockForUpdate()->findOrFail($itemId);
            $request=AccountingDateCorrectionRequest::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($requestId);
            $pair=AccountingDateCorrectionExecutionPair::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($pairId);
            if(!in_array($pair->status,['staged','execution_ready'],true))throw new FinancialDomainException('E-BATCH-PAIR-NOT-STAGED','Execution pair item belum staged.');
            if($request->id!==$pair->correction_request_id||$request->original_transaction_id!==$item->transaction_id||$pair->original_transaction_id!==$item->transaction_id)throw new FinancialDomainException('E-BATCH-PAIR-LINEAGE','Batch item, correction request, dan execution pair tidak mempunyai sumber yang sama.');
            if(!$request->target_accounting_date->isSameDay($batch->target_accounting_date)||!$pair->target_accounting_date->isSameDay($batch->target_accounting_date))throw new FinancialDomainException('E-BATCH-PAIR-TARGET','Target accounting date item tidak sama dengan target batch.');
            $snapshot=['batch_id'=>$batch->id,'batch_fingerprint'=>$batch->snapshot_fingerprint,'batch_item_id'=>$item->id,'item_fingerprint'=>$item->item_fingerprint,'transaction_id'=>$item->transaction_id,'correction_request_id'=>$request->id,'execution_pair_id'=>$pair->id,'pair_source_fingerprint'=>$pair->source_fingerprint,'pair_validation_fingerprint'=>$pair->validation_fingerprint,'target_accounting_date'=>$batch->target_accounting_date->toDateString()];
            $hash=$this->hash($snapshot);
            $existing=AccountingDateCorrectionBatchItemExecutionPair::query()->where('batch_item_id',$item->id)->first();
            if($existing){
                // Reauthorization refreshes validation facts on the same
                // execution pair. The immutable linkage snapshot remains the
                // historical record created at first staging; identity must
                // remain identical, while its old fingerprint is never
                // rewritten to impersonate a newly-created link.
                if($existing->batch_id!==$batch->id||$existing->batch_item_id!==$item->id||$existing->correction_request_id!==$request->id||$existing->execution_pair_id!==$pair->id)throw new FinancialDomainException('E-BATCH-PAIR-WRITE-ONCE','Batch item telah terhubung ke execution pair berbeda.');
                if(hash_equals($existing->link_fingerprint,$hash))return$existing;
                $this->audit->record($entityId,'accounting_date_correction_batch_item_pair_reauthorized','accounting_date_correction_batch_item_execution_pair',$existing->id,$batch->correlation_id,$actor,null,['batch_id'=>$batch->id,'item_id'=>$item->id,'execution_pair_id'=>$pair->id,'current_pair_validation_fingerprint'=>$pair->validation_fingerprint,'immutable_link_fingerprint'=>$existing->link_fingerprint,'financial_facts_written'=>false]);
                return$existing;
            }
            $id=(string)Str::uuid();DB::table((new AccountingDateCorrectionBatchItemExecutionPair)->getTable())->insert(['id'=>$id,'accounting_entity_id'=>$entityId,'batch_id'=>$batch->id,'batch_item_id'=>$item->id,'correction_request_id'=>$request->id,'execution_pair_id'=>$pair->id,'link_snapshot'=>json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),'link_fingerprint'=>$hash,'created_by_user_id'=>$actor,'created_at'=>now()]);
            $link=AccountingDateCorrectionBatchItemExecutionPair::findOrFail($id);$this->audit->record($entityId,'accounting_date_correction_batch_item_pair_linked','accounting_date_correction_batch_item_execution_pair',$link->id,$batch->correlation_id,$actor,null,['batch_id'=>$batch->id,'item_id'=>$item->id,'execution_pair_id'=>$pair->id,'execution_ready'=>false]);return$link;
        });
    }

    public function completeness(string$entityId,string$batchId):array
    {
        return$this->transactions->run(function()use($entityId,$batchId):array{
            $gate=$this->batches->revalidateForExecution($entityId,$batchId);$batch=AccountingDateCorrectionBatch::query()->where('accounting_entity_id',$entityId)->lockForUpdate()->findOrFail($batchId);$items=$batch->items()->lockForUpdate()->get();$links=AccountingDateCorrectionBatchItemExecutionPair::query()->with('executionPair')->where('batch_id',$batch->id)->lockForUpdate()->get();$valid=($gate['blockers']??[])===[]&&$links->count()===$items->count()&&$links->every(fn($link)=>$link->executionPair&&$link->executionPair->status==='execution_ready');return['complete'=>$valid,'execution_ready'=>false,'item_count'=>$items->count(),'linked_count'=>$links->count(),'blockers'=>$valid?[]:array_values(array_unique([...($gate['blockers']??[]),'BATCH_PAIR_INCOMPLETE']))];
        });
    }

    private function hash(array$value):string{ksort($value);return hash('sha256',json_encode($value,JSON_THROW_ON_ERROR));}
}
