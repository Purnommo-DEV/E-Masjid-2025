<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountingDateCorrectionApproval extends FinancialV2Model
{
    public $timestamps = false;
    protected $table = 'financial_v2_accounting_date_correction_approvals';
    protected $casts = ['approval_snapshot' => 'array', 'decided_at' => 'datetime', 'created_at' => 'datetime'];
    protected static function booted(): void
    {
        static::updating(fn () => throw new DomainException('Keputusan koreksi tanggal akuntansi bersifat append-only.'));
        static::deleting(fn () => throw new DomainException('Keputusan koreksi tanggal akuntansi disimpan permanen.'));
    }
    public function request(): BelongsTo { return $this->belongsTo(AccountingDateCorrectionRequest::class, 'correction_request_id'); }
}
