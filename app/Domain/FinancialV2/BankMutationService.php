<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\FinancialTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BankMutationService
{
    public const CATEGORY_CODES = [
        'BANK_INTEREST' => 'Jasa Giro/Bunga',
        'BANK_WHT_PPH' => 'PPH',
        'BANK_ACCOUNT_FEE' => 'Biaya Administrasi Rekening',
        'BANK_CARD_FEE' => 'Biaya Administrasi Kartu / Bank Fee',
        'BANK_TRANSFER_FEE' => 'Biaya Transfer Bank',
    ];

    public function __construct(private readonly FinancialTransactionLifecycleService $lifecycle) {}

    /** @param array<string, mixed> $input */
    public function create(array $input, ?int $actorUserId = null): FinancialTransaction
    {
        return DB::transaction(function () use ($input, $actorUserId): FinancialTransaction {
            $context = $this->resolveContext($input);
            $transactionInput = $this->transactionInput($input, $context);
            $split = $this->splitInput($input, $context);

            return $context['type']->code === TransactionTypeCode::Receipt->value
                ? $this->lifecycle->createReceipt($transactionInput, [$split], $actorUserId)
                : $this->lifecycle->createPayment($transactionInput, [$split], $actorUserId);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $common
     * @param  array<int, array<string, mixed>>  $mutations
     * @return array{transactions:Collection<int,FinancialTransaction>,replayed:bool}
     */
    public function createBatch(array $common, array $mutations, string $batchId, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($common, $mutations, $batchId, $actorUserId): array {
            $references = collect($mutations)->pluck('source_reference')->map(fn ($reference): string => trim((string) $reference));
            if ($references->duplicates()->isNotEmpty()) {
                throw new FinancialDomainException('E-BANK-MUTATION-DUPLICATE', 'Source Reference harus unik pada setiap baris mutasi.');
            }
            $existing = $this->batchTransactions($common['accounting_entity_id'], $batchId, includeCancelled: true);
            if ($existing->isNotEmpty()) {
                $this->assertBatchReplayMatches($existing, $common, $mutations);

                return ['transactions' => $existing, 'replayed' => true];
            }

            if (FinancialTransaction::query()
                ->where('accounting_entity_id', $common['accounting_entity_id'])
                ->whereIn('source_reference', $references)
                ->exists()) {
                throw new FinancialDomainException('E-BANK-MUTATION-DUPLICATE', 'Source Reference sudah digunakan oleh transaksi lain.');
            }

            $transactions = collect();
            foreach ($mutations as $mutation) {
                $transactions->push($this->create($common + $mutation + ['correlation_id' => $batchId], $actorUserId));
            }

            return ['transactions' => $transactions, 'replayed' => false];
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $common
     * @param  array<int, array<string, mixed>>  $mutations
     * @return Collection<int,FinancialTransaction>
     */
    public function updateDraftBatch(string $entityId, string $batchId, array $common, array $mutations, ?int $actorUserId = null): Collection
    {
        return DB::transaction(function () use ($entityId, $batchId, $common, $mutations, $actorUserId): Collection {
            $references = collect($mutations)->pluck('source_reference')->map(fn ($reference): string => trim((string) $reference));
            if ($references->duplicates()->isNotEmpty()) {
                throw new FinancialDomainException('E-BANK-MUTATION-DUPLICATE', 'Source Reference harus unik pada setiap baris mutasi.');
            }
            $existing = $this->batchTransactions($entityId, $batchId);
            if ($existing->isEmpty() || $existing->contains(fn (FinancialTransaction $transaction): bool => $transaction->status !== 'draft')) {
                throw new FinancialDomainException('E-BANK-MUTATION-BATCH-STATE', 'Seluruh Mutasi Bank dalam batch harus berstatus draft agar dapat diubah.');
            }

            $existingById = $existing->keyBy('id');
            $retainedIds = collect();
            foreach ($mutations as $mutation) {
                $transactionId = $mutation['transaction_id'] ?? null;
                if ($transactionId) {
                    $transaction = $existingById->get($transactionId);
                    if (! $transaction) {
                        throw new FinancialDomainException('E-BANK-MUTATION-BATCH-SCOPE', 'Baris mutasi bukan bagian dari batch yang sedang diubah.');
                    }
                    if (FinancialTransaction::query()
                        ->where('accounting_entity_id', $entityId)
                        ->where('source_reference', trim($mutation['source_reference']))
                        ->where('id', '!=', $transaction->id)
                        ->exists()) {
                        throw new FinancialDomainException('E-BANK-MUTATION-DUPLICATE', 'Source Reference sudah digunakan oleh transaksi lain.');
                    }
                    $this->updateDraft($transaction->id, $common + $mutation, $actorUserId);
                    $retainedIds->push($transaction->id);

                    continue;
                }

                if (FinancialTransaction::query()->where('accounting_entity_id', $entityId)->where('source_reference', trim($mutation['source_reference']))->exists()) {
                    throw new FinancialDomainException('E-BANK-MUTATION-DUPLICATE', 'Source Reference sudah digunakan oleh transaksi lain.');
                }
                $created = $this->create($common + $mutation + ['correlation_id' => $batchId], $actorUserId);
                $retainedIds->push($created->id);
            }

            foreach ($existing->whereNotIn('id', $retainedIds) as $removed) {
                $this->lifecycle->cancel($removed->id, 'Baris dihapus saat batch Mutasi Bank diperbarui.', $actorUserId);
            }

            return $this->batchTransactions($entityId, $batchId);
        }, 3);
    }

    public function removeDraftBatch(string $entityId, string $batchId, ?int $actorUserId = null): int
    {
        return DB::transaction(function () use ($entityId, $batchId, $actorUserId): int {
            $transactions = $this->batchTransactions($entityId, $batchId);
            if ($transactions->isEmpty() || $transactions->contains(fn (FinancialTransaction $transaction): bool => $transaction->status !== 'draft')) {
                throw new FinancialDomainException('E-BANK-MUTATION-BATCH-STATE', 'Seluruh Mutasi Bank dalam batch harus berstatus draft agar dapat dihapus.');
            }
            foreach ($transactions as $transaction) {
                $this->lifecycle->cancel($transaction->id, 'Batch Mutasi Bank dihapus oleh operator.', $actorUserId);
            }

            return $transactions->count();
        }, 3);
    }

    /** @return Collection<int,FinancialTransaction> */
    public function batchTransactions(string $entityId, string $batchId, bool $includeCancelled = false): Collection
    {
        return FinancialTransaction::query()
            ->with(['type', 'category', 'primaryFinancialAccount', 'splits.fund'])
            ->where('accounting_entity_id', $entityId)
            ->where('correlation_id', $batchId)
            ->when(! $includeCancelled, fn ($query) => $query->where('status', '!=', 'cancelled'))
            ->orderBy('created_at')
            ->get()
            ->filter(fn (FinancialTransaction $transaction): bool => $this->isBankMutation($transaction))
            ->values();
    }

    /** @param array<string, mixed> $input */
    public function updateDraft(string $transactionId, array $input, ?int $actorUserId = null): FinancialTransaction
    {
        return DB::transaction(function () use ($transactionId, $input, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()->with(['type', 'splits'])->lockForUpdate()->findOrFail($transactionId);
            if (! $this->isBankMutation($transaction) || $transaction->status !== 'draft') {
                throw new FinancialDomainException('E-BANK-MUTATION-STATE', 'Hanya draft Mutasi Bank yang dapat diubah.');
            }
            $context = $this->resolveContext($input);
            if ($context['type']->id !== $transaction->transaction_type_id) {
                throw new FinancialDomainException('E-BANK-MUTATION-TYPE', 'Jenis RCV/PAY tidak dapat diubah setelah draft dibuat. Buat draft baru untuk jenis mutasi lain.');
            }
            $this->lifecycle->updateDraft($transaction->id, $this->transactionInput($input, $context), $actorUserId);

            return $this->lifecycle->replaceDraftSplits($transaction->id, [$this->splitInput($input, $context)], $actorUserId);
        }, 3);
    }

    public function removeDraft(string $transactionId, ?int $actorUserId = null): FinancialTransaction
    {
        $transaction = FinancialTransaction::query()->findOrFail($transactionId);
        if (! $this->isBankMutation($transaction) || $transaction->status !== 'draft') {
            throw new FinancialDomainException('E-BANK-MUTATION-STATE', 'Hanya draft Mutasi Bank yang dapat dihapus.');
        }

        return $this->lifecycle->cancel($transaction->id, 'Draft Mutasi Bank dihapus oleh operator.', $actorUserId);
    }

    public function isBankMutation(FinancialTransaction $transaction): bool
    {
        $code = $transaction->relationLoaded('category') ? $transaction->category?->code : $transaction->category()->value('code');

        return array_key_exists((string) $code, self::CATEGORY_CODES);
    }

    /** @param array<string, mixed> $input @return array{policy: BankMutationPolicy, category: Category, type: mixed, account: Account, counterparty: ?Counterparty} */
    private function resolveContext(array $input): array
    {
        $policy = BankMutationPolicy::query()
            ->with(['category', 'transactionType'])
            ->where('accounting_entity_id', $input['accounting_entity_id'])
            ->where('financial_account_id', $input['financial_account_id'])
            ->where('fund_id', $input['fund_id'])
            ->where('category_id', $input['category_id'])
            ->where('status', 'active')
            ->where('effective_from', '<=', $input['date'])
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $input['date']))
            ->first();
        if (! $policy || ! $policy->category || ! $policy->transactionType || $policy->category->status !== 'active') {
            throw new FinancialDomainException('E-BANK-MUTATION-POLICY', 'Kombinasi rekening, Dana, jenis mutasi, dan tanggal belum memiliki policy aktif.');
        }
        $accountId = $policy->postingRuleVersion?->lines()->whereHas('account', fn ($query) => $query->where('is_liquidity_account', false))->value('account_id');
        $account = $accountId ? Account::query()->find($accountId) : null;
        if (! $account) {
            throw new FinancialDomainException('E-BANK-MUTATION-RULE', 'Posting rule Mutasi Bank belum lengkap.');
        }
        $counterparty = null;
        if ($policy->transactionType->code === TransactionTypeCode::Payment->value) {
            $counterparty = Counterparty::query()->where('accounting_entity_id', $input['accounting_entity_id'])->where('code', 'BANK-BNI')->where('status', 'active')->first();
            if (! $counterparty) {
                throw new FinancialDomainException('E-BANK-MUTATION-COUNTERPARTY', 'Master counterparty bank belum tersedia.');
            }
        }

        return ['policy' => $policy, 'category' => $policy->category, 'type' => $policy->transactionType, 'account' => $account, 'counterparty' => $counterparty];
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $context @return array<string, mixed> */
    private function transactionInput(array $input, array $context): array
    {
        return [
            'accounting_entity_id' => $input['accounting_entity_id'],
            'transaction_type_id' => $context['type']->id,
            'source_reference' => trim($input['source_reference']),
            'business_date' => $input['date'],
            'accounting_date' => $input['date'],
            'description' => trim($input['description']),
            'gross_amount' => $input['amount'],
            'primary_financial_account_id' => $input['financial_account_id'],
            'counterparty_id' => $context['counterparty']?->id,
            'category_id' => $context['category']->id,
            'idempotency_key' => 'bank-mutation:'.trim($input['source_reference']),
            'policy_version_ref' => $context['policy']->id,
            'correlation_id' => $input['correlation_id'] ?? null,
        ];
    }

    /** @param array<string, mixed> $input @param array<string, mixed> $context @return array<string, mixed> */
    private function splitInput(array $input, array $context): array
    {
        return [
            'account_id' => $context['account']->id,
            'split_amount' => $input['amount'],
            'fund_id' => $input['fund_id'],
            'financial_account_id' => $input['financial_account_id'],
            'counterparty_id' => $context['counterparty']?->id,
            'category_id' => $context['category']->id,
            'purpose_note' => trim($input['description']),
            'source_reference' => trim($input['source_reference']),
        ];
    }

    /**
     * @param  Collection<int,FinancialTransaction>  $existing
     * @param  array<string,mixed>  $common
     * @param  array<int,array<string,mixed>>  $mutations
     */
    private function assertBatchReplayMatches(Collection $existing, array $common, array $mutations): void
    {
        if ($existing->count() !== count($mutations)) {
            throw new FinancialDomainException('E-BANK-MUTATION-BATCH-IDEMPOTENCY', 'Batch ID sudah digunakan dengan jumlah mutasi berbeda.');
        }

        $existingByReference = $existing->keyBy('source_reference');
        foreach ($mutations as $mutation) {
            $transaction = $existingByReference->get(trim($mutation['source_reference']));
            $split = $transaction?->splits->first();
            if (! $transaction
                || $transaction->primary_financial_account_id !== $common['financial_account_id']
                || $transaction->accounting_date->toDateString() !== $common['date']
                || $transaction->category_id !== $mutation['category_id']
                || $split?->fund_id !== $mutation['fund_id']
                || ! DecimalAmount::equals($transaction->gross_amount, $mutation['amount'])
                || trim((string) $transaction->description) !== trim($mutation['description'])) {
                throw new FinancialDomainException('E-BANK-MUTATION-BATCH-IDEMPOTENCY', 'Batch ID sudah digunakan dengan payload berbeda.');
            }
        }
    }
}
