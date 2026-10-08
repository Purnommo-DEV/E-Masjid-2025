<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Governed correction path for an effective policy that has never governed an
 * approved allocation or financial fact. Used policies remain immutable.
 */
final class UnusedEffectiveFundPolicyReplacementService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @return array{eligible:bool,status:string,usage:array<string,int>} */
    public function eligibility(FundPolicyVersion $version): array
    {
        if ($version->status !== 'effective') {
            return ['eligible' => false, 'status' => 'NOT_EFFECTIVE', 'usage' => []];
        }

        $usage = $this->usage($version);
        $eligible = $version->rules()->exists()
            && collect($usage)->every(fn (int $count): bool => $count === 0);

        return [
            'eligible' => $eligible,
            'status' => $eligible ? 'UNUSED_EFFECTIVE' : 'USED_OR_EMPTY',
            'usage' => $usage,
        ];
    }

    /**
     * Copies the complete version and all rules, applies one explicitly
     * approved dimension correction, then atomically replaces the unused
     * effective version over the exact same effective period.
     *
     * @param  array{account_id:?string,cost_center_id:?string}  $correction
     */
    public function replace(
        string $entityId,
        string $versionId,
        string $ruleId,
        array $correction,
        string $reason,
        ?int $actorUserId = null,
    ): FundPolicyVersion {
        $reason = trim($reason);
        if ($reason === '') {
            throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-REASON', 'Alasan audit penggantian policy wajib diisi.');
        }

        return DB::transaction(function () use ($entityId, $versionId, $ruleId, $correction, $reason, $actorUserId): FundPolicyVersion {
            $source = FundPolicyVersion::query()
                ->where('accounting_entity_id', $entityId)
                ->with(['fund.type', 'rules'])
                ->lockForUpdate()
                ->findOrFail($versionId);
            Fund::query()->whereKey($source->fund_id)->lockForUpdate()->firstOrFail();

            if ($source->status !== 'effective') {
                throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-STATUS', 'Hanya Fund Policy effective yang belum digunakan yang dapat diganti melalui lifecycle ini.');
            }
            if ($source->rules->isEmpty()) {
                throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-RULES', 'Fund Policy effective tidak memiliki rule yang dapat disalin.');
            }
            $targetRule = $source->rules->firstWhere('id', $ruleId);
            if (! $targetRule) {
                throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-RULE', 'Rule yang dikoreksi tidak berasal dari Fund Policy tersebut.');
            }

            $replacementAccountId = $correction['account_id'] ?? null;
            $replacementCostCenterId = $correction['cost_center_id'] ?? null;
            if ($targetRule->account_id === $replacementAccountId && $targetRule->cost_center_id === $replacementCostCenterId) {
                throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-NOOP', 'Replacement wajib mengoreksi Account atau Cost Center restriction pada rule yang dipilih.');
            }

            $this->assertDimensionScope($entityId, 'financial_v2_accounts', $replacementAccountId, 'Account');
            $this->assertDimensionScope($entityId, 'financial_v2_cost_centers', $replacementCostCenterId, 'Cost Center');

            $usage = $this->usage($source, true);
            if (collect($usage)->contains(fn (int $count): bool => $count > 0)) {
                throw new FinancialDomainException(
                    'E-FUND-POLICY-REPLACEMENT-USED',
                    'Fund Policy sudah digunakan oleh Allocation atau financial fact sehingga wajib tetap immutable.',
                    ['usage' => $usage],
                );
            }

            $overlap = FundPolicyVersion::query()
                ->where('fund_id', $source->fund_id)
                ->whereKeyNot($source->id)
                ->where(fn ($query) => $query->where('status', 'effective')->orWhere(fn ($historical) => $historical->where('status', 'superseded')->whereNotNull('approved_at')))
                ->where('effective_from', '<=', $source->effective_to ?? '9999-12-31')
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $source->effective_from))
                ->lockForUpdate()
                ->exists();
            if ($overlap) {
                throw new FinancialDomainException('E-MASTER-VERSION-OVERLAP', 'Penggantian dibatalkan karena terdapat policy lain pada periode yang sama.');
            }

            $nextVersion = ((int) FundPolicyVersion::query()->where('fund_id', $source->fund_id)->max('version_no')) + 1;
            $replacement = FundPolicyVersion::query()->create([
                'accounting_entity_id' => $source->accounting_entity_id,
                'fund_id' => $source->fund_id,
                'version_no' => $nextVersion,
                'effective_from' => $source->effective_from->toDateString(),
                'effective_to' => $source->effective_to?->toDateString(),
                'policy_document_ref' => $source->policy_document_ref,
                'allowed_matrix_ref' => $source->allowed_matrix_ref,
                'exception_approval_level' => $source->exception_approval_level,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);

            foreach ($source->rules as $rule) {
                $attributes = $rule->only([
                    'transaction_type_id', 'account_id', 'category_id', 'program_id',
                    'cost_center_id', 'decision', 'rationale',
                ]);
                if ($rule->id === $targetRule->id) {
                    $attributes['account_id'] = $correction['account_id'] ?? null;
                    $attributes['cost_center_id'] = $correction['cost_center_id'] ?? null;
                }
                FundPolicyRule::query()->create($attributes + [
                    'accounting_entity_id' => $source->accounting_entity_id,
                    'fund_policy_version_id' => $replacement->id,
                ]);
            }

            $this->assertNoDuplicateRules($replacement);
            $correlationId = (string) Str::uuid();
            $sourceBefore = $this->versionSummary($source);
            $source->update(['status' => 'replaced_unused', 'updated_by_user_id' => $actorUserId]);
            $replacement->update([
                'status' => 'effective',
                'approved_at' => now(),
                'approved_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);

            $auditContext = [
                'reason' => $reason,
                'usage' => $usage,
                'corrected_source_rule_id' => $targetRule->id,
                'correction' => [
                    'account_id' => $correction['account_id'] ?? null,
                    'cost_center_id' => $correction['cost_center_id'] ?? null,
                ],
            ];
            $this->auditTrail->record(
                $source->accounting_entity_id,
                'fund_policy_version_replaced_unused',
                'fund_policy_version',
                $source->id,
                $correlationId,
                $actorUserId,
                $sourceBefore,
                $this->versionSummary($source->fresh()) + ['replacement_id' => $replacement->id] + $auditContext,
            );
            $this->auditTrail->record(
                $replacement->accounting_entity_id,
                'fund_policy_version_effective_replacement',
                'fund_policy_version',
                $replacement->id,
                $correlationId,
                $actorUserId,
                null,
                $this->versionSummary($replacement->fresh()) + ['replaced_version_id' => $source->id] + $auditContext,
            );

            return $replacement->fresh('rules');
        }, 3);
    }

    /** @return array<string, int> */
    private function usage(FundPolicyVersion $version, bool $lock = false): array
    {
        $from = $version->effective_from->toDateString();
        $to = $version->effective_to?->toDateString() ?? '9999-12-31';
        $query = fn (string $table) => DB::table($table);

        $fundingQuery = $query('financial_v2_budget_allocation_fundings')->where('fund_id', $version->fund_id);
        $fundings = ($lock ? $fundingQuery->lockForUpdate() : $fundingQuery)->get();
        $allocationVersionQuery = $query('financial_v2_budget_allocation_versions')
            ->whereIn('id', $fundings->pluck('budget_allocation_version_id'))
            ->where('effective_from', '<=', $to)
            ->where(fn ($range) => $range->whereNull('effective_to')->orWhere('effective_to', '>=', $from));
        $allocationVersions = ($lock ? $allocationVersionQuery->lockForUpdate() : $allocationVersionQuery)->get();
        $allocationQuery = $query('financial_v2_budget_allocations')->whereIn('id', $allocationVersions->pluck('budget_allocation_id'));
        $allocations = ($lock ? $allocationQuery->lockForUpdate() : $allocationQuery)->get();

        $payTypeId = TransactionType::query()
            ->where('accounting_entity_id', $version->accounting_entity_id)
            ->where('code', TransactionTypeCode::Payment->value)
            ->value('id');
        $splitQuery = $query('financial_v2_transaction_splits')->where('fund_id', $version->fund_id);
        $splits = ($lock ? $splitQuery->lockForUpdate() : $splitQuery)->get();
        $transactionQuery = $query('financial_v2_transactions')
            ->whereIn('id', $splits->pluck('transaction_id'))
            ->where('transaction_type_id', $payTypeId)
            ->whereBetween('accounting_date', [$from, $to]);
        $transactions = ($lock ? $transactionQuery->lockForUpdate() : $transactionQuery)->get();

        $realizationQuery = $query('financial_v2_fund_realizations')
            ->whereIn('budget_allocation_version_id', $allocationVersions->pluck('id'));
        $realizations = ($lock ? $realizationQuery->lockForUpdate() : $realizationQuery)->get();
        $journalQuery = $query('financial_v2_journals')
            ->whereIn('transaction_id', $transactions->pluck('id'))
            ->where('journal_status', 'posted');
        $journals = ($lock ? $journalQuery->lockForUpdate() : $journalQuery)->get();
        $journalLineQuery = $query('financial_v2_journal_lines')->whereIn('journal_id', $journals->pluck('id'));
        $journalLines = ($lock ? $journalLineQuery->lockForUpdate() : $journalLineQuery)->get();
        $ledgerQuery = $query('financial_v2_ledger_entries')->whereIn('journal_line_id', $journalLines->pluck('id'));
        $ledgerEntries = ($lock ? $ledgerQuery->lockForUpdate() : $ledgerQuery)->get();

        $directTransactionQuery = $query('financial_v2_transactions')->where('policy_version_ref', $version->id);
        $directTransactions = ($lock ? $directTransactionQuery->lockForUpdate() : $directTransactionQuery)->get();
        $directJournalLineQuery = $query('financial_v2_journal_lines')->where('policy_version_ref', $version->id);
        $directJournalLines = ($lock ? $directJournalLineQuery->lockForUpdate() : $directJournalLineQuery)->get();

        return [
            'allocation_approved' => $allocations->where('status', 'approved')->count(),
            'allocation_version_approved' => $allocationVersions->where('status', 'approved')->count(),
            'fund_realization' => $realizations->count(),
            'pay_transaction' => $transactions->count(),
            'posted_journal' => $journals->count(),
            'posted_journal_line' => $journalLines->count(),
            'ledger_entry' => $ledgerEntries->count(),
            'direct_transaction_reference' => $directTransactions->count(),
            'direct_journal_line_reference' => $directJournalLines->count(),
        ];
    }

    private function assertDimensionScope(string $entityId, string $table, ?string $id, string $label): void
    {
        if ($id && ! DB::table($table)->where('accounting_entity_id', $entityId)->where('id', $id)->exists()) {
            throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-SCOPE', "{$label} koreksi tidak berada dalam Accounting Entity policy.");
        }
    }

    private function assertNoDuplicateRules(FundPolicyVersion $version): void
    {
        $duplicate = $version->rules()->get()->groupBy(fn (FundPolicyRule $rule): string => json_encode($rule->only([
            'transaction_type_id', 'account_id', 'category_id', 'program_id', 'cost_center_id',
        ]), JSON_THROW_ON_ERROR))->first(fn ($rules): bool => $rules->count() > 1);
        if ($duplicate) {
            throw new FinancialDomainException('E-FUND-POLICY-REPLACEMENT-DUPLICATE', 'Koreksi menghasilkan rule Fund Policy dengan cakupan duplikat.');
        }
    }

    /** @return array<string, mixed> */
    private function versionSummary(FundPolicyVersion $version): array
    {
        return [
            'id' => $version->id,
            'fund_id' => $version->fund_id,
            'version_no' => $version->version_no,
            'effective_from' => $version->effective_from?->toDateString(),
            'effective_to' => $version->effective_to?->toDateString(),
            'status' => $version->status,
            'approved_at' => $version->approved_at?->toAtomString(),
        ];
    }
}
