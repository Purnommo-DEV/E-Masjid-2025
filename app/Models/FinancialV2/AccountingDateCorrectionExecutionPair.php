<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionExecutionPairStateGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class AccountingDateCorrectionExecutionPair extends FinancialV2Model
{
    protected $table='financial_v2_accounting_date_correction_execution_pairs';
    protected $casts=['target_accounting_date'=>'date','approved_plan_snapshot'=>'array','validation_snapshot'=>'array','blockers'=>'array','validated_at'=>'datetime','staged_at'=>'datetime','execution_ready_at'=>'datetime','execution_started_at'=>'datetime','completed_at'=>'datetime','invalidated_at'=>'datetime'];

    protected static function booted():void
    {
        static::creating(fn()=>throw new DomainException('Execution pair hanya dapat dibuat service canonical.'));
        static::updating(function(self$model):void{
            $allowed=['status','active_original_guard','validation_snapshot','validation_fingerprint','blockers','reversal_transaction_id','replacement_transaction_id','validated_by_user_id','validated_at','staged_at','execution_ready_at','execution_started_at','completed_at','invalidated_at','updated_at'];
            if(array_diff(array_keys($model->getDirty()),$allowed)!==[])throw new DomainException('Identitas dan snapshot approval execution pair immutable.');
            foreach(['reversal_transaction_id','replacement_transaction_id']as$field)if($model->isDirty($field)&&($model->getOriginal($field)!==null||$model->{$field}===null))throw new DomainException("{$field} bersifat write-once.");
            if($model->isDirty('active_original_guard')&&($model->status!=='invalidated'||$model->active_original_guard!==null))throw new DomainException('Active original guard hanya dapat dilepas saat pair diinvalidasi.');
            if($model->isDirty('status'))AccountingDateCorrectionExecutionPairStateGuard::assertTransition((string)$model->getOriginal('status'),(string)$model->status);
        });
        static::deleting(fn()=>throw new DomainException('Execution pair disimpan permanen untuk audit.'));
    }

    public function correctionRequest():BelongsTo{return$this->belongsTo(AccountingDateCorrectionRequest::class,'correction_request_id');}
    public function originalTransaction():BelongsTo{return$this->belongsTo(FinancialTransaction::class,'original_transaction_id');}
    public function originalJournal():BelongsTo{return$this->belongsTo(Journal::class,'original_journal_id');}
    public function reversalTransaction():BelongsTo{return$this->belongsTo(FinancialTransaction::class,'reversal_transaction_id');}
    public function replacementTransaction():BelongsTo{return$this->belongsTo(FinancialTransaction::class,'replacement_transaction_id');}
    public function batchLink():HasOne{return$this->hasOne(AccountingDateCorrectionBatchItemExecutionPair::class,'execution_pair_id');}
}
