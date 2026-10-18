<?php

namespace App\Domain\FinancialV2;

use App\Domain\FinancialV2\Reporting\FundFinancialAccountCompositionReadService;
use App\Domain\FinancialV2\Reporting\FinancialReportDefinitions;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\ReasonCode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class CanonicalAccountingDateCorrectionFinancialPreview implements AccountingDateCorrectionFinancialPreviewContract
{
    public function __construct(
        private readonly PostingEngine $postingEngine,
        private readonly FundFinancialAccountCompositionReadService $fundFinancialAccounts,
        private readonly FinancialReportDefinitions $reportDefinitions,
        private readonly AccountingDateCorrectionPolicyException $policyExceptions,
    ) {}

    public function preview(FinancialTransaction $originalTransaction, Journal $originalJournal, string $targetAccountingDate, ?array $policyException = null): AccountingDateCorrectionPostingPlan
    {
        return DB::transaction(
            fn (): AccountingDateCorrectionPostingPlan => $this->previewFromConsistentSnapshot($originalTransaction, $originalJournal, $targetAccountingDate, $policyException),
            3,
        );
    }

    private function previewFromConsistentSnapshot(FinancialTransaction $originalTransaction, Journal $originalJournal, string $targetAccountingDate, ?array $policyException): AccountingDateCorrectionPostingPlan
    {
        $originalTransaction = FinancialTransaction::query()->with('type')->findOrFail($originalTransaction->id);
        $originalJournal = Journal::query()->with('lines')->findOrFail($originalJournal->id);
        $sourceDate = $originalTransaction->accounting_date->toDateString();
        $blockers = [];
        $warnings = [];
        if ($targetAccountingDate >= $sourceDate) {
            return new AccountingDateCorrectionPostingPlan([
                'status' => 'not_simulated', 'financial_simulation_status' => 'not_simulated',
                'source_accounting_date' => $sourceDate, 'target_accounting_date' => $targetAccountingDate,
                'blocking_reasons' => [$this->blocker('CORRECTION_TIMELINE_DIRECTION_UNSUPPORTED', 'Planner tahap ini hanya mendukung pemindahan tanggal akuntansi ke tanggal yang lebih awal.')],
                'warnings' => [], 'liquidity_validation' => ['complete' => false], 'execution_ready' => false,
            ]);
        }

        try {
            if($policyException)$this->policyExceptions->assertMatches($policyException,$originalTransaction,$originalJournal,$targetAccountingDate);
            $compiled = $this->postingEngine->compileAccountingDateCorrectionPlan($originalTransaction, $originalJournal, $targetAccountingDate, $policyException);
        } catch (FinancialPostingException $exception) {
            return new AccountingDateCorrectionPostingPlan([
                'status' => 'not_simulated',
                'financial_simulation_status' => 'not_simulated',
                'source_accounting_date' => $sourceDate,
                'target_accounting_date' => $targetAccountingDate,
                'blocking_reasons' => [...$blockers, $this->blocker($exception->failureCode, $exception->getMessage())],
                'warnings' => $warnings,
                'execution_ready' => false,
            ]);
        }

        $reasonCodes = ReasonCode::query()
            ->where('accounting_entity_id', $originalTransaction->accounting_entity_id)
            ->where('reason_class', 'reversal')->where('status', 'active')->orderBy('id')->pluck('id')->all();
        $warnings[] = $this->blocker('REVERSAL_REASON_CODE_NOT_SELECTED', 'Reason code reversal harus dipilih dan divalidasi sebelum eksekusi dapat dirancang.', ['available_reason_code_ids' => $reasonCodes]);
        $warnings[] = $this->blocker('REPLACEMENT_GOVERNANCE_NOT_MATERIALIZED', 'Approval dan evidence replacement belum menjadi fakta karena transaksi replacement belum dibentuk oleh workflow resmi.');

        $reversalByKey = collect($compiled['reversal_liquidity_effects'])->keyBy(fn (array $row): string => $this->effectKey($row));
        $replacementByKey = collect($compiled['replacement_liquidity_effects'])->keyBy(fn (array $row): string => $this->effectKey($row));
        $timelines = [];
        foreach ($compiled['replacement_liquidity_effects'] as $replacementEffect) {
            $key = $this->effectKey($replacementEffect);
            $reversalEffect = $reversalByKey->get($key);
            if (! $reversalEffect || ! DecimalAmount::equals(DecimalAmount::add($replacementEffect['amount'], $reversalEffect['amount']), '0.00')) {
                $blockers[] = $this->blocker('LIQUIDITY_EFFECT_PAIR_MISMATCH', 'Efek likuiditas replacement dan reversal tidak membentuk pasangan net-zero.', ['dimension_key' => $key]);
                continue;
            }
            $projection = $this->fundFinancialAccounts->accountingDateCorrectionProjection(
                $originalTransaction->accounting_entity_id,
                $replacementEffect['fund_id'],
                $replacementEffect['financial_account_id'],
                $targetAccountingDate,
                $sourceDate,
                $replacementEffect['amount'],
                $reversalEffect['amount'],
                $originalJournal->id,
            );
            $fund = Fund::query()->findOrFail($replacementEffect['fund_id']);
            $minimumPolicy = DecimalAmount::normalize($fund->minimum_balance_policy ?? 0);
            if (! $fund->allow_negative_balance && DecimalAmount::compare($projection['minimum_projected_balance'], $minimumPolicy) < 0) {
                $blockers[] = $this->blocker('PROJECTED_LIQUIDITY_BELOW_MINIMUM', 'Correction timeline melanggar minimum balance Fund.', ['dimension_key' => $key, 'minimum_date' => $projection['minimum_projected_date']]);
            }
            $timelines[] = ['fund_id' => $replacementEffect['fund_id'], 'financial_account_id' => $replacementEffect['financial_account_id'], 'account_id' => $replacementEffect['account_id'], 'minimum_balance_policy' => $minimumPolicy] + $projection;
        }
        foreach ($reversalByKey as $key => $effect) {
            if (! $replacementByKey->has($key)) {
                $blockers[] = $this->blocker('LIQUIDITY_EFFECT_PAIR_MISMATCH', 'Reversal memiliki dimensi likuiditas tanpa pasangan replacement.', ['dimension_key' => $key]);
            }
        }
        if ($replacementByKey->isEmpty() || $reversalByKey->isEmpty()) {
            $blockers[] = $this->blocker('LIQUIDITY_EFFECT_MISSING', 'PAY/RCV correction plan tidak menghasilkan efek likuiditas lengkap yang dapat divalidasi.');
        }
        $liquidityComplete = $replacementByKey->isNotEmpty()
            && $replacementByKey->count() === $reversalByKey->count()
            && count($timelines) === $replacementByKey->count()
            && collect($timelines)->every(fn (array $timeline): bool => DecimalAmount::equals($timeline['ending_cumulative_effect'], '0.00'))
            && ! collect($blockers)->contains(fn (array $row): bool => in_array($row['code'], ['LIQUIDITY_EFFECT_MISSING', 'LIQUIDITY_EFFECT_PAIR_MISMATCH'], true));
        $governedCounterpartyCompletion = ($policyException['counterparty']['mode'] ?? null) === 'verified_bni_bank_counterparty';
        $cumulativeDimensionChanges = $this->cumulativeDimensionChanges($compiled['reversal_lines'], $compiled['replacement_lines'], $governedCounterpartyCompletion);
        if (collect($cumulativeDimensionChanges)->contains(fn (array $row): bool => ! DecimalAmount::equals($row['net_debit_change'], '0.00'))) {
            $blockers[] = $this->blocker('CUMULATIVE_DIMENSION_EFFECT_CHANGED', 'Replacement dan reversal mengubah efek kumulatif akun/dimensi; koreksi tanggal harus net-zero setelah tanggal asal.');
        }

        $balanceCheckpoints = $this->balanceCheckpoints($originalTransaction->accounting_entity_id, $timelines, $targetAccountingDate, $sourceDate);
        $liquidityComplete = $liquidityComplete && count($balanceCheckpoints) === count($timelines);
        $snapshot = $compiled + [
            'status' => $blockers === [] ? 'simulated' : 'blocked',
            'financial_simulation_status' => 'simulated',
            'source_transaction_id' => $originalTransaction->id,
            'source_journal_id' => $originalJournal->id,
            'source_accounting_date' => $sourceDate,
            'target_accounting_date' => $targetAccountingDate,
            'dimension_effects' => [
                'replacement' => $this->dimensionEffects($compiled['replacement_lines']),
                'reversal' => $this->dimensionEffects($compiled['reversal_lines']),
            ],
            'cumulative_dimension_changes' => $cumulativeDimensionChanges,
            'liquidity_timelines' => $timelines,
            'liquidity_validation' => ['complete' => $liquidityComplete, 'replacement_dimension_count' => $replacementByKey->count(), 'reversal_dimension_count' => $reversalByKey->count(), 'validated_timeline_count' => count($timelines)],
            'balance_checkpoints' => $balanceCheckpoints,
            'period_report_effects' => $this->periodEffects($compiled['reversal_lines'], $compiled['replacement_lines'], $sourceDate, $targetAccountingDate, $originalTransaction->type->code),
            'reporting_semantics' => [
                'effective_transaction_type' => $originalTransaction->type->code,
                'cash_in_types' => $this->reportDefinitions->cashInTypes(),
                'cash_out_types' => $this->reportDefinitions->cashOutTypes(),
                'accounts' => $this->reportingAccountFacts([...$compiled['reversal_lines'], ...$compiled['replacement_lines']]),
            ],
            'blocking_reasons' => $blockers,
            'warnings' => $warnings,
            'execution_ready' => false,
        ];
        $snapshot['canonical_fingerprint'] = hash('sha256', json_encode($this->canonicalize($snapshot), JSON_THROW_ON_ERROR));

        return new AccountingDateCorrectionPostingPlan($snapshot);
    }

    /** @param array<int,array<string,mixed>> $reversal @param array<int,array<string,mixed>> $replacement */
    private function periodEffects(array $reversal, array $replacement, string $sourceDate, string $targetDate, string $effectiveType): array
    {
        return [
            ['accounting_date' => $targetDate, 'operation' => 'replacement'] + $this->statementEffect($replacement, $effectiveType),
            ['accounting_date' => $sourceDate, 'operation' => 'reversal'] + $this->statementEffect($reversal, $effectiveType),
        ];
    }

    /** @param array<int,array<string,mixed>> $lines @return array<int,array<string,mixed>> */
    private function dimensionEffects(array $lines): array
    {
        return collect($lines)->groupBy(fn (array $line): string => implode('|', [$line['account_id'], $line['fund_id'] ?? '', $line['financial_account_id'] ?? '']))
            ->map(function ($rows): array {
                $first = $rows->first();
                return ['account_id' => $first['account_id'], 'fund_id' => $first['fund_id'], 'financial_account_id' => $first['financial_account_id'], 'debit' => DecimalAmount::sum($rows->pluck('debit_amount')->all()), 'credit' => DecimalAmount::sum($rows->pluck('credit_amount')->all())];
            })->values()->all();
    }

    /** @param array<int,array<string,mixed>> $reversal @param array<int,array<string,mixed>> $replacement @return array<int,array<string,mixed>> */
    private function cumulativeDimensionChanges(array $reversal, array $replacement, bool $ignoreCounterparty = false): array
    {
        return collect([...$reversal, ...$replacement])
            ->groupBy(fn (array $line): string => implode('|', [$line['account_id'], $line['fund_id'] ?? '', $line['financial_account_id'] ?? '', $line['program_id'] ?? '', $line['cost_center_id'] ?? '', $ignoreCounterparty ? '' : ($line['counterparty_id'] ?? ''), $line['category_id'] ?? '']))
            ->map(function ($rows) use ($ignoreCounterparty): array {
                $first = $rows->first();
                return [
                    'account_id' => $first['account_id'], 'fund_id' => $first['fund_id'], 'financial_account_id' => $first['financial_account_id'],
                    'program_id' => $first['program_id'], 'cost_center_id' => $first['cost_center_id'], 'counterparty_id' => $ignoreCounterparty ? null : $first['counterparty_id'], 'category_id' => $first['category_id'],
                    'net_debit_change' => DecimalAmount::subtract(DecimalAmount::sum($rows->pluck('debit_amount')->all()), DecimalAmount::sum($rows->pluck('credit_amount')->all())),
                ];
            })->values()->all();
    }

    /** @param array<int,array<string,mixed>> $lines @return array<string,string> */
    private function statementEffect(array $lines, string $effectiveType): array
    {
        $revenue = '0.00';
        $expense = '0.00';
        foreach ($lines as $line) {
            $account = Account::query()->findOrFail($line['account_id']);
            $signed = $account->normal_balance === 'debit'
                ? DecimalAmount::subtract($line['debit_amount'], $line['credit_amount'])
                : DecimalAmount::subtract($line['credit_amount'], $line['debit_amount']);
            $kind = $this->reportDefinitions->statementKind($effectiveType, $account->account_class);
            if ($kind === 'receipt') $revenue = DecimalAmount::add($revenue, $signed);
            if ($kind === 'usage') $expense = DecimalAmount::add($expense, $signed);
        }
        return ['total_debit' => DecimalAmount::sum(array_column($lines, 'debit_amount')), 'total_credit' => DecimalAmount::sum(array_column($lines, 'credit_amount')), 'revenue_change' => $revenue, 'expense_change' => $expense];
    }

    /** @param array<int,array<string,mixed>> $lines @return array<int,array<string,mixed>> */
    private function reportingAccountFacts(array $lines): array
    {
        return Account::query()->whereIn('id', collect($lines)->pluck('account_id')->unique()->all())
            ->orderBy('id')->get(['id', 'account_class', 'normal_balance', 'is_liquidity_account', 'valid_from', 'valid_to', 'status'])
            ->map(fn (Account $account): array => [
                'id' => $account->id, 'account_class' => $account->account_class, 'normal_balance' => $account->normal_balance,
                'is_liquidity_account' => (bool) $account->is_liquidity_account,
                'valid_from' => $account->valid_from?->toDateString(), 'valid_to' => $account->valid_to?->toDateString(), 'status' => $account->status,
            ])->values()->all();
    }

    /** @param array<int,array<string,mixed>> $timelines @return array<int,array<string,mixed>> */
    private function balanceCheckpoints(string $entityId, array $timelines, string $targetDate, string $sourceDate): array
    {
        $targetMonthEnd = CarbonImmutable::parse($targetDate)->endOfMonth()->toDateString();
        $sourceMonthStart = CarbonImmutable::parse($sourceDate)->startOfMonth()->subDay()->toDateString();
        $sourceMonthEnd = CarbonImmutable::parse($sourceDate)->endOfMonth()->toDateString();
        return collect($timelines)->map(function (array $timeline) use ($entityId, $targetDate, $sourceDate, $targetMonthEnd, $sourceMonthStart, $sourceMonthEnd): array {
            $points = [];
            foreach (['target_month_end' => $targetMonthEnd, 'source_month_opening' => $sourceMonthStart, 'source_month_end' => $sourceMonthEnd] as $label => $date) {
                $effect = '0.00';
                if ($date >= $targetDate) $effect = DecimalAmount::add($effect, $timeline['replacement_effect']);
                if ($date >= $sourceDate) $effect = DecimalAmount::add($effect, $timeline['reversal_effect']);
                $current = $this->fundFinancialAccounts->snapshotBalance($entityId, $timeline['fund_id'], $timeline['financial_account_id'], $date);
                $points[$label] = ['date' => $date, 'current_balance' => $current, 'correction_effect' => $effect, 'projected_balance' => DecimalAmount::add($current, $effect)];
            }
            return ['fund_id' => $timeline['fund_id'], 'financial_account_id' => $timeline['financial_account_id'], 'points' => $points];
        })->values()->all();
    }

    /** @param array<string,mixed> $effect */
    private function effectKey(array $effect): string { return implode('|', [$effect['fund_id'], $effect['financial_account_id'], $effect['account_id']]); }
    private function blocker(string $code, string $message, array $context = []): array { return ['code' => $code, 'message' => $message] + $context; }
    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) return $value;
        if (! array_is_list($value)) ksort($value);
        foreach ($value as $key => $item) $value[$key] = $this->canonicalize($item);
        return $value;
    }
}
