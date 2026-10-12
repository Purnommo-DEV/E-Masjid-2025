<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\TransactionDateCorrection;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class PostedTransactionDateCorrectionService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @return array<string, mixed> */
    public function preview(FinancialTransaction $transaction, array $input): array
    {
        $validated = $this->validateRequest($transaction, $input);
        $journal = Journal::query()
            ->where('transaction_id', $transaction->id)
            ->where('journal_status', 'posted')
            ->first();
        if (! $journal) {
            throw new FinancialDomainException('E-DATE-CORRECTION-JOURNAL', 'Jurnal posted transaksi tidak ditemukan. Koreksi dihentikan agar histori tidak ambigu.');
        }

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

            $validated = $this->validateRequest($locked, $input);

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
                ['transaction_id' => $locked->id, 'business_date' => $validated['businessDate']->toDateString(), 'accounting_date' => $locked->accounting_date->toDateString(), 'evidence_attachment_id' => $input['attachment_id']],
            );

            return $correction;
        }, 3);
    }

    /** @return array{businessDate: CarbonImmutable, accountingDate: CarbonImmutable} */
    private function validateRequest(FinancialTransaction $transaction, array $input): array
    {
        $transaction->loadMissing(['category', 'latestDateCorrection']);
        if ($transaction->status !== 'posted') {
            throw new FinancialDomainException('E-DATE-CORRECTION-STATUS', 'Koreksi tanggal ini hanya berlaku untuk transaksi berstatus posted.');
        }
        if (array_key_exists((string) $transaction->category?->code, BankMutationService::CATEGORY_CODES)) {
            throw new FinancialDomainException('E-DATE-CORRECTION-BANK-MUTATION', 'Mutasi Bank tidak termasuk ruang lingkup fitur koreksi tanggal ini.');
        }
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
        if (! AttachmentLink::query()
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->where('target_type', 'transaction')
            ->where('target_id', $transaction->id)
            ->where('attachment_id', $input['attachment_id'])
            ->where('status', 'active')
            ->exists()) {
            throw new FinancialDomainException('E-DATE-CORRECTION-EVIDENCE', 'Bukti yang dipilih bukan lampiran aktif milik transaksi ini.');
        }

        return compact('businessDate', 'accountingDate');
    }
}
