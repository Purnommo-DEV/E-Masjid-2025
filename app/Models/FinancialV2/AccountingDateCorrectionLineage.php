<?php

namespace App\Models\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionLineageGuard;
use DomainException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AccountingDateCorrectionLineage extends FinancialV2Model
{
    protected $table = 'financial_v2_accounting_date_correction_lineages';
    protected static function booted(): void
    {
        static::creating(fn () => AccountingDateCorrectionLineageGuard::assertCreationWrite());
        static::updating(function (self $model): void {
            $immutable = ['id', 'correction_request_id', 'original_transaction_id', 'original_journal_id', 'original_realization_id', 'created_at'];
            if ($model->isDirty($immutable)) {
                throw new DomainException('Lineage original bersifat immutable.');
            }
            $writeOnce = ['reversal_transaction_id', 'replacement_transaction_id', 'reversal_journal_id', 'replacement_journal_id', 'replacement_realization_id'];
            foreach ($writeOnce as $field) {
                if ($model->isDirty($field) && ($model->getOriginal($field) !== null || $model->{$field} === null)) {
                    throw new DomainException("Field lineage {$field} hanya dapat diisi satu kali.");
                }
            }
            if ($model->isDirty($writeOnce)) {
                AccountingDateCorrectionLineageGuard::assertExecutionWrite();
            }
            $allowed = [...$writeOnce, 'updated_at'];
            if (array_diff(array_keys($model->getDirty()), $allowed) !== []) {
                throw new DomainException('Field lineage di luar kontrak eksekusi tidak dapat diubah.');
            }
        });
        static::deleting(fn () => throw new DomainException('Lineage koreksi tanggal akuntansi tidak dapat dihapus.'));
    }
    public function request(): BelongsTo { return $this->belongsTo(AccountingDateCorrectionRequest::class, 'correction_request_id'); }
    public function originalTransaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class, 'original_transaction_id'); }
    public function reversalTransaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class, 'reversal_transaction_id'); }
    public function replacementTransaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class, 'replacement_transaction_id'); }
    public function originalJournal(): BelongsTo { return $this->belongsTo(Journal::class, 'original_journal_id'); }
    public function reversalJournal(): BelongsTo { return $this->belongsTo(Journal::class, 'reversal_journal_id'); }
    public function replacementJournal(): BelongsTo { return $this->belongsTo(Journal::class, 'replacement_journal_id'); }
}
