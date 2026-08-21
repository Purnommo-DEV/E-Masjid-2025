<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Creates an explicitly labelled, idempotent local-only Financial V2 QA
 * context. It is deliberately additive: it never deletes or truncates data,
 * and all accounting facts are created through the lifecycle and PostingEngine.
 */
final class ProvisionFinancialV2LocalQaCommand extends Command
{
    protected $signature = 'financial-v2:provision-local-qa
                            {--with-sample-transactions : Create posted SAMPLE/QA operational scenarios}';

    protected $description = 'Provision non-destructive SAMPLE/QA Financial V2 masters for local development only.';

    /** @var array<string, Account> */
    private array $accounts = [];

    /** @var array<string, FinancialAccount> */
    private array $financialAccounts = [];

    /** @var array<string, Fund> */
    private array $funds = [];

    /** @var array<string, Program> */
    private array $programs = [];

    /** @var array<string, Category> */
    private array $categories = [];

    /** @var array<string, TransactionType> */
    private array $types = [];

    /** @var array<string, Counterparty> */
    private array $counterparties = [];

    private int $actorUserId;

    private AccountingEntity $entity;

    private FinancialMasterDataService $masters;

    private MasterDataGovernanceService $governance;

    private FinancialTransactionLifecycleService $lifecycle;

    private BudgetAllocationService $allocations;

    private EvidenceService $evidence;

    public function handle(
        FinancialMasterDataService $masters,
        MasterDataGovernanceService $governance,
        FinancialTransactionLifecycleService $lifecycle,
        BudgetAllocationService $allocations,
        EvidenceService $evidence,
    ): int {
        if (! app()->environment('local')) {
            $this->error('Refusing to provision SAMPLE/QA Financial V2 data outside the local environment.');

            return self::FAILURE;
        }

        if ((string) config('database.connections.'.config('database.default').'.database') !== 'mrj_prod_db') {
            $this->error('Refusing to run: the local development target must be mrj_prod_db.');

            return self::FAILURE;
        }

        $this->masters = $masters;
        $this->governance = $governance;
        $this->lifecycle = $lifecycle;
        $this->allocations = $allocations;
        $this->evidence = $evidence;
        $this->actorUserId = $this->localQaUser()->id;

        $this->entity = AccountingEntity::query()->firstOrCreate(
            ['code' => 'MRJ-SAMPLE-QA'],
            [
                'name' => 'Masjid Raudhotul Jannah — SAMPLE/QA',
                'legal_name' => 'Masjid Raudhotul Jannah — SAMPLE/QA (Local Development)',
                'functional_currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'fiscal_year_start_month' => 1,
                'status' => 'active',
                'created_by_user_id' => $this->actorUserId,
                'updated_by_user_id' => $this->actorUserId,
            ],
        );

        $this->ensureCalendarAndOpenPeriods();
        $this->ensureSupportingAccounts();
        $this->ensureTransactionTypes();
        $this->ensureFinancialAccounts();
        $this->retireUnusedSampleLiquidityAccounts();
        $this->ensurePrograms();
        $this->ensureCategoriesAndCounterparties();
        $this->ensureFundsAndPolicies();
        $this->ensurePostingRulesAndSequences();

        if ($this->option('with-sample-transactions')) {
            $this->ensureSampleTransactions();
        }

        $this->newLine();
        $this->info('Financial V2 local SAMPLE/QA provisioning completed without destructive operations.');
        $this->table(['Master/fact', 'Count'], [
            ['Financial accounts', FinancialAccount::query()->where('accounting_entity_id', $this->entity->id)->count()],
            ['Funds', Fund::query()->where('accounting_entity_id', $this->entity->id)->count()],
            ['Programs', Program::query()->where('accounting_entity_id', $this->entity->id)->count()],
            ['Categories', Category::query()->where('accounting_entity_id', $this->entity->id)->count()],
            ['Posted transactions', FinancialTransaction::query()->where('accounting_entity_id', $this->entity->id)->where('status', 'posted')->count()],
        ]);

        return self::SUCCESS;
    }

    private function localQaUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'superadmin@emasjid.com'],
            [
                'name' => 'Super Admin (Local QA)',
                'password' => Hash::make('password'),
            ],
        );
    }

    private function ensureCalendarAndOpenPeriods(): void
    {
        $year = (int) now()->year;
        $calendar = AccountingCalendar::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => "QA-{$year}"],
            [
                'name' => "Kalender SAMPLE/QA {$year}",
                'fiscal_year_label' => "QA-{$year}",
                'start_date' => CarbonImmutable::create($year, 1, 1)->toDateString(),
                'end_date' => CarbonImmutable::create($year, 12, 31)->toDateString(),
                'status' => 'active',
                'created_by_user_id' => $this->actorUserId,
                'updated_by_user_id' => $this->actorUserId,
            ],
        );

        foreach (range(1, 12) as $month) {
            $start = CarbonImmutable::create($year, $month, 1);
            AccountingPeriod::query()->firstOrCreate(
                ['accounting_calendar_id' => $calendar->id, 'period_no' => $month],
                [
                    'accounting_entity_id' => $this->entity->id,
                    'period_name' => 'SAMPLE/QA '.$start->translatedFormat('F Y'),
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->endOfMonth()->toDateString(),
                    'status' => 'open',
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    private function ensureSupportingAccounts(): void
    {
        $groups = [];
        foreach ([
            'AST' => ['Aset Likuiditas SAMPLE/QA', 'asset'],
            'REV' => ['Penerimaan SAMPLE/QA', 'revenue'],
            'EXP' => ['Penggunaan Dana SAMPLE/QA', 'expense'],
            'TRF' => ['Transfer Dana SAMPLE/QA', 'transfer'],
        ] as $code => [$name, $class]) {
            $groups[$code] = AccountGroup::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                [
                    'name' => $name,
                    'group_class' => $class,
                    'status' => 'active',
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }

        $definitions = [
            // A FinancialAccount carries bank/cash identity. One liquidity GL
            // account can therefore back several custody accounts safely.
            'LIQ-01' => ['Likuiditas Kas dan Bank Internal SAMPLE/QA', 'AST', 'asset', 'debit', true],
            'REV-01' => ['Penerimaan Dana Internal SAMPLE/QA', 'REV', 'revenue', 'credit', false],
            'EXP-01' => ['Penggunaan Dana Internal SAMPLE/QA', 'EXP', 'expense', 'debit', false],
            'IFT-IN' => ['Transfer Dana Masuk Internal SAMPLE/QA', 'TRF', 'transfer', 'debit', false],
            'IFT-OUT' => ['Transfer Dana Keluar Internal SAMPLE/QA', 'TRF', 'transfer', 'credit', false],
        ];

        foreach ($definitions as $code => [$name, $group, $class, $normal, $liquidity]) {
            $this->accounts[$code] = Account::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                [
                    'account_group_id' => $groups[$group]->id,
                    'name' => $name,
                    'account_class' => $class,
                    'normal_balance' => $normal,
                    'is_posting_account' => true,
                    'is_liquidity_account' => $liquidity,
                    'is_control_account' => false,
                    'allow_manual_posting' => false,
                    'status' => 'active',
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    private function ensureTransactionTypes(): void
    {
        foreach ([
            'RCV' => ['Penerimaan', 'RCV'],
            'PAY' => ['Pengeluaran', 'PAY'],
            'TRF' => ['Transfer Kas/Rekening', 'TRF'],
            'IFT' => ['Transfer Antar Dana', 'IFT'],
        ] as $code => [$name, $prefix]) {
            $this->types[$code] = TransactionType::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                [
                    'name' => $name,
                    'voucher_prefix' => $prefix,
                    'has_financial_impact' => true,
                    'status' => 'active',
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    private function ensureFinancialAccounts(): void
    {
        $definitions = [
            'BNK-OPS' => ['Bank Operasional Masjid — SAMPLE/QA', 'bank', 'BNI SAMPLE/QA', '****1001'],
            'BNK-ZIS' => ['BNI ZISWAF — SAMPLE/QA', 'bank', 'BNI SAMPLE/QA', '****1002'],
            'BNK-SOS' => ['Bank Sosial/Kematian — SAMPLE/QA', 'bank', 'BNI SAMPLE/QA', '****1003'],
            'BNK-AULA' => ['Bank Sewa Aula — SAMPLE/QA', 'bank', 'BNI SAMPLE/QA', '****1004'],
            'BNK-QUR' => ['Bank Qurban — SAMPLE/QA', 'bank', 'BNI SAMPLE/QA', '****1005'],
            'CSH-OPS' => ['Cash Operasional — SAMPLE/QA', 'cash', 'Kas Bendahara Operasional', 'daily'],
            'CSH-ZIS' => ['Cash ZISWAF — SAMPLE/QA', 'cash', 'Kas Tim ZISWAF', 'daily'],
            'CSH-SOS' => ['Cash Sosial — SAMPLE/QA', 'cash', 'Kas Tim Sosial', 'weekly'],
            'CSH-QUR' => ['Cash Qurban — SAMPLE/QA', 'cash', 'Kas Panitia Qurban', 'daily'],
        ];

        foreach ($definitions as $code => [$name, $type, $detail, $extra]) {
            $financial = FinancialAccount::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $financial) {
                $data = [
                    'account_id' => $this->accounts['LIQ-01']->id,
                    'code' => $code,
                    'name' => $name,
                    'account_type' => $type,
                    'currency_code' => 'IDR',
                    'opening_date' => now()->startOfYear()->toDateString(),
                ];
                $data += $type === 'bank'
                    ? ['bank_name' => $detail, 'account_number_masked' => $extra]
                    : ['cash_location' => $detail, 'cash_count_frequency' => $extra];
                $financial = $this->masters->createFinancialAccount($this->entity->id, $data, $this->actorUserId);
            }
            if ($financial->status === 'draft') {
                $financial = $this->governance->activateFinancialAccount($financial->id, now()->toDateString(), $this->actorUserId);
            }
            $this->financialAccounts[$code] = $financial->fresh();
        }
    }

    /**
     * Corrects only obsolete internal SAMPLE/QA setup records made by an
     * earlier local run. They are neither referenced by a FinancialAccount nor
     * by a posted fact; deactivation preserves their auditability and avoids
     * offering an incompatible GL mapping in the operator-facing master form.
     */
    private function retireUnusedSampleLiquidityAccounts(): void
    {
        foreach (['BNK-OPS', 'BNK-ZIS', 'BNK-SOS', 'BNK-AULA', 'BNK-QUR', 'CSH-OPS', 'CSH-ZIS', 'CSH-SOS', 'CSH-QUR'] as $code) {
            $account = Account::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $account || FinancialAccount::query()->where('account_id', $account->id)->exists() || DB::table('financial_v2_journal_lines')->where('account_id', $account->id)->exists()) {
                continue;
            }
            if ($account->status !== 'inactive' || $account->is_liquidity_account) {
                $account->update(['status' => 'inactive', 'is_liquidity_account' => false, 'updated_by_user_id' => $this->actorUserId]);
            }
        }
    }

    private function ensurePrograms(): void
    {
        foreach ([
            'OPS' => 'Operasional Masjid — SAMPLE/QA',
            'DHUAFA' => 'Santunan Dhuafa — SAMPLE/QA',
            'YATIM' => 'Santunan Anak Yatim Bulanan — SAMPLE/QA',
            'QURBAN' => 'Qurban — SAMPLE/QA',
            'RAMADHAN' => 'Ramadhan — SAMPLE/QA',
            'SOSIAL' => 'Sosial & Kematian — SAMPLE/QA',
            'AULA' => 'Sewa Aula — SAMPLE/QA',
        ] as $code => $name) {
            $program = Program::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $program) {
                $program = $this->masters->createProgram($this->entity->id, [
                    'code' => $code,
                    'name' => $name,
                    'start_date' => now()->startOfYear()->toDateString(),
                    'end_date' => now()->addYear()->endOfYear()->toDateString(),
                    'program_owner_reference' => 'local-qa',
                ], $this->actorUserId);
            }
            if ($program->status === 'draft') {
                $program = $this->governance->activateProgram($program->id, $this->actorUserId);
            }
            $this->programs[$code] = $program->fresh();
        }
    }

    private function ensureCategoriesAndCounterparties(): void
    {
        foreach ([
            'RCV' => ['Penerimaan Dana — SAMPLE/QA', 'RCV'],
            'OPS' => ['Biaya Operasional — SAMPLE/QA', 'PAY'],
            'SANTUNAN' => ['Santunan — SAMPLE/QA', 'PAY'],
            'QURBAN' => ['Biaya Qurban — SAMPLE/QA', 'PAY'],
            'RAMADHAN' => ['Kegiatan Ramadhan — SAMPLE/QA', 'PAY'],
            'SOSIAL' => ['Sosial/Kematian — SAMPLE/QA', 'PAY'],
            'AULA' => ['Biaya Sewa Aula — SAMPLE/QA', 'PAY'],
        ] as $code => [$name, $type]) {
            $category = Category::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $category) {
                $category = $this->masters->createCategory($this->entity->id, [
                    'transaction_type_id' => $this->types[$type]->id,
                    'code' => $code,
                    'name' => $name,
                    'status' => 'active',
                    'valid_from' => now()->startOfYear()->toDateString(),
                ], $this->actorUserId);
            }
            $this->categories[$code] = $category->fresh();
        }

        foreach ([
            'DONOR' => ['donor', 'Donatur SAMPLE/QA'],
            'SUPPLIER' => ['supplier', 'Pemasok SAMPLE/QA'],
            'BENEFICIARY' => ['beneficiary', 'Penerima Santunan SAMPLE/QA'],
        ] as $code => [$type, $name]) {
            $this->counterparties[$code] = Counterparty::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                [
                    'party_type' => $type,
                    'display_name' => $name,
                    'status' => 'active',
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    private function ensureFundsAndPolicies(): void
    {
        $unrestricted = FundType::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'UNRESTRICTED')->first()
            ?? $this->masters->createFundType($this->entity->id, ['code' => 'UNRESTRICTED', 'name' => 'Dana Tidak Terikat', 'classification' => 'unrestricted', 'status' => 'active', 'valid_from' => now()->startOfYear()->toDateString()], $this->actorUserId);
        $restricted = FundType::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'RESTRICTED')->first()
            ?? $this->masters->createFundType($this->entity->id, ['code' => 'RESTRICTED', 'name' => 'Dana Terikat', 'classification' => 'restricted', 'status' => 'active', 'valid_from' => now()->startOfYear()->toDateString()], $this->actorUserId);
        $generalRestriction = $this->restriction('GENERAL', 'Tidak Terikat', 'low', $unrestricted);
        $restrictedRule = $this->restriction('RESTRICTED', 'Terikat sesuai Aturan Dana', 'high', $restricted);

        $definitions = [
            'OPS' => ['Dana Operasional Masjid — SAMPLE/QA', $unrestricted, $generalRestriction, null],
            'ZAKAT' => ['Dana Zakat Maal — SAMPLE/QA', $restricted, $restrictedRule, 'DHUAFA'],
            'INFAQ' => ['Dana Infaq/Tromol — SAMPLE/QA', $restricted, $restrictedRule, 'OPS'],
            'SODAQOH' => ['Dana Sodaqoh — SAMPLE/QA', $restricted, $restrictedRule, 'OPS'],
            'FIDYAH' => ['Dana Fidyah — SAMPLE/QA', $restricted, $restrictedRule, 'DHUAFA'],
            'DHUAFA' => ['Dana Dhuafa — SAMPLE/QA', $restricted, $restrictedRule, 'DHUAFA'],
            'YATIM' => ['Dana Santunan Anak Yatim — SAMPLE/QA', $restricted, $restrictedRule, 'YATIM'],
            'QURBAN' => ['Dana Qurban — SAMPLE/QA', $restricted, $restrictedRule, 'QURBAN'],
            'RAMADHAN' => ['Dana Ramadhan — SAMPLE/QA', $restricted, $restrictedRule, 'RAMADHAN'],
            'SOSIAL' => ['Dana Sosial/Kematian — SAMPLE/QA', $restricted, $restrictedRule, 'SOSIAL'],
            'SEWA-AULA' => ['Dana Sewa Aula — SAMPLE/QA', $restricted, $restrictedRule, 'AULA'],
        ];

        foreach ($definitions as $code => [$name, $type, $restriction, $programCode]) {
            $fund = Fund::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $fund) {
                $fund = $this->masters->createFund($this->entity->id, [
                    'fund_type_id' => $type->id,
                    'fund_restriction_id' => $restriction->id,
                    'code' => $code,
                    'name' => $name,
                    'purpose_statement' => 'SAMPLE/QA only. This master defines a separate fund dimension and is not a bank balance.',
                    'prohibited_use_statement' => $programCode ? 'Only uses allowed by the effective SAMPLE/QA fund policy are permitted.' : null,
                    'allow_negative_balance' => false,
                ], $this->actorUserId);
            }

            if ($type->classification === 'restricted') {
                $this->ensureRestrictedPolicy($fund, $programCode);
            }
            if ($fund->status === 'draft') {
                $fund = $this->governance->activateFund($fund->id, now()->startOfYear()->toDateString(), $this->actorUserId);
            }
            $this->funds[$code] = $fund->fresh();
        }
    }

    private function restriction(string $code, string $name, string $severity, FundType $type): FundRestriction
    {
        $restriction = FundRestriction::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
        if (! $restriction) {
            $restriction = $this->masters->createFundRestriction($this->entity->id, [
                'fund_type_id' => $type->id,
                'code' => $code,
                'name' => $name,
                'severity' => $severity,
                'policy_basis' => 'Local SAMPLE/QA policy. Real operating policy must be configured separately.',
                'status' => 'active',
                'valid_from' => now()->startOfYear()->toDateString(),
            ], $this->actorUserId);
        }

        return $restriction->fresh();
    }

    private function ensureRestrictedPolicy(Fund $fund, ?string $programCode): void
    {
        $policy = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('version_no', 1)->first();
        if (! $policy) {
            $policy = $this->masters->createFundPolicyVersion($this->entity->id, [
                'fund_id' => $fund->id,
                'effective_from' => now()->startOfYear()->toDateString(),
                'policy_document_ref' => 'LOCAL-SAMPLE-QA-POLICY-'.strtoupper($fund->code),
                'allowed_matrix_ref' => 'LOCAL-SAMPLE-QA-MATRIX-'.strtoupper($fund->code),
                'exception_approval_level' => 'local-qa-only',
            ], $this->actorUserId);
        }

        if ($policy->status === 'draft') {
            $allowed = [
                ['transaction_type_id' => $this->types['RCV']->id],
                ['transaction_type_id' => $this->types['TRF']->id],
                ['transaction_type_id' => $this->types['IFT']->id],
                ['transaction_type_id' => $this->types['PAY']->id, 'program_id' => $programCode ? $this->programs[$programCode]->id : null],
            ];
            foreach ($allowed as $rule) {
                $this->masters->createFundPolicyRule($this->entity->id, $policy->id, $rule + [
                    'decision' => 'allowed',
                    'rationale' => 'Local SAMPLE/QA permitted-use matrix.',
                ], $this->actorUserId);
            }
            $this->governance->makeFundPolicyVersionEffective($policy->id, $this->actorUserId);
        }
    }

    private function ensurePostingRulesAndSequences(): void
    {
        $this->ensurePostingRule('RCV', 'receipt', [
            ['account_id' => $this->accounts['LIQ-01']->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split', 'program_source' => 'split'],
            ['account_id' => $this->accounts['REV-01']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'program_source' => 'split', 'category_source' => 'transaction'],
        ]);
        $this->ensurePostingRule('PAY', 'payment', [
            ['account_id' => $this->accounts['EXP-01']->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'program_source' => 'split', 'category_source' => 'transaction', 'counterparty_source' => 'transaction'],
            ['account_id' => $this->accounts['LIQ-01']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split', 'program_source' => 'split'],
        ]);
        $this->ensurePostingRule('TRF', 'treasury-transfer', [
            ['account_id' => $this->accounts['LIQ-01']->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_destination', 'fund_source' => 'split'],
            ['account_id' => $this->accounts['LIQ-01']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_source', 'fund_source' => 'split'],
        ]);
        $this->ensurePostingRule('IFT', 'interfund-transfer', [
            ['account_id' => $this->accounts['IFT-IN']->id, 'entry_side' => 'debit', 'amount_source' => 'transaction_gross_amount', 'fund_source' => 'interfund_destination'],
            ['account_id' => $this->accounts['IFT-OUT']->id, 'entry_side' => 'credit', 'amount_source' => 'transaction_gross_amount', 'fund_source' => 'interfund_source'],
        ]);

        foreach ($this->types as $code => $type) {
            DocumentSequence::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => "QA-{$code}"],
                [
                    'transaction_type_id' => $type->id,
                    'name' => "Voucher {$type->name} SAMPLE/QA",
                    'prefix' => $type->voucher_prefix,
                    'scope_key' => 'local-sample-qa-'.$code,
                    'next_value' => 1,
                    'reset_rule' => 'yearly',
                    'status' => 'active',
                    'valid_from' => now()->startOfYear()->toDateString(),
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function ensurePostingRule(string $typeCode, string $family, array $lines): void
    {
        $rule = PostingRule::query()->where('accounting_entity_id', $this->entity->id)->where('code', "QA-{$typeCode}")->first();
        if (! $rule) {
            $rule = PostingRule::query()->create([
                'accounting_entity_id' => $this->entity->id,
                'transaction_type_id' => $this->types[$typeCode]->id,
                'code' => "QA-{$typeCode}",
                'name' => "{$this->types[$typeCode]->name} SAMPLE/QA",
                'rule_family' => $family,
                'status' => 'active',
                'valid_from' => now()->startOfYear()->toDateString(),
                'created_by_user_id' => $this->actorUserId,
                'updated_by_user_id' => $this->actorUserId,
            ]);
        }
        $version = PostingRuleVersion::query()->where('posting_rule_id', $rule->id)->where('version_no', 1)->first();
        if (! $version) {
            $version = PostingRuleVersion::query()->create([
                'accounting_entity_id' => $this->entity->id,
                'posting_rule_id' => $rule->id,
                'version_no' => 1,
                'effective_from' => now()->startOfYear()->toDateString(),
                'input_contract_ref' => 'LOCAL-SAMPLE-QA-'.$typeCode,
                'journal_template_ref' => 'LOCAL-SAMPLE-QA-'.$typeCode,
                'business_rule_refs' => 'LOCAL-SAMPLE-QA',
                'status' => 'draft',
                'created_by_user_id' => $this->actorUserId,
                'updated_by_user_id' => $this->actorUserId,
            ]);
        }
        if ($version->status === 'draft') {
            foreach (array_values($lines) as $index => $line) {
                PostingRuleLine::query()->firstOrCreate(
                    ['posting_rule_version_id' => $version->id, 'line_no' => $index + 1],
                    [
                        'accounting_entity_id' => $this->entity->id,
                        'account_id' => $line['account_id'],
                        'entry_side' => $line['entry_side'],
                        'amount_source' => $line['amount_source'],
                        'financial_account_source' => $line['financial_account_source'] ?? 'none',
                        'fund_source' => $line['fund_source'] ?? 'none',
                        'program_source' => $line['program_source'] ?? 'none',
                        'cost_center_source' => $line['cost_center_source'] ?? 'none',
                        'counterparty_source' => $line['counterparty_source'] ?? 'none',
                        'category_source' => $line['category_source'] ?? 'none',
                        'created_by_user_id' => $this->actorUserId,
                        'updated_by_user_id' => $this->actorUserId,
                    ],
                );
            }
            $this->governance->makePostingRuleVersionEffective($version->id, $this->actorUserId);
        }
    }

    private function ensureSampleTransactions(): void
    {
        $friday = CarbonImmutable::now()->previous(CarbonImmutable::FRIDAY)->toDateString();
        $today = now()->toDateString();

        // Friday operations: exactly Rp3.000.000 receipt - Rp750.000 expense = Rp2.250.000 cash/fund balance.
        $this->receipt('FRIDAY-RECEIPT', $friday, '3000000.00', 'OPS', 'CSH-OPS', 'OPS', 'RCV', 'Penerimaan Jumat SAMPLE/QA');
        $this->payment('FRIDAY-EXPENSE', $friday, '750000.00', 'OPS', 'CSH-OPS', 'OPS', 'OPS', 'Pemasok SAMPLE/QA', 'Pengeluaran operasional Jumat SAMPLE/QA');

        // Zakat: funds stay distinct from the shared ZISWAF bank liquidity.
        $this->receipt('ZAKAT-RECEIPT', $today, '20000000.00', 'ZAKAT', 'BNK-ZIS', 'DHUAFA', 'RCV', 'Penerimaan Zakat Maal SAMPLE/QA');
        $this->transfer('ZAKAT-BANK-TO-CASH', $today, '5000000.00', 'ZAKAT', 'BNK-ZIS', 'CSH-ZIS', 'Pengambilan Zakat Maal Bank ke Cash SAMPLE/QA');
        $this->payment('ZAKAT-DHUAFA', $today, '5000000.00', 'ZAKAT', 'CSH-ZIS', 'DHUAFA', 'SANTUNAN', 'Penerima Santunan SAMPLE/QA', 'Santunan Dhuafa dari Zakat SAMPLE/QA');

        // Three monthly Yatim cycles establish allocation -> approval -> realization history without 100 journals per distribution.
        foreach ([2, 1, 0] as $monthsAgo) {
            $date = CarbonImmutable::now()->startOfMonth()->subMonths($monthsAgo)->addDays(7)->toDateString();
            $suffix = CarbonImmutable::parse($date)->format('Ym');
            $this->receipt("YATIM-RECEIPT-{$suffix}", $date, '10000000.00', 'YATIM', 'BNK-ZIS', 'YATIM', 'RCV', "Dana Santunan Anak Yatim {$suffix} SAMPLE/QA");
            $this->transfer("YATIM-BANK-TO-CASH-{$suffix}", $date, '10000000.00', 'YATIM', 'BNK-ZIS', 'CSH-ZIS', "Pengambilan dana Yatim {$suffix} Bank ke Cash SAMPLE/QA");
            $this->realization("YATIM-REALIZATION-{$suffix}", $date, '10000000.00', $suffix);
        }

        $this->receipt('QURBAN-RECEIPT', $today, '30000000.00', 'QURBAN', 'BNK-QUR', 'QURBAN', 'RCV', 'Penerimaan Qurban SAMPLE/QA');
        $this->payment('QURBAN-PAYMENT', $today, '25000000.00', 'QURBAN', 'BNK-QUR', 'QURBAN', 'QURBAN', 'Pemasok SAMPLE/QA', 'Pembelian hewan Qurban SAMPLE/QA');
        $this->receipt('RAMADHAN-RECEIPT', $today, '5000000.00', 'RAMADHAN', 'BNK-OPS', 'RAMADHAN', 'RCV', 'Penerimaan Ramadhan SAMPLE/QA');
        $this->payment('RAMADHAN-PAYMENT', $today, '1500000.00', 'RAMADHAN', 'BNK-OPS', 'RAMADHAN', 'RAMADHAN', 'Pemasok SAMPLE/QA', 'Konsumsi Ramadhan SAMPLE/QA');
        $this->receipt('SOSIAL-RECEIPT', $today, '3000000.00', 'SOSIAL', 'BNK-SOS', 'SOSIAL', 'RCV', 'Penerimaan Sosial/Kematian SAMPLE/QA');
        $this->payment('SOSIAL-PAYMENT', $today, '1000000.00', 'SOSIAL', 'BNK-SOS', 'SOSIAL', 'SOSIAL', 'Penerima Santunan SAMPLE/QA', 'Santunan Sosial/Kematian SAMPLE/QA');
        $this->receipt('AULA-RECEIPT', $today, '2000000.00', 'SEWA-AULA', 'BNK-AULA', 'AULA', 'RCV', 'Penerimaan Sewa Aula SAMPLE/QA');
        $this->payment('AULA-PAYMENT', $today, '500000.00', 'SEWA-AULA', 'BNK-AULA', 'AULA', 'AULA', 'Pemasok SAMPLE/QA', 'Biaya Sewa Aula SAMPLE/QA');
    }

    private function receipt(string $reference, string $date, string $amount, string $fundCode, string $financialAccountCode, string $programCode, string $categoryCode, string $description): void
    {
        $transaction = $this->findOrCreate($reference, function () use ($reference, $date, $amount, $fundCode, $financialAccountCode, $programCode, $categoryCode, $description): FinancialTransaction {
            return $this->lifecycle->createReceipt($this->input($reference, $date, $amount, $description, [
                'transaction_type_id' => $this->types['RCV']->id,
                'primary_financial_account_id' => $this->financialAccounts[$financialAccountCode]->id,
                'category_id' => $this->categories[$categoryCode]->id,
            ]), [[
                'account_id' => $this->accounts['REV-01']->id,
                'split_amount' => $amount,
                'fund_id' => $this->funds[$fundCode]->id,
                'program_id' => $this->programs[$programCode]->id,
            ]], $this->actorUserId);
        });
        $this->attachEvidenceAndPost($transaction, 'receipt');
    }

    private function payment(string $reference, string $date, string $amount, string $fundCode, string $financialAccountCode, string $programCode, string $categoryCode, string $counterpartyName, string $description): void
    {
        $party = $counterpartyName === 'Penerima Santunan SAMPLE/QA' ? $this->counterparties['BENEFICIARY'] : $this->counterparties['SUPPLIER'];
        $transaction = $this->findOrCreate($reference, function () use ($reference, $date, $amount, $fundCode, $financialAccountCode, $programCode, $categoryCode, $party, $description): FinancialTransaction {
            return $this->lifecycle->createPayment($this->input($reference, $date, $amount, $description, [
                'transaction_type_id' => $this->types['PAY']->id,
                'primary_financial_account_id' => $this->financialAccounts[$financialAccountCode]->id,
                'counterparty_id' => $party->id,
                'category_id' => $this->categories[$categoryCode]->id,
            ]), [[
                'account_id' => $this->accounts['EXP-01']->id,
                'split_amount' => $amount,
                'fund_id' => $this->funds[$fundCode]->id,
                'program_id' => $this->programs[$programCode]->id,
            ]], $this->actorUserId);
        });
        $this->attachEvidenceAndPost($transaction, 'invoice');
    }

    private function transfer(string $reference, string $date, string $amount, string $fundCode, string $sourceCode, string $destinationCode, string $description): void
    {
        $transaction = $this->findOrCreate($reference, function () use ($reference, $date, $amount, $fundCode, $sourceCode, $destinationCode, $description): FinancialTransaction {
            return $this->lifecycle->createTreasuryTransfer($this->input($reference, $date, $amount, $description, [
                'transaction_type_id' => $this->types['TRF']->id,
                'source_financial_account_id' => $this->financialAccounts[$sourceCode]->id,
                'destination_financial_account_id' => $this->financialAccounts[$destinationCode]->id,
            ]), [[
                'account_id' => $this->accounts['LIQ-01']->id,
                'split_amount' => $amount,
                'fund_id' => $this->funds[$fundCode]->id,
            ]], $this->actorUserId);
        });
        $this->attachEvidenceAndPost($transaction, 'transfer_proof');
    }

    private function realization(string $reference, string $date, string $amount, string $month): void
    {
        $allocation = BudgetAllocation::query()->where('accounting_entity_id', $this->entity->id)->where('allocation_reference', "SAMPLE-QA-YATIM-{$month}")->first();
        if (! $allocation) {
            $period = AccountingPeriod::query()->where('accounting_entity_id', $this->entity->id)->where('start_date', '<=', $date)->where('end_date', '>=', $date)->firstOrFail();
            $allocation = $this->allocations->create([
                'accounting_entity_id' => $this->entity->id,
                'accounting_period_id' => $period->id,
                'fund_id' => $this->funds['YATIM']->id,
                'program_id' => $this->programs['YATIM']->id,
                'category_id' => $this->categories['SANTUNAN']->id,
                'allocation_reference' => "SAMPLE-QA-YATIM-{$month}",
                'idempotency_key' => "sample-qa-allocation-yatim-{$month}",
                'allocated_amount' => $amount,
                'effective_from' => $date,
                'reason' => "Alokasi 100 penerima x Rp100.000 untuk {$month} — SAMPLE/QA.",
            ], $this->actorUserId);
            $this->allocations->submit($allocation->id, $this->actorUserId);
            $this->allocations->approveVersion($allocation->id, $allocation->versions->firstOrFail()->id, $this->actorUserId);
        }
        $allocation->load('versions');
        $version = $allocation->versions->firstWhere('status', 'approved');

        $transaction = $this->findOrCreate($reference, function () use ($reference, $date, $amount, $month, $version): FinancialTransaction {
            return $this->lifecycle->createRealization($this->input($reference, $date, $amount, "Realisasi 100 penerima x Rp100.000 untuk {$month} — SAMPLE/QA.", [
                'transaction_type_id' => $this->types['PAY']->id,
                'primary_financial_account_id' => $this->financialAccounts['CSH-ZIS']->id,
                'counterparty_id' => $this->counterparties['BENEFICIARY']->id,
                'category_id' => $this->categories['SANTUNAN']->id,
            ]), [[
                'account_id' => $this->accounts['EXP-01']->id,
                'split_amount' => $amount,
                'fund_id' => $this->funds['YATIM']->id,
                'program_id' => $this->programs['YATIM']->id,
                'purpose_note' => '100 penerima; Rp100.000 per penerima; one payment accounting effect.',
            ]], $version->id, $this->actorUserId);
        });
        $this->attachEvidenceAndPost($transaction, 'approval');
    }

    /** @param array<string, mixed> $extra @return array<string, mixed> */
    private function input(string $reference, string $date, string $amount, string $description, array $extra): array
    {
        return $extra + [
            'accounting_entity_id' => $this->entity->id,
            'business_date' => $date,
            'accounting_date' => $date,
            'gross_amount' => $amount,
            'source_reference' => 'SAMPLE-QA-'.$reference,
            'idempotency_key' => 'sample-qa-source-'.strtolower($reference),
            'description' => $description,
        ];
    }

    /** @param callable(): FinancialTransaction $create */
    private function findOrCreate(string $reference, callable $create): FinancialTransaction
    {
        return FinancialTransaction::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('source_reference', 'SAMPLE-QA-'.$reference)
            ->first() ?? $create();
    }

    private function attachEvidenceAndPost(FinancialTransaction $transaction, string $type): void
    {
        if (! AttachmentLink::query()->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('evidence_type', $type)->exists()) {
            $path = 'financial-v2-sample-qa/'.$transaction->id.'-'.$type.'.pdf';
            if (! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, "SAMPLE/QA evidence only\n{$transaction->source_reference}\n");
            }
            $this->evidence->attachToTransaction($this->entity->id, $transaction->id, basename($path), 'application/pdf', Storage::disk('local')->size($path), hash('sha256', $transaction->id.'|'.$type), $path, $type, $this->actorUserId);
        }

        $transaction = $transaction->fresh();
        if ($transaction->status === 'draft') {
            $transaction = $this->lifecycle->submit($transaction->id, $this->actorUserId);
        }
        if ($transaction->status === 'submitted') {
            $transaction = $this->lifecycle->verify($transaction->id, $this->actorUserId);
        }
        if ($transaction->status === 'verified') {
            $transaction = $this->lifecycle->approve($transaction->id, $this->actorUserId);
        }
        if ($transaction->status === 'approved') {
            $key = 'sample-qa-post-'.$transaction->id;
            $this->lifecycle->post($transaction->id, $key, hash('sha256', $key), $this->actorUserId);
        }
    }
}
