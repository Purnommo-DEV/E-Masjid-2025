<?php

namespace App\Models\FinancialV2;

use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TransactionDateCorrection extends FinancialV2Model
{
    public $timestamps = false;

    protected $table = 'financial_v2_transaction_date_corrections';

    protected $casts = [
        'original_business_date' => 'date',
        'corrected_business_date' => 'date',
        'accounting_date' => 'date',
        'corrected_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new DomainException('Koreksi tanggal transaksi bersifat append-only.'));
        static::deleting(fn () => throw new DomainException('Koreksi tanggal transaksi tidak dapat dihapus.'));
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'evidence_attachment_id');
    }
}
