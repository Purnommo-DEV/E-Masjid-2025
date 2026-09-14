<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\FundPolicyCompatibilityService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Provisions the approved PAY Fidyah allocation configuration for FIDYAH and
 * INFAQ-TROMOL. It creates configuration only and never creates an allocation
 * or any Financial V2 fact.
 */
final class ConfigureMrjFidyahAllocationCommand extends Command
{
    private const EFFECTIVE_FROM = '2026-08-22';

    private const CATEGORY_CODE = 'PAY-FIDYAH';

    /** @var array<string, array{0:string,1:string}> */
    private const FUND_POLICY_REFERENCES = [
        'FIDYAH' => [
            'GOVERNANCE-DECISION|2026-08-22|FIDYAH-ALLOCATION|FIDYAH',
            'PAY-FIDYAH|PROGRAM-WILDCARD|FIDYAH',
        ],
        'INFAQ-TROMOL' => [
            'GOVERNANCE-DECISION|2026-08-22|FIDYAH-ALLOCATION|INFAQ-TROMOL',
            'PAY-FIDYAH|PROGRAM-WILDCARD|INFAQ-TROMOL',
        ],
    ];

    protected $signature = 'financial-v2:configure-mrj-fidyah-allocation
                            {--apply : Persist the governed category and successor policies; without this flag the command is read-only}
                            {--allow-testing : Permit execution only under testing on mrj_test_db}';

    protected $description = 'Configure PAY-FIDYAH allocation policy for Dana Fidyah and Dana Infaq & Tromol without creating financial facts.';

    public function handle(
        FinancialMasterDataService $masters,
        MasterDataGovernanceService $governance,
        FundPolicyCompatibilityService $compatibility,
    ): int {
        $this->assertSafeDatabase();

        $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->where('status', 'active')->firstOrFail();
        $payment = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->where('status', 'active')->firstOrFail();
        $postingRule = PostingRule::query()->where('accounting_entity_id', $entity->id)->where('code', 'MRJ-PAY-STANDARD')->where('status', 'active')->firstOrFail();
        $funds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', array_keys(self::FUND_POLICY_REFERENCES))->get()->keyBy('code');
        foreach (array_keys(self::FUND_POLICY_REFERENCES) as $fundCode) {
            if (! $funds->has($fundCode)) {
                throw new FinancialDomainException('E-FIDYAH-ALLOCATION-CONFIGURATION', "Dana {$fundCode} tidak ditemukan.");
            }
        }

        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', self::CATEGORY_CODE)->first();
        $before = $this->financialFactSnapshot($entity->id);

        $this->table(['Konfigurasi', 'Status'], [
            ['Database', DB::connection()->getDatabaseName()],
            ['Tanggal berlaku', self::EFFECTIVE_FROM],
            ['Kategori PAY', $category ? "{$category->code} · {$category->status}" : 'belum ada'],
            ['Dana', 'FIDYAH + INFAQ-TROMOL'],
            ['Program', 'Tanpa Program (wildcard)'],
            ['ZAKAT-MAAL', 'tidak dikonfigurasi'],
        ]);

        if (! $this->option('apply')) {
            $this->info('Dry-run selesai. Tidak ada category, policy, alokasi, transaksi, Journal, JournalLine, Ledger, atau voucher yang diubah.');

            return self::SUCCESS;
        }

        $actorId = User::query()->orderBy('id')->value('id');
        [$category, $policies] = DB::transaction(function () use ($masters, $governance, $entity, $payment, $postingRule, $funds, $category, $actorId): array {
            $category = $this->ensureCategory($masters, $entity->id, $payment, $postingRule, $category, $actorId);
            $policies = [];
            foreach (self::FUND_POLICY_REFERENCES as $fundCode => [$document, $matrix]) {
                $policies[$fundCode] = $this->ensureSuccessorPolicy(
                    $masters,
                    $governance,
                    $entity->id,
                    $funds->get($fundCode),
                    $payment,
                    $category,
                    $document,
                    $matrix,
                    $actorId,
                );
            }

            return [$category, $policies];
        }, 3);

        $fundIds = [];
        foreach (array_keys(self::FUND_POLICY_REFERENCES) as $fundCode) {
            $fundId = $funds->get($fundCode)->id;
            $compatibility->assertAllocationCompatible($entity->id, [$fundId], self::EFFECTIVE_FROM, null, $category->id, null);
            $fundIds[] = $fundId;
            $this->line("READY: {$fundCode} → ".self::CATEGORY_CODE.' → Tanpa Program');
        }
        $compatibility->assertAllocationCompatible($entity->id, $fundIds, self::EFFECTIVE_FROM, null, $category->id, null);
        $this->line('READY: FIDYAH + INFAQ-TROMOL → '.self::CATEGORY_CODE.' → Tanpa Program');

        if ($before !== $this->financialFactSnapshot($entity->id)) {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-FACT-MUTATION', 'Configuration command changed a Financial V2 fact.');
        }

        $this->info('Konfigurasi alokasi Fidyah siap. Tidak ada alokasi atau financial fact yang dibuat. Policy: '.collect($policies)->map(fn (FundPolicyVersion $policy, string $code) => "{$code} v{$policy->version_no}")->implode(', ').'.');

        return self::SUCCESS;
    }

    private function ensureCategory(FinancialMasterDataService $masters, string $entityId, TransactionType $payment, PostingRule $postingRule, ?Category $category, ?int $actorId): Category
    {
        if (! $category) {
            return $masters->createCategory($entityId, [
                'transaction_type_id' => $payment->id,
                'default_posting_rule_id' => $postingRule->id,
                'code' => self::CATEGORY_CODE,
                'name' => 'Penyaluran Fidyah',
                'status' => 'active',
                'valid_from' => self::EFFECTIVE_FROM,
                'valid_to' => null,
            ], $actorId);
        }

        if ($category->transaction_type_id !== $payment->id
            || $category->default_posting_rule_id !== $postingRule->id
            || $category->name !== 'Penyaluran Fidyah'
            || $category->status !== 'active'
            || $category->valid_from?->toDateString() !== self::EFFECTIVE_FROM
            || $category->valid_to !== null) {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-CONFIGURATION', 'Existing PAY-FIDYAH category conflicts with the approved configuration.');
        }

        return $category;
    }

    private function ensureSuccessorPolicy(
        FinancialMasterDataService $masters,
        MasterDataGovernanceService $governance,
        string $entityId,
        Fund $fund,
        TransactionType $payment,
        Category $category,
        string $document,
        string $matrix,
        ?int $actorId,
    ): FundPolicyVersion {
        $target = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('effective_from', self::EFFECTIVE_FROM)
            ->where('policy_document_ref', $document)
            ->first();
        $predecessor = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('effective_from', '<', self::EFFECTIVE_FROM)
            ->orderByDesc('effective_from')
            ->firstOrFail();

        $successor = $target ?: $masters->createFundPolicyVersion($entityId, [
            'fund_id' => $fund->id,
            'effective_from' => self::EFFECTIVE_FROM,
            'effective_to' => null,
            'policy_document_ref' => $document,
            'allowed_matrix_ref' => $matrix,
            'exception_approval_level' => 'financial-governance',
        ], $actorId);

        if (! in_array($successor->status, ['draft', 'effective'], true)
            || $successor->allowed_matrix_ref !== $matrix
            || $successor->effective_to !== null) {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-CONFIGURATION', "Successor policy {$fund->code} conflicts with the approved configuration.");
        }

        if ($successor->status === 'draft') {
            $this->copyPolicyRules($masters, $entityId, $predecessor, $successor, $actorId);
            $this->ensureRule($masters, $entityId, $successor, [
                'transaction_type_id' => $payment->id,
                'account_id' => null,
                'category_id' => $category->id,
                'program_id' => null,
                'cost_center_id' => null,
                'decision' => 'allowed',
                'rationale' => 'Diizinkan untuk alokasi dan penyaluran Fidyah tanpa Program berdasarkan keputusan bisnis tanggal 22 Agustus 2026.',
            ], $actorId);
            $successor = $governance->makeFundPolicyVersionEffective($successor->id, $actorId);
        }

        $matches = FundPolicyRule::query()
            ->where('fund_policy_version_id', $successor->id)
            ->where('transaction_type_id', $payment->id)
            ->whereNull('account_id')
            ->where('category_id', $category->id)
            ->whereNull('program_id')
            ->whereNull('cost_center_id')
            ->where('decision', 'allowed')
            ->exists();
        if ($successor->status !== 'effective' || ! $matches || $predecessor->fresh()->status === 'effective') {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-CONFIGURATION', "Policy {$fund->code} tidak menjadi successor efektif yang valid.");
        }

        return $successor;
    }

    private function copyPolicyRules(FinancialMasterDataService $masters, string $entityId, FundPolicyVersion $source, FundPolicyVersion $target, ?int $actorId): void
    {
        FundPolicyRule::query()->where('fund_policy_version_id', $source->id)->orderBy('created_at')
            ->each(fn (FundPolicyRule $rule) => $this->ensureRule($masters, $entityId, $target, [
                'transaction_type_id' => $rule->transaction_type_id,
                'account_id' => $rule->account_id,
                'category_id' => $rule->category_id,
                'program_id' => $rule->program_id,
                'cost_center_id' => $rule->cost_center_id,
                'decision' => $rule->decision,
                'rationale' => $rule->rationale,
            ], $actorId));
    }

    /** @param array<string, mixed> $data */
    private function ensureRule(FinancialMasterDataService $masters, string $entityId, FundPolicyVersion $policy, array $data, ?int $actorId): void
    {
        $exists = FundPolicyRule::query()
            ->where('fund_policy_version_id', $policy->id)
            ->where('transaction_type_id', $data['transaction_type_id'])
            ->where(fn ($query) => $this->whereNullable($query, 'account_id', $data['account_id']))
            ->where(fn ($query) => $this->whereNullable($query, 'category_id', $data['category_id']))
            ->where(fn ($query) => $this->whereNullable($query, 'program_id', $data['program_id']))
            ->where(fn ($query) => $this->whereNullable($query, 'cost_center_id', $data['cost_center_id']))
            ->exists();
        if (! $exists) {
            $masters->createFundPolicyRule($entityId, $policy->id, $data, $actorId);
        }
    }

    private function whereNullable($query, string $column, ?string $value): void
    {
        $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }

    /** @return array<string, int> */
    private function financialFactSnapshot(string $entityId): array
    {
        return [
            'transactions' => FinancialTransaction::query()->where('accounting_entity_id', $entityId)->count(),
            'journals' => Journal::query()->where('accounting_entity_id', $entityId)->count(),
            'journal_lines' => JournalLine::query()->where('accounting_entity_id', $entityId)->count(),
            'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $entityId)->count(),
            'vouchers' => Voucher::query()->where('accounting_entity_id', $entityId)->count(),
        ];
    }

    private function assertSafeDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        $actualDatabase = in_array($database, ['raudhotu_mrj_db', 'mrj_prod_db', 'mrj_prod_new'], true);
        $actualEnvironment = app()->environment(['local', 'development', 'production']) && $actualDatabase;
        $safeTesting = app()->environment('testing') && $database === 'mrj_test_db' && (bool) $this->option('allow-testing');
        if (! $actualEnvironment && ! $safeTesting) {
            throw new FinancialDomainException('E-FIDYAH-ALLOCATION-ENVIRONMENT', 'Command hanya dapat dijalankan pada database MRJ aktual lokal atau testing mrj_test_db dengan --allow-testing.');
        }
    }
}
