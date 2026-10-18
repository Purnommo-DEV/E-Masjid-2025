<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\Reconciliation;
use App\Models\FinancialV2\ReconciliationItem;
use App\Models\FinancialV2\TransactionDateCorrection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class PostedTransactionDateCorrectionService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly FinancialTransactionConfigurationResolver $configurationResolver,
    ) {}

    /** @return array<string, mixed> */
    public function preview(FinancialTransaction $transaction, array $input): array
    {
        $validated = $this->validateRequest($transaction, $input, false);
        $journal = Journal::query()
            ->where('transaction_id', $transaction->id)
            ->where('journal_status', 'posted')
            ->first();
        if (! $journal) {
            throw new FinancialDomainException('E-DATE-CORRECTION-JOURNAL', 'Jurnal posted transaksi tidak ditemukan. Koreksi dihentikan agar histori tidak ambigu.');
        }

        $bankContext = $validated['isBankMutation'] ? $this->bankMutationContext($transaction) : null;

        return [
            'transaction_id' => $transaction->id,
            'status' => $transaction->status,
            'old_business_date' => $transaction->effective_business_date->format('d/m/Y'),
            'new_business_date' => $validated['businessDate']->format('d/m/Y'),
            'old_accounting_date' => $transaction->accounting_date->format('d/m/Y'),
            'new_accounting_date' => $validated['accountingDate']->format('d/m/Y'),
            'amount' => number_format((float) $transaction->gross_amount, 2, ',', '.'),
            'accounting_period' => $transaction->accounting_date->format('m/Y'),
            'journal_id' => $journal->id,
            'journal_posted_at' => $journal->posted_at?->format('d/m/Y H:i'),
            'ledger_impact' => 'Tidak ada. Accounting date, Journal, JournalLine, Ledger, saldo, dan periode laporan tidak berubah.',
            'balance_validation' => 'Tidak diperlukan untuk koreksi metadata tanggal aktual karena tidak ada fakta akuntansi atau urutan ledger yang berubah.',
            'reason' => $input['reason'],
            'evidence_attachment_id' => $input['attachment_id'],
            'is_bank_mutation' => $validated['isBankMutation'],
            'bank_mutation' => $bankContext,
        ];
    }

    public function correct(FinancialTransaction $transaction, array $input, ?int $actorUserId): TransactionDateCorrection
    {
        return DB::transaction(function () use ($transaction, $input, $actorUserId): TransactionDateCorrection {
            $locked = FinancialTransaction::query()->whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $requestedBusinessDate = CarbonImmutable::parse($input['business_date'])->startOfDay();
            $requestedAccountingDate = CarbonImmutable::parse($input['accounting_date'])->startOfDay();
            $fingerprint = hash('sha256', implode('|', [
                $locked->id,
                $requestedBusinessDate->toDateString(),
                $requestedAccountingDate->toDateString(),
                trim($input['reason']),
                $input['attachment_id'],
            ]));

            $existing = TransactionDateCorrection::query()->where('idempotency_key', $input['idempotency_key'])->first();
            if ($existing) {
                if (! hash_equals($existing->request_fingerprint, $fingerprint)) {
                    throw new FinancialDomainException('E-DATE-CORRECTION-IDEMPOTENCY', 'Kunci permintaan sudah digunakan untuk isi koreksi yang berbeda.');
                }

                return $existing;
            }

            $validated = $this->validateRequest($locked, $input, true);
            $bankContext = $validated['isBankMutation'] ? $this->bankMutationContext($locked) : null;

            $previousDate = $locked->effective_business_date;
            $correction = TransactionDateCorrection::create([
                'accounting_entity_id' => $locked->accounting_entity_id,
                'transaction_id' => $locked->id,
                'correction_no' => ((int) TransactionDateCorrection::query()->where('transaction_id', $locked->id)->max('correction_no')) + 1,
                'original_business_date' => $previousDate->toDateString(),
                'corrected_business_date' => $validated['businessDate']->toDateString(),
                'accounting_date' => $locked->accounting_date->toDateString(),
                'reason' => trim($input['reason']),
                'evidence_attachment_id' => $input['attachment_id'],
                'idempotency_key' => $input['idempotency_key'],
                'request_fingerprint' => $fingerprint,
                'corrected_by_user_id' => $actorUserId,
                'corrected_at' => now(),
                'created_at' => now(),
            ]);

            $this->auditTrail->record(
                $locked->accounting_entity_id,
                'posted_transaction_business_date_corrected',
                'transaction_date_correction',
                $correction->id,
                $input['idempotency_key'],
                $actorUserId,
                ['transaction_id' => $locked->id, 'business_date' => $previousDate->toDateString(), 'accounting_date' => $locked->accounting_date->toDateString()],
                [
                    'transaction_id' => $locked->id,
                    'business_date' => $validated['businessDate']->toDateString(),
                    'accounting_date' => $locked->accounting_date->toDateString(),
                    'evidence_attachment_id' => $input['attachment_id'],
                    'bank_mutation_context' => $bankContext,
                ],
            );

            return $correction;
        }, 3);
    }

    /** @return array{businessDate: CarbonImmutable, accountingDate: CarbonImmutable} */
    private function validateRequest(FinancialTransaction $transaction, array $input, bool $requireBatchConfirmation): array
    {
        $transaction->loadMissing(['type', 'category', 'splits', 'latestDateCorrection']);
        if ($transaction->status !== 'posted') {
            throw new FinancialDomainException('E-DATE-CORRECTION-STATUS', 'Koreksi tanggal ini hanya berlaku untuk transaksi berstatus posted.');
        }
        $isBankMutation = array_key_exists((string) $transaction->category?->code, BankMutationService::CATEGORY_CODES);
        if (! Journal::query()->where('transaction_id', $transaction->id)->where('journal_status', 'posted')->exists()) {
            throw new FinancialDomainException('E-DATE-CORRECTION-JOURNAL', 'Jurnal posted transaksi tidak ditemukan. Koreksi dihentikan agar histori tidak ambigu.');
        }

        $businessDate = CarbonImmutable::parse($input['business_date'])->startOfDay();
        $accountingDate = CarbonImmutable::parse($input['accounting_date'])->startOfDay();
        if (! $accountingDate->isSameDay($transaction->accounting_date)) {
            throw new FinancialDomainException(
                'E-ACCOUNTING-DATE-CORRECTION-UNSUPPORTED',
                'Tanggal akuntansi transaksi posted belum dapat diubah. Perubahan memerlukan alur reversal dan transaksi pengganti yang menjaga period, Journal, Ledger, saldo historis, serta keterlacakan transaksi asal.'
            );
        }
        if ($businessDate->isSameDay($transaction->effective_business_date)) {
            throw new FinancialDomainException('E-DATE-CORRECTION-NO-CHANGE', 'Tanggal aktual baru sama dengan tanggal aktual yang sedang berlaku.');
        }
        if (mb_strlen(trim((string) ($input['reason'] ?? ''))) < 10) {
            throw new FinancialDomainException('E-DATE-CORRECTION-REASON', 'Alasan koreksi wajib diisi minimal 10 karakter.');
        }

        $evidence = AttachmentLink::query()
            ->where('financial_v2_attachment_links.accounting_entity_id', $transaction->accounting_entity_id)
            ->where('financial_v2_attachment_links.target_type', 'transaction')
            ->where('financial_v2_attachment_links.target_id', $transaction->id)
            ->where('financial_v2_attachment_links.attachment_id', $input['attachment_id'])
            ->where('financial_v2_attachment_links.status', 'active')
            ->select('financial_v2_attachment_links.evidence_type')
            ->first();
        $attachmentIsActive = $evidence && Attachment::query()->whereKey($input['attachment_id'])->where('status', 'active')->exists();
        if (! $evidence || ! $attachmentIsActive) {
            throw new FinancialDomainException('E-DATE-CORRECTION-EVIDENCE', 'Bukti yang dipilih bukan lampiran aktif milik transaksi ini.');
        }
        if ($isBankMutation) {
            if ($evidence->evidence_type !== 'statement') {
                throw new FinancialDomainException('E-DATE-CORRECTION-BANK-STATEMENT', 'Koreksi tanggal Mutasi Bank wajib menggunakan lampiran rekening koran aktif (statement).');
            }
            $this->validateBankMutationScope($transaction);
            $batch = $this->bankBatchContext($transaction);
            if ($requireBatchConfirmation && $batch['other_count'] > 0 && ! filter_var($input['bank_batch_confirmed'] ?? false, FILTER_VALIDATE_BOOL)) {
                throw new FinancialDomainException('E-DATE-CORRECTION-BANK-BATCH-CONFIRMATION', 'Konfirmasi koreksi satu baris Mutasi Bank wajib diberikan karena baris lain dalam batch tidak ikut berubah.');
            }
        }

        return compact('businessDate', 'accountingDate', 'isBankMutation');
    }

    private function validateBankMutationScope(FinancialTransaction $transaction): void
    {
        $expectedType = $transaction->category?->code === 'BANK_INTEREST' ? 'RCV' : 'PAY';
        if ($transaction->type?->code !== $expectedType) {
            throw new FinancialDomainException('E-DATE-CORRECTION-BANK-TYPE', 'Jenis transaksi Mutasi Bank tidak sesuai dengan kategori canonical-nya.');
        }

        try {
            $policy = $this->configurationResolver->resolveTransaction($transaction)->bankMutationPolicy;
        } catch (FinancialPostingException $exception) {
            throw new FinancialDomainException('E-DATE-CORRECTION-BANK-POLICY', 'Policy Mutasi Bank tidak dapat diverifikasi: '.$exception->getMessage());
        }
        $fundIds = $transaction->splits->pluck('fund_id')->filter()->unique()->values();
        if (! $policy
            || $transaction->policy_version_ref !== $policy->id
            || $policy->accounting_entity_id !== $transaction->accounting_entity_id
            || $policy->financial_account_id !== $transaction->primary_financial_account_id
            || $policy->category_id !== $transaction->category_id
            || $policy->transaction_type_id !== $transaction->transaction_type_id
            || $fundIds->count() !== 1
            || $fundIds->first() !== $policy->fund_id) {
            throw new FinancialDomainException('E-DATE-CORRECTION-BANK-SCOPE', 'Entity, rekening, Fund, kategori, tipe transaksi, atau versi policy Mutasi Bank tidak konsisten.');
        }
    }

    /** @return array<string, mixed> */
    private function bankMutationContext(FinancialTransaction $transaction): array
    {
        return [
            'batch' => $this->bankBatchContext($transaction),
            'reconciliations' => $this->reconciliationContext($transaction),
            'statement_note' => 'Koreksi ini hanya memperbaiki tanggal debit/kredit aktual berdasarkan rekening koran; periode layanan tidak berubah.',
        ];
    }

    /** @return array{is_batch:bool,other_count:int,other_transaction_ids:array<int,string>} */
    private function bankBatchContext(FinancialTransaction $transaction): array
    {
        $otherIds = FinancialTransaction::query()
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->where('correlation_id', $transaction->correlation_id)
            ->where('id', '!=', $transaction->id)
            ->where('status', '!=', 'cancelled')
            ->whereHas('category', fn ($query) => $query->whereIn('code', array_keys(BankMutationService::CATEGORY_CODES)))
            ->pluck('id')
            ->values()
            ->all();

        return ['is_batch' => $otherIds !== [], 'other_count' => count($otherIds), 'other_transaction_ids' => $otherIds];
    }

    /** @return array<string, mixed> */
    private function reconciliationContext(FinancialTransaction $transaction): array
    {
        $candidates = Reconciliation::query()
            ->with('period')
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->where('financial_account_id', $transaction->primary_financial_account_id)
            ->whereHas('period', fn ($query) => $query
                ->where('start_date', '<=', $transaction->accounting_date)
                ->where('end_date', '>=', $transaction->accounting_date))
            ->get();
        $matchedIds = ReconciliationItem::query()
            ->whereIn('reconciliation_id', $candidates->pluck('id'))
            ->where('external_reference', $transaction->source_reference)
            ->pluck('reconciliation_id')
            ->all();

        return [
            'match_basis' => $matchedIds !== [] ? 'source_reference' : 'financial_account_and_accounting_period_only',
            'relationship_certain' => $matchedIds !== [],
            'items' => $candidates->map(fn (Reconciliation $reconciliation): array => [
                'id' => $reconciliation->id,
                'period' => $reconciliation->period?->period_name,
                'status' => $reconciliation->status,
                'matched_by_source_reference' => in_array($reconciliation->id, $matchedIds, true),
                'completed_warning' => $reconciliation->status === 'completed'
                    ? 'Rekonsiliasi completed tetap immutable; koreksi tanggal aktual dicatat setelah rekonsiliasi tanpa mengubah snapshotnya.'
                    : null,
            ])->values()->all(),
        ];
    }
}
