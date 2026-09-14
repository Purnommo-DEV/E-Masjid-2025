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
        $reused = [];

        DB::transaction(function () use ($entity, $actorUserId, $origin, $factsBefore, &$created, &$reused): void {
            $payment = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->where('status', 'active')->lockForUpdate()->firstOrFail();
            $postingRule = PostingRule::query()->where('accounting_entity_id', $entity->id)->where('code', 'MRJ-PAY-STANDARD')->where('status', 'active')->lockForUpdate()->firstOrFail();
            $funds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', [...array_keys(self::POLICIES), 'ZAKAT-MAAL'])->lockForUpdate()->get()->keyBy('code');
            foreach ([...array_keys(self::POLICIES), 'ZAKAT-MAAL'] as $fundCode) {
                if (! $funds->has($fundCode)) {
                    throw $this->conflict();
                }
            }

            $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', self::CATEGORY_CODE)->lockForUpdate()->first();
            if (! $category) {
                $category = $this->masters->createCategory($entity->id, [
                    'transaction_type_id' => $payment->id,
                    'default_posting_rule_id' => $postingRule->id,
                    'code' => self::CATEGORY_CODE,
                    'name' => 'Penyaluran Fidyah',
                    'status' => 'active',
                    'valid_from' => self::EFFECTIVE_FROM,
                    'valid_to' => null,
                ], $actorUserId);
                $created[] = 'category:'.self::CATEGORY_CODE;
            } else {
                $this->assertCategory($category, $payment, $postingRule);
                $reused[] = 'category:'.self::CATEGORY_CODE;
            }

            $versions = [];
            foreach (self::POLICIES as $fundCode => $references) {
                [$policy, $wasCreated] = $this->ensurePolicy($entity->id, $funds[$fundCode], $payment, $category, $references, $actorUserId);
                $versions[$fundCode] = $policy->version_no;
                if ($wasCreated) {
                    $created[] = "fund-policy:{$fundCode}:v{$policy->version_no}";
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
                ['origin' => $origin, 'created' => $created, 'reused' => $reused, 'policy_versions' => $versions, 'resolver' => $resolver]);
        }, 3);

        $factsAfter = $this->factCounts($entity->id);
        if ($factsAfter !== $factsBefore) {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-FACT-MUTATION', 'Provisioning mengubah financial fact.');
        }

        return $this->status() + ['changed' => $created !== [], 'created' => $created, 'reused' => $reused, 'origin' => $origin, 'facts_before' => $factsBefore, 'facts_after' => $factsAfter];
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
        } elseif (! $payment || ! $postingRule || ! $this->categoryMatches($category, $payment, $postingRule)) {
            $conflicts[] = 'category:'.self::CATEGORY_CODE;
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
                $policy = $this->targetPolicy($funds[$code], $refs);
                if (! $policy) {
                    if (FundPolicyVersion::query()->where('fund_id', $funds[$code]->id)->whereDate('effective_from', self::EFFECTIVE_FROM)->exists()) {
                        $conflicts[] = 'fund-policy:'.$code;
                    } else {
                        $missing[] = 'fund-policy:'.$code;
                    }
                } elseif (! $this->policyMatches($policy, $payment, $category, $refs) || $this->effectivePolicyCount($funds[$code]) !== 1) {
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

    /** @param array{document:string,matrix:string} $refs @return array{FundPolicyVersion,bool} */
    private function ensurePolicy(string $entityId, Fund $fund, TransactionType $payment, Category $category, array $refs, ?int $actor): array
    {
        $target = $this->targetPolicy($fund, $refs);
        if (FundPolicyVersion::query()->where('fund_id', $fund->id)->whereDate('effective_from', self::EFFECTIVE_FROM)->when($target, fn ($q) => $q->whereKeyNot($target->id))->exists()) {
            throw $this->conflict();
        }
        $created = false;
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
            $created = true;
        }
        if (! $this->policyMatches($target, $payment, $category, $refs) || $this->effectivePolicyCount($fund) !== 1) {
            throw $this->conflict();
        }

        return [$target, $created];
    }

    private function assertCategory(Category $category, TransactionType $payment, PostingRule $rule): void
    {
        if (! $this->categoryMatches($category, $payment, $rule)) {
            throw $this->conflict();
        }
    }

    private function categoryMatches(Category $c, TransactionType $p, PostingRule $r): bool
    {
        return $c->transaction_type_id === $p->id && $c->default_posting_rule_id === $r->id && $c->name === 'Penyaluran Fidyah' && $c->status === 'active' && $c->valid_from?->toDateString() === self::EFFECTIVE_FROM && $c->valid_to === null;
    }

    private function targetPolicy(Fund $fund, array $refs): ?FundPolicyVersion
    {
        return FundPolicyVersion::query()->where('fund_id', $fund->id)->whereDate('effective_from', self::EFFECTIVE_FROM)->where('policy_document_ref', $refs['document'])->first();
    }

    private function effectivePolicyCount(Fund $fund): int
    {
        return FundPolicyVersion::query()->where('fund_id', $fund->id)->where('status', 'effective')->whereDate('effective_from', '<=', self::EFFECTIVE_FROM)->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', self::EFFECTIVE_FROM))->count();
    }

    private function policyMatches(FundPolicyVersion $p, TransactionType $type, Category $category, array $refs): bool
    {
        return $p->status === 'effective' && $p->effective_from?->toDateString() === self::EFFECTIVE_FROM && $p->effective_to === null
            && $p->policy_document_ref === $refs['document'] && $p->allowed_matrix_ref === $refs['matrix']
            && FundPolicyRule::query()->where('fund_policy_version_id', $p->id)->where('transaction_type_id', $type->id)->whereNull('account_id')
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
