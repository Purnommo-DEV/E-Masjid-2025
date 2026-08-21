<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Controlled activation of masters and policy/rule versions. It deliberately
 * creates no business master values: operators supply approved master data.
 */
final class MasterDataGovernanceService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    public function activateFund(string $fundId, string $effectiveDate, ?int $actorUserId = null): Fund
    {
        return DB::transaction(function () use ($fundId, $effectiveDate, $actorUserId): Fund {
            $fund = Fund::query()->with(['type', 'restriction'])->lockForUpdate()->findOrFail($fundId);
            if ($fund->status === 'closed' || ! $fund->type || ! $fund->restriction
                || $fund->type->accounting_entity_id !== $fund->accounting_entity_id
                || $fund->restriction->accounting_entity_id !== $fund->accounting_entity_id
                || $fund->restriction->fund_type_id !== $fund->fund_type_id
                || $fund->type->status !== 'active' || $fund->restriction->status !== 'active'
                || blank($fund->purpose_statement)) {
                throw new FinancialDomainException('E-MASTER-GOVERNANCE', 'Fund cannot be activated without compatible active type, restriction, and purpose.');
            }

            if (in_array($fund->type->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true)) {
                $policy = FundPolicyVersion::query()
                    ->where('fund_id', $fund->id)
                    ->where('status', 'effective')
                    ->where('effective_from', '<=', $effectiveDate)
                    ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $effectiveDate))
                    ->first();
                if (! $policy || blank($policy->allowed_matrix_ref)) {
                    throw new FinancialDomainException('E-FUND-POLICY-REQUIRED', 'Restricted Fund activation requires an effective policy version with an allowed matrix reference.');
                }
            }

            $before = $this->summary($fund, ['status', 'valid_from']);
            $fund->update(['status' => 'active', 'valid_from' => $fund->valid_from ?? $effectiveDate, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($fund->accounting_entity_id, 'fund_activated', 'fund', $fund->id, (string) Str::uuid(), $actorUserId, $before, $this->summary($fund->fresh(), ['status', 'valid_from']));

            return $fund->fresh();
        }, 3);
    }

    public function activateFinancialAccount(string $financialAccountId, string $effectiveDate, ?int $actorUserId = null): FinancialAccount
    {
        return DB::transaction(function () use ($financialAccountId, $effectiveDate, $actorUserId): FinancialAccount {
            $financialAccount = FinancialAccount::query()->with(['account', 'bankDetail', 'cashDetail'])->lockForUpdate()->findOrFail($financialAccountId);
            if ($financialAccount->status === 'closed' || ! $financialAccount->account
                || $financialAccount->account->accounting_entity_id !== $financialAccount->accounting_entity_id
                || ! $financialAccount->account->is_liquidity_account || $financialAccount->account->status !== 'active'
                || $financialAccount->opening_date->gt($effectiveDate) || ! $this->hasCompatibleDetail($financialAccount)) {
                throw new FinancialDomainException('E-FINANCIAL-ACCOUNT-GOVERNANCE', 'Financial Account requires an active liquidity account and one compatible custody detail.');
            }

            $before = $this->summary($financialAccount, ['status']);
            $financialAccount->update(['status' => 'active', 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($financialAccount->accounting_entity_id, 'financial_account_activated', 'financial_account', $financialAccount->id, (string) Str::uuid(), $actorUserId, $before, $this->summary($financialAccount->fresh(), ['status']));

            return $financialAccount->fresh();
        }, 3);
    }

    public function activateProgram(string $programId, ?int $actorUserId = null): Program
    {
        return DB::transaction(function () use ($programId, $actorUserId): Program {
            $program = Program::query()->lockForUpdate()->findOrFail($programId);
            if ($program->status === 'closed' || ($program->end_date && $program->end_date->lt(now()->startOfDay()))) {
                throw new FinancialDomainException('E-PROGRAM-GOVERNANCE', 'A closed or ended Program cannot be activated.');
            }
            if ($program->cost_center_id) {
                $costCenter = DB::table('financial_v2_cost_centers')->where('id', $program->cost_center_id)->first();
                if (! $costCenter || $costCenter->accounting_entity_id !== $program->accounting_entity_id || $costCenter->status !== 'active') {
                    throw new FinancialDomainException('E-PROGRAM-GOVERNANCE', 'Program CostCenter must be active and in the same AccountingEntity.');
                }
            }

            $before = $this->summary($program, ['status']);
            $program->update(['status' => 'active', 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($program->accounting_entity_id, 'program_activated', 'program', $program->id, (string) Str::uuid(), $actorUserId, $before, $this->summary($program->fresh(), ['status']));

            return $program->fresh();
        }, 3);
    }

    public function makePostingRuleVersionEffective(string $versionId, ?int $actorUserId = null): PostingRuleVersion
    {
        return DB::transaction(function () use ($versionId, $actorUserId): PostingRuleVersion {
            $version = PostingRuleVersion::query()->with('rule')->lockForUpdate()->findOrFail($versionId);
            if (! $version->rule || $version->rule->accounting_entity_id !== $version->accounting_entity_id || $version->rule->status !== 'active') {
                throw new FinancialDomainException('E-POSTING-RULE-GOVERNANCE', 'Posting Rule Version must belong to an active rule in the same AccountingEntity.');
            }
            $lines = PostingRuleLine::query()->where('posting_rule_version_id', $version->id)->orderBy('line_no')->get();
            if ($lines->count() < 2) {
                throw new FinancialDomainException('E-POSTING-RULE-GOVERNANCE', 'An effective Posting Rule Version requires at least two deterministic rule lines.');
            }
            foreach ($lines as $line) {
                $account = Account::query()->find($line->account_id);
                if (! $account || $account->accounting_entity_id !== $version->accounting_entity_id) {
                    throw new FinancialDomainException('E-POSTING-RULE-GOVERNANCE', 'Posting Rule Lines must reference Accounts in the same AccountingEntity.');
                }
                $this->assertFixedDimensionScope($version->accounting_entity_id, $line);
            }
            $this->assertNoEffectiveOverlap(PostingRuleVersion::class, 'posting_rule_id', $version->posting_rule_id, $version);

            $before = $this->summary($version, ['status', 'approved_at']);
            $version->update(['status' => 'effective', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($version->accounting_entity_id, 'posting_rule_version_effective', 'posting_rule_version', $version->id, (string) Str::uuid(), $actorUserId, $before, $this->summary($version->fresh(), ['status', 'approved_at']));

            return $version->fresh();
        }, 3);
    }

    public function makeFundPolicyVersionEffective(string $versionId, ?int $actorUserId = null): FundPolicyVersion
    {
        return DB::transaction(function () use ($versionId, $actorUserId): FundPolicyVersion {
            $version = FundPolicyVersion::query()->with('fund.type')->lockForUpdate()->findOrFail($versionId);
            if (! $version->fund || $version->fund->accounting_entity_id !== $version->accounting_entity_id || blank($version->policy_document_ref)) {
                throw new FinancialDomainException('E-FUND-POLICY-GOVERNANCE', 'Fund Policy Version must reference an in-scope Fund and policy document.');
            }
            if (in_array($version->fund->type->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true) && blank($version->allowed_matrix_ref)) {
                throw new FinancialDomainException('E-FUND-POLICY-GOVERNANCE', 'Restricted Fund Policy Version requires an allowed matrix reference.');
            }
            // A policy is immutable once effective. A later approved policy
            // therefore supersedes (rather than edits) its effective
            // predecessor, preserving the historical decision and audit trail.
            $this->supersedeEffectiveFundPolicyPredecessors($version, $actorUserId);

            $before = $this->summary($version, ['status', 'approved_at']);
            $version->update(['status' => 'effective', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($version->accounting_entity_id, 'fund_policy_version_effective', 'fund_policy_version', $version->id, (string) Str::uuid(), $actorUserId, $before, $this->summary($version->fresh(), ['status', 'approved_at']));

            return $version->fresh();
        }, 3);
    }

    private function hasCompatibleDetail(FinancialAccount $financialAccount): bool
    {
        return match ($financialAccount->account_type) {
            'bank' => $financialAccount->bankDetail !== null && $financialAccount->cashDetail === null,
            'cash', 'petty_cash' => $financialAccount->cashDetail !== null && $financialAccount->bankDetail === null,
            'e_wallet' => $financialAccount->bankDetail === null && $financialAccount->cashDetail === null,
            default => false,
        };
    }

    private function assertFixedDimensionScope(string $entityId, PostingRuleLine $line): void
    {
        foreach ([
            'fixed_financial_account_id' => 'financial_v2_financial_accounts',
            'fixed_fund_id' => 'financial_v2_funds',
            'fixed_program_id' => 'financial_v2_programs',
            'fixed_cost_center_id' => 'financial_v2_cost_centers',
            'fixed_counterparty_id' => 'financial_v2_counterparties',
            'fixed_category_id' => 'financial_v2_categories',
        ] as $field => $table) {
            if (! $line->{$field}) {
                continue;
            }
            $dimension = DB::table($table)->where('id', $line->{$field})->first();
            if (! $dimension || $dimension->accounting_entity_id !== $entityId) {
                throw new FinancialDomainException('E-POSTING-RULE-GOVERNANCE', 'Fixed rule dimensions must be in the same AccountingEntity.');
            }
        }
    }

    private function assertNoEffectiveOverlap(string $modelClass, string $foreignKey, string $parentId, object $version): void
    {
        $overlap = $modelClass::query()
            ->where($foreignKey, $parentId)
            ->where('status', 'effective')
            ->whereKeyNot($version->id)
            ->where('effective_from', '<=', $version->effective_to ?? '9999-12-31')
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $version->effective_from))
            ->exists();
        if ($overlap) {
            throw new FinancialDomainException('E-MASTER-VERSION-OVERLAP', 'Effective master-policy date ranges must not overlap.');
        }
    }

    private function supersedeEffectiveFundPolicyPredecessors(FundPolicyVersion $version, ?int $actorUserId): void
    {
        $overlaps = FundPolicyVersion::query()
            ->where('fund_id', $version->fund_id)
            ->where('status', 'effective')
            ->whereKeyNot($version->id)
            ->where('effective_from', '<=', $version->effective_to ?? '9999-12-31')
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $version->effective_from))
            ->lockForUpdate()
            ->get();

        foreach ($overlaps as $predecessor) {
            if ($predecessor->effective_from->gte($version->effective_from)) {
                throw new FinancialDomainException('E-MASTER-VERSION-OVERLAP', 'Effective master-policy date ranges must not overlap. A successor policy must start after its predecessor.');
            }

            $before = $this->summary($predecessor, ['status', 'effective_to', 'approved_at']);
            $predecessor->update([
                'status' => 'superseded',
                'effective_to' => $version->effective_from->copy()->subDay()->toDateString(),
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($predecessor->accounting_entity_id, 'fund_policy_version_superseded', 'fund_policy_version', $predecessor->id, (string) Str::uuid(), $actorUserId, $before, $this->summary($predecessor->fresh(), ['status', 'effective_to', 'approved_at']));
        }
    }

    /** @param array<int, string> $fields @return array<string, mixed> */
    private function summary(object $model, array $fields): array
    {
        return collect($fields)->mapWithKeys(fn (string $field) => [$field => $model->{$field} instanceof \DateTimeInterface ? $model->{$field}->format(DATE_ATOM) : $model->{$field}])->all();
    }
}
