<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionStateGuard;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class AccountingDateCorrectionRequest extends FinancialV2Model
{
    protected $table = 'financial_v2_accounting_date_correction_requests';

    protected $casts = [
        'original_accounting_date' => 'date', 'target_accounting_date' => 'date',
        'preview_snapshot' => 'array', 'blocking_reasons' => 'array',
        'requested_at' => 'datetime', 'approved_at' => 'datetime',
        'cancelled_at' => 'datetime', 'execution_started_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $model): void {
            $allowed = ['status', 'active_transaction_guard', 'approved_at', 'cancelled_at', 'execution_started_at', 'completed_at', 'updated_at'];
            if (array_diff(array_keys($model->getDirty()), $allowed) !== []) {
                throw new DomainException('Fakta permintaan koreksi tanggal akuntansi bersifat append-only.');
            }
            if ($model->isDirty('status')) {
                AccountingDateCorrectionStateGuard::assertTransition((string) $model->getOriginal('status'), (string) $model->status);
            }
            if ($model->isDirty('active_transaction_guard')) {
                $terminal = in_array((string) $model->status, ['rejected', 'cancelled', 'completed', 'failed'], true);
                if (! $terminal || $model->active_transaction_guard !== null || $model->getOriginal('active_transaction_guard') !== $model->original_transaction_id) {
                    throw new DomainException('Guard transaksi aktif hanya dapat dilepas saat request memasuki status terminal.');
                }
            }
        });
        static::deleting(fn () => throw new DomainException('Permintaan koreksi tanggal akuntansi disimpan permanen untuk audit.'));
    }

    public function originalTransaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class, 'original_transaction_id'); }
    public function originalJournal(): BelongsTo { return $this->belongsTo(Journal::class, 'original_journal_id'); }
    public function evidence(): BelongsTo { return $this->belongsTo(Attachment::class, 'evidence_attachment_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by_user_id'); }
    public function approval(): HasOne { return $this->hasOne(AccountingDateCorrectionApproval::class, 'correction_request_id'); }
    public function evaluations(): HasMany { return $this->hasMany(AccountingDateCorrectionEvaluation::class, 'correction_request_id')->orderBy('evaluated_at'); }
    public function lineage(): HasOne { return $this->hasOne(AccountingDateCorrectionLineage::class, 'correction_request_id'); }
    public function replacementRealizationRequest(): HasOne { return $this->hasOne(ReplacementRealizationRequest::class, 'correction_request_id'); }
    public function executionPair(): HasOne { return $this->hasOne(AccountingDateCorrectionExecutionPair::class, 'correction_request_id'); }
}
