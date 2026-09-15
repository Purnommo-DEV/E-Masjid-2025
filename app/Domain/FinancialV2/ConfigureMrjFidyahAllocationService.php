<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** Canonical, configuration-only provisioning for the approved Fidyah allocation. */
final class ConfigureMrjFidyahAllocationService
{
    public const ENTITY_CODE = 'MRJ-ACTUAL';

    public const EFFECTIVE_FROM = '2026-08-22';

    public const CATEGORY_CODE = 'PAY-FIDYAH';

    public const ORIGIN_ADMIN = 'ADMIN_CONFIGURATION_PROVISION';

    /** @var array<string, array{document:string,matrix:string}> */
    private const POLICIES = [
        'FIDYAH' => ['document' => 'GOVERNANCE-DECISION|2026-08-22|FIDYAH-ALLOCATION|FIDYAH', 'matrix' => 'PAY-FIDYAH|PROGRAM-WILDCARD|FIDYAH'],
        'INFAQ-TROMOL' => ['document' => 'GOVERNANCE-DECISION|2026-08-22|FIDYAH-ALLOCATION|INFAQ-TROMOL', 'matrix' => 'PAY-FIDYAH|PROGRAM-WILDCARD|INFAQ-TROMOL'],
    ];

    private const FACT_TABLES = [
        'allocations' => 'financial_v2_budget_allocations',
        'transactions' => 'financial_v2_transactions',
        'journals' => 'financial_v2_journals',
        'journal_lines' => 'financial_v2_journal_lines',
        'ledger_entries' => 'financial_v2_ledger_entries',
        'vouchers' => 'financial_v2_vouchers',
    ];

    public function __construct(
        private readonly FinancialMasterDataService $masters,
        private readonly MasterDataGovernanceService $governance,
        private readonly FundPolicyCompatibilityService $compatibility,
        private readonly AuditTrailService $auditTrail,
    ) {}

    /** @return array<string, mixed> */
    public function configure(?int $actorUserId, string $origin): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->firstOrFail();
        $factsBefore = $this->factCounts($entity->id);
        $created = [];
        $updated = [];
        $reused = [];

        DB::transaction(function () use ($entity, $actorUserId, $origin, $factsBefore, &$created, &$updated, &$reused): void {
            $payment = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->where('status', 'active')->lockForUpdate()->firstOrFail();
            $postingRule = PostingRule::query()->where('accounting_entity_id', $entity->id)->where('code', 'MRJ-PAY-STANDARD')->where('status', 'active')->lockForUpdate()->firstOrFail();
            $funds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', [...array_keys(self::POLICIES), 'ZAKAT-MAAL'])->lockForUpdate()->get()->keyBy('code');
            foreach ([...array_keys(self::POLICIES), 'ZAKAT-MAAL'] as $fundCode) {
                if (! $funds->has($fundCode)) {
                    throw $this->conflict();
                }
            }

            [$category, $categoryAction] = $this->ensureCategory($entity->id, $payment, $postingRule, $actorUserId);
            if ($categoryAction === 'created') {
                $created[] = 'category:'.self::CATEGORY_CODE;
            } elseif ($categoryAction === 'updated') {
                $updated[] = 'category:'.self::CATEGORY_CODE;
            } else {
                $reused[] = 'category:'.self::CATEGORY_CODE;
            }

            $versions = [];
            foreach (self::POLICIES as $fundCode => $references) {
                [$policy, $action] = $this->ensurePolicy($entity->id, $funds[$fundCode], $payment, $category, $references, $actorUserId);
                $versions[$fundCode] = $policy->version_no;
                if ($action === 'created') {
                    $created[] = "fund-policy:{$fundCode}:v{$policy->version_no}";
                } elseif ($action === 'updated') {
                    $updated[] = "fund-policy:{$fundCode}:v{$policy->version_no}";
                } else {
                    $reused[] = "fund-policy:{$fundCode}:v{$policy->version_no}";
                }
            }

            $resolver = $this->resolverResults($entity->id, $category, $funds);
            if ($resolver !== ['FIDYAH' => 'READY', 'INFAQ-TROMOL' => 'READY', 'ZAKAT-MAAL' => 'INVALID', 'combined' => 'READY']) {
                throw $this->conflict();
            }
            if ($this->factCounts($entity->id) !== $factsBefore) {
                throw new FinancialDomainException('E-FIDYAH-ALLOCATION-FACT-MUTATION', 'Provisioning mencoba mengubah financial fact dan seluruh perubahan dibatalkan.');
            }

            $this->auditTrail->record($entity->id, 'fidyah_allocation_configuration_provisioned', 'accounting_entity', $entity->id, (string) Str::uuid(), $actorUserId,
                ['origin' => $origin, 'facts' => $factsBefore],
                ['origin' => $origin, 'created' => $created, 'updated' => $updated, 'reused' => $reused, 'policy_versions' => $versions, 'resolver' => $resolver]);
        }, 3);

        $factsAfter = $this->factCounts($entity->id);
        if ($factsAfter !== $factsBefore) {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-FACT-MUTATION', 'Provisioning mengubah financial fact.');
        }

        return $this->status() + ['changed' => $created !== [] || $updated !== [], 'created' => $created, 'updated' => $updated, 'reused' => $reused, 'origin' => $origin, 'facts_before' => $factsBefore, 'facts_after' => $factsAfter];
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->first();
        if (! $entity) {
            return ['ready' => false, 'conflict' => false, 'missing' => ['entity:'.self::ENTITY_CODE], 'resolver' => []];
        }
        $payment = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->where('status', 'active')->first();
        $postingRule = PostingRule::query()->where('accounting_entity_id', $entity->id)->where('code', 'MRJ-PAY-STANDARD')->where('status', 'active')->first();
        $funds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', [...array_keys(self::POLICIES), 'ZAKAT-MAAL'])->get()->keyBy('code');
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', self::CATEGORY_CODE)->first();
        $missing = [];
        $conflicts = [];

        if (! $payment || ! $postingRule) {
            $missing[] = 'PAY / MRJ-PAY-STANDARD';
        }
        if (! $category) {
            $missing[] = 'category:'.self::CATEGORY_CODE;
        } elseif (! $payment || ! $postingRule || ! $this->categoryIdentityMatches($category, $payment) || ($this->categoryHasFinancialUsage($category) && ! $this->categoryMatches($category, $payment, $postingRule))) {
            $conflicts[] = 'category:'.self::CATEGORY_CODE;
        } elseif (! $this->categoryMatches($category, $payment, $postingRule)) {
            $missing[] = 'category:'.self::CATEGORY_CODE;
        }
        foreach ([...array_keys(self::POLICIES), 'ZAKAT-MAAL'] as $code) {
            if (! $funds->has($code)) {
                $missing[] = 'fund:'.$code;
            }
        }

        $versions = [];
        if ($payment && $category) {
            foreach (self::POLICIES as $code => $refs) {
                if (! $funds->has($code)) {
                    continue;
                }
                $policy = $this->targetPolicy($funds[$code]);
                if (! $policy) {
                    $missing[] = 'fund-policy:'.$code;
                } elseif ($policy->status === 'draft' && $this->draftPolicyCanBeCompleted($policy)) {
                    $missing[] = 'fund-policy:'.$code;
                } elseif (! $this->policyMatches($policy, $payment, $category) || $this->effectivePolicyCount($funds[$code]) !== 1) {
                    $conflicts[] = 'fund-policy:'.$code;
                } else {
                    $versions[$code] = $policy->version_no;
                }
            }
        }

        $resolver = ($category && $funds->count() === 3) ? $this->resolverResults($entity->id, $category, $funds) : [];
        $ready = $missing === [] && $conflicts === [] && $resolver === ['FIDYAH' => 'READY', 'INFAQ-TROMOL' => 'READY', 'ZAKAT-MAAL' => 'INVALID', 'combined' => 'READY'];

        return ['ready' => $ready, 'conflict' => $conflicts !== [], 'missing' => $missing, 'conflicts' => $conflicts, 'entity_id' => $entity->id,
            'effective_from' => self::EFFECTIVE_FROM, 'category' => self::CATEGORY_CODE, 'program' => null, 'policy_versions' => $versions, 'resolver' => $resolver];
    }

    /** @return array{Category,string} */
    private function ensureCategory(string $entityId, TransactionType $payment, PostingRule $postingRule, ?int $actor): array
    {
        $category = Category::query()->where('accounting_entity_id', $entityId)->where('code', self::CATEGORY_CODE)->lockForUpdate()->first();
        $data = [
            'transaction_type_id' => $payment->id,
            'default_posting_rule_id' => $postingRule->id,
            'code' => self::CATEGORY_CODE,
            'name' => 'Penyaluran Fidyah',
            'status' => 'active',
            'valid_from' => self::EFFECTIVE_FROM,
            'valid_to' => null,
        ];
        if (! $category) {
            return [$this->masters->createCategory($entityId, $data, $actor), 'created'];
        }
        if (! $this->categoryIdentityMatches($category, $payment)
            || ($this->categoryHasFinancialUsage($category) && ! $this->categoryMatches($category, $payment, $postingRule))) {
            throw $this->conflict();
        }
        if (! $this->categoryMatches($category, $payment, $postingRule)) {
            return [$this->masters->updateCategory($entityId, $category->id, $data, $actor), 'updated'];
        }

        return [$category, 'reused'];
    }

    /** @param array{document:string,matrix:string} $refs @return array{FundPolicyVersion,string} */
    private function ensurePolicy(string $entityId, Fund $fund, TransactionType $payment, Category $category, array $refs, ?int $actor): array
    {
        $targets = FundPolicyVersion::query()->where('fund_id', $fund->id)->whereDate('effective_from', self::EFFECTIVE_FROM)->lockForUpdate()->get();
        if ($targets->count() > 1) {
            throw $this->conflict();
        }
        $target = $targets->first();
        $action = 'reused';
        if (! $target) {
            $predecessor = FundPolicyVersion::query()->where('fund_id', $fund->id)->whereIn('status', ['effective', 'superseded'])
                ->whereDate('effective_from', '<=', '2026-08-21')->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', '2026-08-21'))
                ->orderByDesc('effective_from')->firstOrFail();
            $target = $this->masters->createFundPolicyVersion($entityId, ['fund_id' => $fund->id, 'effective_from' => self::EFFECTIVE_FROM, 'effective_to' => null,
                'policy_document_ref' => $refs['document'], 'allowed_matrix_ref' => $refs['matrix'], 'exception_approval_level' => 'financial-governance'], $actor);
            $this->copyRules($entityId, $predecessor, $target, $actor);
            $this->ensureRule($entityId, $target, ['transaction_type_id' => $payment->id, 'account_id' => null, 'category_id' => $category->id, 'program_id' => null,
                'cost_center_id' => null, 'decision' => 'allowed', 'rationale' => 'Diizinkan untuk alokasi dan penyaluran Fidyah tanpa Program berdasarkan keputusan bisnis tanggal 22 Agustus 2026.'], $actor);
            $target = $this->governance->makeFundPolicyVersionEffective($target->id, $actor);
            $action = 'created';
        } elseif ($target->status === 'draft') {
            if (! $this->draftPolicyCanBeCompleted($target)) {
                throw $this->conflict();
            }
            $predecessor = FundPolicyVersion::query()->where('fund_id', $fund->id)->whereIn('status', ['effective', 'superseded'])
                ->whereDate('effective_from', '<=', '2026-08-21')->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', '2026-08-21'))
                ->orderByDesc('effective_from')->firstOrFail();
            $this->copyRules($entityId, $predecessor, $target, $actor);
            $this->ensureRule($entityId, $target, ['transaction_type_id' => $payment->id, 'account_id' => null, 'category_id' => $category->id, 'program_id' => null,
                'cost_center_id' => null, 'decision' => 'allowed', 'rationale' => 'Diizinkan untuk alokasi dan penyaluran Fidyah tanpa Program berdasarkan keputusan bisnis tanggal 22 Agustus 2026.'], $actor);
            $target = $this->governance->makeFundPolicyVersionEffective($target->id, $actor);
            $action = 'updated';
        }
        if (! $this->policyMatches($target, $payment, $category) || $this->effectivePolicyCount($fund) !== 1) {
            throw $this->conflict();
        }

        return [$target, $action];
    }

    private function categoryIdentityMatches(Category $category, TransactionType $payment): bool
    {
        return $category->transaction_type_id === $payment->id && $category->name === 'Penyaluran Fidyah';
    }

    private function categoryHasFinancialUsage(Category $category): bool
    {
        return DB::table('financial_v2_transactions')->where('category_id', $category->id)->exists()
            || DB::table('financial_v2_budget_allocations')->where('category_id', $category->id)->exists();
    }

    private function categoryMatches(Category $c, TransactionType $p, PostingRule $r): bool
    {
        return $c->transaction_type_id === $p->id && $c->default_posting_rule_id === $r->id && $c->name === 'Penyaluran Fidyah' && $c->status === 'active' && $c->valid_from?->toDateString() === self::EFFECTIVE_FROM && $c->valid_to === null;
    }

    private function targetPolicy(Fund $fund): ?FundPolicyVersion
    {
        $targets = FundPolicyVersion::query()->where('fund_id', $fund->id)->whereDate('effective_from', self::EFFECTIVE_FROM)->get();

        return $targets->count() === 1 ? $targets->first() : null;
    }

    private function effectivePolicyCount(Fund $fund): int
    {
        return FundPolicyVersion::query()->where('fund_id', $fund->id)->where('status', 'effective')->whereDate('effective_from', '<=', self::EFFECTIVE_FROM)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', self::EFFECTIVE_FROM))->count();
    }

    private function draftPolicyCanBeCompleted(FundPolicyVersion $policy): bool
    {
        return $policy->effective_from?->toDateString() === self::EFFECTIVE_FROM
            && (! $policy->effective_to || $policy->effective_to->gte(self::EFFECTIVE_FROM))
            && filled($policy->policy_document_ref) && filled($policy->allowed_matrix_ref);
    }

    private function policyMatches(FundPolicyVersion $policy, TransactionType $type, Category $category): bool
    {
        return $policy->status === 'effective' && $policy->effective_from?->lte(self::EFFECTIVE_FROM)
            && (! $policy->effective_to || $policy->effective_to->gte(self::EFFECTIVE_FROM))
            && FundPolicyRule::query()->where('fund_policy_version_id', $policy->id)->where('transaction_type_id', $type->id)->whereNull('account_id')
                ->where('category_id', $category->id)->whereNull('program_id')->whereNull('cost_center_id')->where('decision', 'allowed')->exists();
    }

    private function copyRules(string $entityId, FundPolicyVersion $source, FundPolicyVersion $target, ?int $actor): void
    {
        FundPolicyRule::query()->where('fund_policy_version_id', $source->id)->orderBy('created_at')->each(fn (FundPolicyRule $r) => $this->ensureRule($entityId, $target,
            ['transaction_type_id' => $r->transaction_type_id, 'account_id' => $r->account_id, 'category_id' => $r->category_id, 'program_id' => $r->program_id,
                'cost_center_id' => $r->cost_center_id, 'decision' => $r->decision, 'rationale' => $r->rationale], $actor));
    }

    private function ensureRule(string $entityId, FundPolicyVersion $policy, array $data, ?int $actor): void
    {
        $exists = FundPolicyRule::query()->where('fund_policy_version_id', $policy->id)->where('transaction_type_id', $data['transaction_type_id'])
            ->where(fn ($q) => $this->nullable($q, 'account_id', $data['account_id']))->where(fn ($q) => $this->nullable($q, 'category_id', $data['category_id']))
            ->where(fn ($q) => $this->nullable($q, 'program_id', $data['program_id']))->where(fn ($q) => $this->nullable($q, 'cost_center_id', $data['cost_center_id']))->exists();
        if (! $exists) {
            $this->masters->createFundPolicyRule($entityId, $policy->id, $data, $actor);
        }
    }

    private function nullable($query, string $column, ?string $value): void
    {
        $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }

    private function resolverResults(string $entityId, Category $category, $funds): array
    {
        $resolve = function (array $codes) use ($entityId, $category, $funds): string {
            try {
                $this->compatibility->assertAllocationCompatible($entityId, collect($codes)->map(fn ($code) => $funds[$code]->id), self::EFFECTIVE_FROM, null, $category->id, null);

                return 'READY';
            } catch (Throwable) {
                return 'INVALID';
            }
        };

        return ['FIDYAH' => $resolve(['FIDYAH']), 'INFAQ-TROMOL' => $resolve(['INFAQ-TROMOL']), 'ZAKAT-MAAL' => $resolve(['ZAKAT-MAAL']), 'combined' => $resolve(['FIDYAH', 'INFAQ-TROMOL'])];
    }

    private function factCounts(string $entityId): array
    {
        return collect(self::FACT_TABLES)->mapWithKeys(fn ($table, $name) => [$name => DB::table($table)->where('accounting_entity_id', $entityId)->count()])->all();
    }

    private function conflict(): FinancialDomainException
    {
        return new FinancialDomainException('E-FIDYAH-ALLOCATION-CONFLICT', 'Konfigurasi production berbeda dari configuration yang diharapkan. Perlu pemeriksaan administrator.');
    }
}
