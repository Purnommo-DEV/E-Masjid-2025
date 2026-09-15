<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FundPolicyRuleDeduplicationService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @return array{groups:array<int,array<string,mixed>>,duplicate_count:int,protected_count:int} */
    public function audit(?string $entityId = null): array
    {
        $rules = FundPolicyRule::query()
            ->when($entityId, fn ($query) => $query->where('accounting_entity_id', $entityId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
        $groups = $rules->groupBy(fn (FundPolicyRule $rule): string => $this->semanticKey($rule))
            ->filter(fn (Collection $matches): bool => $matches->count() > 1)
            ->map(function (Collection $matches): array {
                /** @var FundPolicyRule $canonical */
                $canonical = $matches->first();
                $usage = $this->usage($canonical->fund_policy_version_id);

                return [
                    'accounting_entity_id' => $canonical->accounting_entity_id,
                    'fund_policy_version_id' => $canonical->fund_policy_version_id,
                    'canonical_rule_id' => $canonical->id,
                    'duplicate_rule_ids' => $matches->skip(1)->pluck('id')->values()->all(),
                    'semantic_identity' => $this->semanticIdentity($canonical),
                    'protected' => $usage['transaction_count'] > 0 || $usage['journal_line_count'] > 0,
                    'transaction_count' => $usage['transaction_count'],
                    'journal_line_count' => $usage['journal_line_count'],
                ];
            })->values()->all();

        return [
            'groups' => $groups,
            'duplicate_count' => collect($groups)->sum(fn (array $group): int => count($group['duplicate_rule_ids'])),
            'protected_count' => collect($groups)->where('protected', true)->count(),
        ];
    }

    /** @return array{groups:array<int,array<string,mixed>>,duplicate_count:int,protected_count:int,removed_count:int,facts_before:array<string,int>,facts_after:array<string,int>} */
    public function clean(?string $entityId = null, ?int $actorUserId = null): array
    {
        $audit = $this->audit($entityId);
        $factsBefore = $this->factCounts();
        if ($audit['protected_count'] > 0) {
            throw new FinancialDomainException('E-FUND-POLICY-RULE-USED', 'Duplicate aturan Dana berada pada policy version yang sudah direferensikan financial fact. Tidak ada rule yang dihapus.');
        }

        $result = DB::transaction(function () use ($audit, $actorUserId, $factsBefore): array {
            $removed = 0;
            foreach ($audit['groups'] as $group) {
                FundPolicyVersion::query()->lockForUpdate()->findOrFail($group['fund_policy_version_id']);
                $usage = $this->usage($group['fund_policy_version_id']);
                if ($usage['transaction_count'] > 0 || $usage['journal_line_count'] > 0) {
                    throw new FinancialDomainException('E-FUND-POLICY-RULE-USED', 'Policy version mulai digunakan saat cleanup berlangsung. Tidak ada rule yang dihapus.');
                }
                foreach ($group['duplicate_rule_ids'] as $duplicateId) {
                    $duplicate = FundPolicyRule::query()->lockForUpdate()->findOrFail($duplicateId);
                    $before = $this->semanticIdentity($duplicate) + ['rationale' => $duplicate->rationale];
                    $duplicate->delete();
                    $this->auditTrail->record(
                        $duplicate->accounting_entity_id,
                        'duplicate_fund_policy_rule_removed',
                        'fund_policy_rule',
                        $duplicateId,
                        (string) Str::uuid(),
                        $actorUserId,
                        $before,
                        ['deleted' => true, 'canonical_rule_id' => $group['canonical_rule_id']],
                    );
                    $removed++;
                }
            }

            $factsAfter = $this->factCounts();
            if ($factsBefore !== $factsAfter) {
                throw new FinancialDomainException('E-CONFIGURATION-FACT-MUTATION', 'Cleanup duplicate aturan Dana mengubah financial fact dan dibatalkan.');
            }

            return ['removed_count' => $removed, 'facts_after' => $factsAfter];
        }, 3);

        return $audit + ['removed_count' => $result['removed_count'], 'facts_before' => $factsBefore, 'facts_after' => $result['facts_after']];
    }

    /** @return array{transaction_count:int,journal_line_count:int} */
    private function usage(string $policyVersionId): array
    {
        return [
            'transaction_count' => DB::table('financial_v2_transactions')->where('policy_version_ref', $policyVersionId)->count(),
            'journal_line_count' => DB::table('financial_v2_journal_lines')->where('policy_version_ref', $policyVersionId)->count(),
        ];
    }

    private function semanticKey(FundPolicyRule $rule): string
    {
        return json_encode($this->semanticIdentity($rule), JSON_THROW_ON_ERROR);
    }

    /** @return array<string, string|null> */
    private function semanticIdentity(FundPolicyRule $rule): array
    {
        return $rule->only([
            'accounting_entity_id',
            'fund_policy_version_id',
            'transaction_type_id',
            'decision',
            'account_id',
            'category_id',
            'program_id',
            'cost_center_id',
        ]);
    }

    /** @return array<string, int> */
    private function factCounts(): array
    {
        return [
            'transactions' => DB::table('financial_v2_transactions')->count(),
            'journals' => DB::table('financial_v2_journals')->count(),
            'journal_lines' => DB::table('financial_v2_journal_lines')->count(),
            'ledger_entries' => DB::table('financial_v2_ledger_entries')->count(),
            'vouchers' => DB::table('financial_v2_vouchers')->count(),
        ];
    }
}
