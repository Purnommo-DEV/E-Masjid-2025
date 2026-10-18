<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountingDateCorrectionBatchItemExecutionPair extends FinancialV2Model
{
    public $timestamps=false;
    protected $table='financial_v2_acct_date_corr_batch_item_pairs';
    protected $casts=['link_snapshot'=>'array','created_at'=>'datetime'];
    protected static function booted():void
    {
        static::creating(fn()=>throw new DomainException('Lineage batch item hanya dapat dibuat workflow canonical.'));
        static::updating(fn()=>throw new DomainException('Lineage batch item dan execution pair immutable.'));
        static::deleting(fn()=>throw new DomainException('Lineage batch item disimpan permanen.'));
    }
    public function batch():BelongsTo{return$this->belongsTo(AccountingDateCorrectionBatch::class,'batch_id');}
    public function item():BelongsTo{return$this->belongsTo(AccountingDateCorrectionBatchItem::class,'batch_item_id');}
    public function correctionRequest():BelongsTo{return$this->belongsTo(AccountingDateCorrectionRequest::class,'correction_request_id');}
    public function executionPair():BelongsTo{return$this->belongsTo(AccountingDateCorrectionExecutionPair::class,'execution_pair_id');}
}
