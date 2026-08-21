<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\AuditTrailService;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Provisions only governed Financial V2 operational masters for MRJ.
 *
 * This command is deliberately idempotent and asserts that no financial fact
 * (transaction, opening balance, Journal, JournalLine, or LedgerEntry) has
 * been created or modified by its work. Actual ZISWAF opening facts remain
 * under the OpeningBalanceService and PostingEngine only.
 */
final class ProvisionMrjOperationalMasterCommand extends Command
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    private const EFFECTIVE_DATE = '2026-08-15';

    protected $signature = 'financial-v2:provision-mrj-operational-master
        {--dry-run : Validate the local configuration without writing masters}
        {--allow-testing : Permit execution only in APP_ENV=testing on mrj_test_db}';

    protected $description = 'Provision governed MRJ operational Financial V2 masters and policy matrix without creating financial facts.';

    private AccountingEntity $entity;

    private int $actorUserId;

    /** @var array<string, Account> */
    private array $accounts = [];

    /** @var array<string, TransactionType> */
    private array $types = [];

    /** @var array<string, Program> */
    private array $programs = [];

    /** @var array<string, \App\Models\FinancialV2\Category> */
    private array $categories = [];

    /** @var array<string, Fund> */
    private array $funds = [];

    public function handle(
        FinancialMasterDataService $masters,
        MasterDataGovernanceService $governance,
        AuditTrailService $audit,
    ): int {
        $this->assertPermittedEnvironment();
        $this->entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->firstOrFail();
        $before = $this->financialFactSnapshot();

        if ($this->option('dry-run')) {
            $this->table(['Check', 'Result'], [
                ['Accounting entity', $this->entity->name],
                ['Policy effective date', self::EFFECTIVE_DATE],
                ['Financial fact mutation', 'NONE (dry run)'],
                ['ALC / REAL mapping', 'Allocation lifecycle / realized PAY transaction'],
            ]);

            return self::SUCCESS;
        }

        $this->actorUserId = $this->qaUser()->id;

        DB::transaction(function () use ($masters, $governance, $audit): void {
            $this->ensureOperationalAccounts($audit);
            $this->ensureTransactionTypesAndSequences($audit);
            $this->ensureFinancialAccounts($masters, $governance);
            $this->ensurePrograms($masters, $governance);
            $this->ensureCategories($masters);
            $this->ensurePostingRules($governance, $audit);
            $this->linkCategoryPostingRules($masters);
            $this->ensureFundsAndPolicies($masters, $governance);
        }, 3);

        $after = $this->financialFactSnapshot();
        if ($before !== $after) {
            throw new RuntimeException('Operational-master provisioning refused completion because it changed a Financial V2 fact.');
        }

        $this->table(['Control', 'Result'], [
            ['Master funds', (string) Fund::query()->where('accounting_entity_id', $this->entity->id)->count()],
            ['Active financial accounts', (string) FinancialAccount::query()->where('accounting_entity_id', $this->entity->id)->where('status', 'active')->count()],
            ['Active programs', (string) Program::query()->where('accounting_entity_id', $this->entity->id)->where('status', 'active')->count()],
            ['Active categories', (string) \App\Models\FinancialV2\Category::query()->where('accounting_entity_id', $this->entity->id)->where('status', 'active')->count()],
            ['Financial facts before / after', json_encode([$before, $after], JSON_THROW_ON_ERROR)],
            ['ALC / REAL mapping', 'Allocation is non-financial; realization is a posted PAY linked to FundRealization'],
        ]);
        $this->info('MRJ operational masters and governed policy configuration are ready. No financial fact was created.');

        return self::SUCCESS;
    }

    private function assertPermittedEnvironment(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($this->option('allow-testing')) {
            if (! app()->environment('testing') || $database !== 'mrj_test_db') {
                throw new RuntimeException('Testing provisioning is permitted only in APP_ENV=testing on mrj_test_db.');
            }

            return;
        }

        if (! app()->environment('local') || $database !== 'mrj_prod_db') {
            throw new RuntimeException('Operational master provisioning is permitted only in local development on mrj_prod_db.');
        }
    }

    private function qaUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'superadmin@emasjid.com'],
            ['name' => 'Super Admin (Local QA)', 'password' => Hash::make('password')],
        );
    }

    private function ensureOperationalAccounts(AuditTrailService $audit): void
    {
        $groups = [
            'REV' => ['Penerimaan Dana', 'revenue', 30],
            'EXP' => ['Penggunaan Dana', 'expense', 40],
            'TRF' => ['Transfer Antar Dana', 'transfer', 50],
        ];
        foreach ($groups as $code => [$name, $class, $order]) {
            $group = AccountGroup::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                ['name' => $name, 'group_class' => $class, 'display_order' => $order, 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId],
            );
            $this->recordNew($audit, $group, 'account_group', 'account_group_created');
        }

        $definitions = [
            'LIQ-ZIS' => [null, 'Likuiditas ZISWAF', 'asset', 'debit', true],
            'REV-MRJ' => ['REV', 'Penerimaan Operasional MRJ', 'revenue', 'credit', false],
            'EXP-MRJ' => ['EXP', 'Penggunaan Operasional MRJ', 'expense', 'debit', false],
            'TRF-MRJ' => ['TRF', 'Transfer Antar Dana MRJ', 'transfer', 'debit', false],
        ];
        foreach ($definitions as $code => [$groupCode, $name, $class, $normal, $liquidity]) {
            $existing = Account::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $existing && ! $groupCode) {
                throw new RuntimeException('The immutable MRJ ZISWAF liquidity account LIQ-ZIS is missing. Run approved onboarding first.');
            }
            $account = $existing ?: Account::query()->create([
                'accounting_entity_id' => $this->entity->id,
                'account_group_id' => AccountGroup::query()->where('accounting_entity_id', $this->entity->id)->where('code', $groupCode)->value('id'),
                'code' => $code, 'name' => $name, 'account_class' => $class, 'normal_balance' => $normal,
                'is_posting_account' => true, 'is_liquidity_account' => $liquidity, 'is_control_account' => false,
                'allow_manual_posting' => false, 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE,
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ]);
            $this->recordNew($audit, $account, 'account', 'account_created');
            $this->accounts[$code] = $account;
        }
    }

    private function ensureTransactionTypesAndSequences(AuditTrailService $audit): void
    {
        $definitions = [
            'RCV' => ['Penerimaan', 'RCV'], 'PAY' => ['Pengeluaran', 'PAY'], 'TRF' => ['Transfer Rekening/Kas', 'TRF'],
            'IFT' => ['Transfer Antar Dana', 'IFT'], 'ADJ' => ['Penyesuaian Terkendali', 'ADJ'], 'OPB' => ['Saldo Awal', 'OPB'],
        ];
        foreach ($definitions as $code => [$name, $prefix]) {
            $type = TransactionType::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                ['name' => $name, 'voucher_prefix' => $prefix, 'has_financial_impact' => true, 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId],
            );
            $this->recordNew($audit, $type, 'transaction_type', 'transaction_type_created');
            $this->types[$code] = $type;
            if ($code === 'OPB') {
                continue;
            }
            $sequence = DocumentSequence::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => 'MRJ-'.$code],
                ['transaction_type_id' => $type->id, 'name' => 'Voucher '.$name, 'prefix' => $prefix, 'scope_key' => 'mrj-operational-'.$code, 'next_value' => 1, 'reset_rule' => 'yearly', 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId],
            );
            $this->recordNew($audit, $sequence, 'document_sequence', 'document_sequence_created');
        }
    }

    private function ensureFinancialAccounts(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        $definitions = [
            'BSI-MRJ-TCE' => ['BSI Masjid Raudhotul Jannah TCE', 'bank', ['bank_name' => 'BSI', 'account_number_masked' => 'Nomor belum dikonfigurasi']],
            'MANDIRI-ZISWAF' => ['Bank Mandiri ZISWAF', 'bank', ['bank_name' => 'Bank Mandiri', 'account_number_masked' => 'Nomor belum dikonfigurasi']],
            'BCA-SEWA-AULA' => ['BCA Sewa Aula', 'bank', ['bank_name' => 'BCA', 'account_number_masked' => 'Nomor belum dikonfigurasi']],
            'BANK-QURBAN' => ['Bank Qurban', 'bank', ['bank_name' => 'Belum dikonfigurasi', 'account_number_masked' => 'Nomor belum dikonfigurasi']],
            'BANK-SOSIAL-KEMATIAN' => ['Bank Sosial/Kematian', 'bank', ['bank_name' => 'Belum dikonfigurasi', 'account_number_masked' => 'Nomor belum dikonfigurasi']],
            'CASH-OPERASIONAL' => ['Cash Operasional', 'cash', ['cash_location' => 'Kas operasional masjid', 'cash_count_frequency' => 'daily']],
            'CASH-QURBAN' => ['Cash Qurban', 'cash', ['cash_location' => 'Kas kegiatan qurban', 'cash_count_frequency' => 'ad_hoc']],
            'CASH-SOSIAL' => ['Cash Sosial', 'cash', ['cash_location' => 'Kas sosial dan kematian', 'cash_count_frequency' => 'ad_hoc']],
        ];
        foreach ($definitions as $code => [$name, $type, $detail]) {
            $account = FinancialAccount::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $account) {
                $account = $masters->createFinancialAccount($this->entity->id, [
                    'account_id' => $this->accounts['LIQ-ZIS']->id, 'code' => $code, 'name' => $name, 'account_type' => $type,
                    'currency_code' => 'IDR', 'opening_date' => self::EFFECTIVE_DATE,
                ] + $detail, $this->actorUserId);
            }
            if ($account->status === 'draft') {
                $governance->activateFinancialAccount($account->id, self::EFFECTIVE_DATE, $this->actorUserId);
            }
        }
    }

    private function ensurePrograms(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        $definitions = [
            'OPERASIONAL-JUMAT' => 'Operasional Jumat', 'OPERASIONAL-HARIAN' => 'Operasional Harian Masjid',
            'SANTUNAN-YATIM-BULANAN' => 'Santunan Anak Yatim Bulanan', 'QURBAN' => 'Qurban', 'IFTAR-RAMADHAN' => 'Iftar Ramadhan',
            'KEGIATAN-RAMADHAN' => 'Kegiatan Ramadhan', 'SANTUNAN-RAMADHAN' => 'Santunan Ramadhan',
            'SOSIAL-KEMATIAN' => 'Sosial/Kematian', 'SEWA-AULA' => 'Sewa Aula', 'BEASISWA-DHUAFA' => 'Beasiswa Dhuafa',
            'BANTUAN-DHUAFA' => 'Bantuan Dhuafa', 'PEMELIHARAAN-MASJID' => 'Pemeliharaan Masjid',
        ];
        foreach ($definitions as $code => $name) {
            $program = Program::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $program) {
                $program = $masters->createProgram($this->entity->id, ['cost_center_id' => null, 'code' => $code, 'name' => $name, 'start_date' => self::EFFECTIVE_DATE, 'end_date' => null, 'program_owner_reference' => 'Konfigurasi operasional MRJ Phase 12'], $this->actorUserId);
            }
            if ($program->status === 'draft') {
                $governance->activateProgram($program->id, $this->actorUserId);
            }
            $this->programs[$code] = $program->fresh();
        }
    }

    private function ensureCategories(FinancialMasterDataService $masters): void
    {
        $receipt = ['INFAK-JUMAT' => 'Infak Jumat', 'KOTAK-AMAL' => 'Kotak Amal', 'TROMOL' => 'Tromol', 'DONASI' => 'Donasi', 'ZAKAT' => 'Zakat', 'FIDYAH' => 'Fidyah', 'SODAQOH' => 'Sodaqoh', 'SEWA-AULA' => 'Sewa Aula', 'QURBAN' => 'Penerimaan Qurban', 'LAINNYA' => 'Lainnya'];
        $payment = ['OPERASIONAL-MASJID' => 'Operasional Masjid', 'KONSUMSI' => 'Konsumsi', 'KEBERSIHAN' => 'Kebersihan', 'LISTRIK' => 'Listrik', 'AIR' => 'Air', 'PEMELIHARAAN' => 'Pemeliharaan', 'SANTUNAN' => 'Santunan', 'BEASISWA' => 'Beasiswa', 'DHUAFA' => 'Dhuafa', 'QURBAN' => 'Qurban', 'RAMADHAN' => 'Ramadhan', 'SOSIAL-KEMATIAN' => 'Sosial/Kematian', 'SEWA-AULA' => 'Sewa Aula', 'ATK' => 'ATK', 'KEGIATAN' => 'Kegiatan', 'PEMBELIAN-HEWAN' => 'Pembelian Hewan', 'OPERASIONAL-QURBAN' => 'Operasional Qurban', 'DISTRIBUSI-QURBAN' => 'Distribusi Qurban', 'BANTUAN-KEMATIAN' => 'Bantuan Kematian', 'BANTUAN-SOSIAL' => 'Bantuan Sosial', 'LAINNYA' => 'Lainnya'];
        foreach (['RCV' => $receipt, 'PAY' => $payment] as $typeCode => $definitions) {
            foreach ($definitions as $suffix => $name) {
                $code = $typeCode.'-'.$suffix;
                $category = \App\Models\FinancialV2\Category::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
                if (! $category) {
                    $category = $masters->createCategory($this->entity->id, ['transaction_type_id' => $this->types[$typeCode]->id, 'default_posting_rule_id' => null, 'code' => $code, 'name' => $name, 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'valid_to' => null], $this->actorUserId);
                }
                $this->categories[$code] = $category->fresh();
            }
        }
    }

    private function ensurePostingRules(MasterDataGovernanceService $governance, AuditTrailService $audit): void
    {
        $rules = [
            'RCV' => ['MRJ-RCV-STANDARD', 'Penerimaan operasional MRJ', 'receipt', [[1, 'LIQ-ZIS', 'debit', 'transaction_primary', 'split', 'split', 'split', 'none'], [2, 'REV-MRJ', 'credit', 'none', 'split', 'split', 'split', 'none']]],
            'PAY' => ['MRJ-PAY-STANDARD', 'Pengeluaran operasional MRJ', 'payment', [[1, 'EXP-MRJ', 'debit', 'none', 'split', 'split', 'split', 'split'], [2, 'LIQ-ZIS', 'credit', 'transaction_primary', 'split', 'split', 'split', 'split']]],
            'TRF' => ['MRJ-TRF-STANDARD', 'Transfer rekening atau kas MRJ', 'treasury-transfer', [[1, 'LIQ-ZIS', 'debit', 'transfer_destination', 'split', 'none', 'none', 'none'], [2, 'LIQ-ZIS', 'credit', 'transfer_source', 'split', 'none', 'none', 'none']]],
            'IFT' => ['MRJ-IFT-STANDARD', 'Transfer antar Dana MRJ', 'interfund-transfer', [[1, 'TRF-MRJ', 'debit', 'none', 'interfund_destination', 'none', 'none', 'none'], [2, 'TRF-MRJ', 'credit', 'none', 'interfund_source', 'none', 'none', 'none']]],
        ];
        foreach ($rules as $typeCode => [$code, $name, $family, $lines]) {
            $rule = PostingRule::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                ['transaction_type_id' => $this->types[$typeCode]->id, 'name' => $name, 'rule_family' => $family, 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId],
            );
            $this->recordNew($audit, $rule, 'posting_rule', 'posting_rule_created');
            $version = PostingRuleVersion::query()->firstOrCreate(
                ['posting_rule_id' => $rule->id, 'version_no' => 1],
                ['accounting_entity_id' => $this->entity->id, 'effective_from' => self::EFFECTIVE_DATE, 'input_contract_ref' => 'Phase 12 operational '.$typeCode.' input contract', 'journal_template_ref' => $code, 'business_rule_refs' => 'FinancialTransactionLifecycleService; PostingEngine; Phase 12', 'status' => 'draft', 'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId],
            );
            $this->recordNew($audit, $version, 'posting_rule_version', 'posting_rule_version_created');
            foreach ($lines as [$lineNo, $accountCode, $side, $financialSource, $fundSource, $programSource, $categorySource, $counterpartySource]) {
                $line = PostingRuleLine::query()->firstOrCreate(
                    ['posting_rule_version_id' => $version->id, 'line_no' => $lineNo],
                    ['accounting_entity_id' => $this->entity->id, 'account_id' => $this->accounts[$accountCode]->id, 'entry_side' => $side, 'amount_source' => $typeCode === 'IFT' ? 'transaction_gross_amount' : 'split_amount', 'financial_account_source' => $financialSource, 'fund_source' => $fundSource, 'program_source' => $programSource, 'cost_center_source' => 'none', 'counterparty_source' => $counterpartySource, 'category_source' => $categorySource, 'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId],
                );
                $this->recordNew($audit, $line, 'posting_rule_line', 'posting_rule_line_created');
            }
            if ($version->status === 'draft') {
                $governance->makePostingRuleVersionEffective($version->id, $this->actorUserId);
            }
            $this->ensureEvidenceRequirements($version->fresh(), $typeCode, $audit);
        }
    }

    private function ensureEvidenceRequirements(PostingRuleVersion $version, string $typeCode, AuditTrailService $audit): void
    {
        $types = match ($typeCode) { 'PAY' => ['invoice'], 'TRF' => ['transfer_proof'], default => [] };
        foreach ($types as $type) {
            $requirement = EvidenceRequirement::query()->firstOrCreate(
                ['posting_rule_version_id' => $version->id, 'evidence_type' => $type],
                ['accounting_entity_id' => $this->entity->id, 'minimum_count' => 1],
            );
            $this->recordNew($audit, $requirement, 'evidence_requirement', 'evidence_requirement_created');
        }
    }

    private function linkCategoryPostingRules(FinancialMasterDataService $masters): void
    {
        $ruleIds = PostingRule::query()->where('accounting_entity_id', $this->entity->id)->whereIn('code', ['MRJ-RCV-STANDARD', 'MRJ-PAY-STANDARD'])->pluck('id', 'code');
        foreach ($this->categories as $code => $category) {
            $ruleId = Str::startsWith($code, 'RCV-') ? $ruleIds['MRJ-RCV-STANDARD'] : $ruleIds['MRJ-PAY-STANDARD'];
            if ($category->default_posting_rule_id === $ruleId) {
                continue;
            }
            $masters->updateCategory($this->entity->id, $category->id, ['transaction_type_id' => $category->transaction_type_id, 'default_posting_rule_id' => $ruleId, 'code' => $category->code, 'name' => $category->name, 'status' => $category->status, 'valid_from' => $category->valid_from, 'valid_to' => $category->valid_to], $this->actorUserId);
        }
    }

    private function ensureFundsAndPolicies(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        $restrictedType = FundType::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'RESTRICTED')->firstOrFail();
        $unrestricted = FundType::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'UNRESTRICTED-GENERAL')->first();
        if (! $unrestricted) {
            $unrestricted = $masters->createFundType($this->entity->id, ['code' => 'UNRESTRICTED-GENERAL', 'name' => 'Dana Tidak Terikat', 'classification' => 'unrestricted', 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'valid_to' => null], $this->actorUserId);
        }
        $generalRestriction = FundRestriction::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'MRJ-GENERAL-USE')->first();
        if (! $generalRestriction) {
            $generalRestriction = $masters->createFundRestriction($this->entity->id, ['fund_type_id' => $unrestricted->id, 'code' => 'MRJ-GENERAL-USE', 'name' => 'Penggunaan umum sesuai tata kelola', 'severity' => 'low', 'policy_basis' => 'Dana tidak terikat; konfigurasi penggunaan tercatat untuk transparansi dan tidak menggantikan pengawasan pengurus.', 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'valid_to' => null], $this->actorUserId);
        }
        $governedRestriction = FundRestriction::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'MRJ-GOVERNED-RESTRICTED')->first();
        if (! $governedRestriction) {
            $governedRestriction = $masters->createFundRestriction($this->entity->id, ['fund_type_id' => $restrictedType->id, 'code' => 'MRJ-GOVERNED-RESTRICTED', 'name' => 'Terikat sesuai kebijakan Dana MRJ', 'severity' => 'high', 'policy_basis' => 'Aturan penggunaan harus melalui matrix kebijakan yang berlaku. Ketidakjelasan kebijakan ditolak secara server-side.', 'status' => 'active', 'valid_from' => self::EFFECTIVE_DATE, 'valid_to' => null], $this->actorUserId);
        }

        $definitions = [
            'OPERASIONAL-MASJID' => [$unrestricted, $generalRestriction, 'Dana Operasional Masjid', 'Membiayai operasional masjid sesuai tata kelola pengurus.', null],
            'QURBAN' => [$restrictedType, $governedRestriction, 'Dana Qurban', 'Dana khusus kegiatan qurban.', 'Tidak boleh digunakan di luar kegiatan qurban yang diizinkan.'],
            'RAMADHAN' => [$restrictedType, $governedRestriction, 'Dana Ramadhan', 'Dana khusus kegiatan Ramadhan.', 'Ketidakjelasan peruntukan ditolak hingga kebijakan menetapkan penggunaan.'],
            'SOSIAL-KEMATIAN' => [$restrictedType, $governedRestriction, 'Dana Sosial/Kematian', 'Dana khusus bantuan sosial dan kematian.', 'Tidak boleh digunakan di luar bantuan sosial atau kematian yang diizinkan.'],
            'SEWA-AULA' => [$restrictedType, $governedRestriction, 'Dana Sewa Aula', 'Dana hasil dan penggunaan terkait sewa aula.', 'Ketidakjelasan peruntukan ditolak hingga kebijakan menetapkan penggunaan.'],
        ];
        foreach ($definitions as $code => [$type, $restriction, $name, $purpose, $prohibited]) {
            $fund = Fund::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $fund) {
                $fund = $masters->createFund($this->entity->id, ['fund_type_id' => $type->id, 'fund_restriction_id' => $restriction->id, 'code' => $code, 'name' => $name, 'purpose_statement' => $purpose, 'prohibited_use_statement' => $prohibited, 'minimum_balance_policy' => null, 'allow_negative_balance' => false, 'valid_from' => self::EFFECTIVE_DATE, 'valid_to' => null], $this->actorUserId);
            }
            $this->funds[$code] = $fund->fresh(['type']);
        }
        foreach (['ZAKAT-MAAL', 'INFAQ-TROMOL', 'SODAQOH', 'SANTUNAN-YATIM', 'FIDYAH', 'DHUAFA'] as $code) {
            $this->funds[$code] = Fund::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->firstOrFail()->fresh(['type']);
        }

        foreach ($this->funds as $code => $fund) {
            $version = $this->ensurePolicy($masters, $fund, $code);
            if ($version->status === 'draft') {
                $this->addPolicyRules($masters, $version, $code);
                $version = $governance->makeFundPolicyVersionEffective($version->id, $this->actorUserId);
            }
            if ($fund->status === 'draft') {
                $governance->activateFund($fund->id, self::EFFECTIVE_DATE, $this->actorUserId);
            }
        }
    }

    private function ensurePolicy(FinancialMasterDataService $masters, Fund $fund, string $fundCode): FundPolicyVersion
    {
        $reference = 'PHASE-12-MRJ-OPERATIONAL-POLICY|'.$fundCode;
        $existing = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('policy_document_ref', $reference)->first();
        if ($existing) {
            return $existing;
        }

        return $masters->createFundPolicyVersion($this->entity->id, ['fund_id' => $fund->id, 'effective_from' => self::EFFECTIVE_DATE, 'effective_to' => null, 'policy_document_ref' => $reference, 'allowed_matrix_ref' => 'PHASE-12-MRJ-OPERATIONAL-MATRIX|'.$fundCode, 'exception_approval_level' => 'financial-governance'], $this->actorUserId);
    }

    private function addPolicyRules(FinancialMasterDataService $masters, FundPolicyVersion $version, string $fundCode): void
    {
        $allowed = fn (string $type, ?string $category = null, ?string $program = null, string $rationale = 'Diizinkan oleh kebijakan operasional yang berlaku.') => ['transaction_type_id' => $this->types[$type]->id, 'account_id' => null, 'category_id' => $category ? $this->categories[$category]->id : null, 'program_id' => $program ? $this->programs[$program]->id : null, 'cost_center_id' => null, 'decision' => 'allowed', 'rationale' => $rationale];
        $prohibited = fn (string $type, ?string $category = null, ?string $program = null, string $rationale = 'Penggunaan tidak diizinkan oleh kebijakan Dana.') => ['transaction_type_id' => $this->types[$type]->id, 'account_id' => null, 'category_id' => $category ? $this->categories[$category]->id : null, 'program_id' => $program ? $this->programs[$program]->id : null, 'cost_center_id' => null, 'decision' => 'prohibited', 'rationale' => $rationale];
        $rules = [
            $allowed('OPB', null, null, 'Saldo awal hanya melalui proses Opening Balance yang terdokumentasi.'),
            $allowed('TRF', null, null, 'Transfer internal mempertahankan saldo Dana dan hanya memindahkan likuiditas.'),
        ];
        $rules = match ($fundCode) {
            'OPERASIONAL-MASJID' => [...$rules, $allowed('PAY', 'PAY-OPERASIONAL-MASJID', 'OPERASIONAL-JUMAT', 'Pengeluaran operasional Jumat dicatat sebagai konfigurasi transparansi Dana tidak terikat.')],
            'ZAKAT-MAAL' => [...$rules, $allowed('RCV', 'RCV-ZAKAT'), $prohibited('PAY', 'PAY-OPERASIONAL-MASJID', null, 'Dana Zakat Maal tidak boleh digunakan untuk operasional umum.')],
            'INFAQ-TROMOL' => [...$rules, $allowed('RCV', 'RCV-INFAK-JUMAT'), $allowed('RCV', 'RCV-KOTAK-AMAL'), $allowed('RCV', 'RCV-TROMOL'), $allowed('RCV', 'RCV-DONASI')],
            'SODAQOH' => [...$rules, $allowed('RCV', 'RCV-SODAQOH'), $allowed('RCV', 'RCV-DONASI')],
            'SANTUNAN-YATIM' => [...$rules, $allowed('RCV', 'RCV-DONASI'), $allowed('PAY', 'PAY-SANTUNAN', 'SANTUNAN-YATIM-BULANAN', 'Santunan Anak Yatim Bulanan diperbolehkan.')],
            'FIDYAH' => [...$rules, $allowed('RCV', 'RCV-FIDYAH')],
            'DHUAFA' => [...$rules, $allowed('RCV', 'RCV-DONASI'), $allowed('PAY', 'PAY-DHUAFA', 'BANTUAN-DHUAFA')],
            'QURBAN' => [...$rules, $allowed('RCV', 'RCV-QURBAN', 'QURBAN'), $allowed('PAY', 'PAY-PEMBELIAN-HEWAN', 'QURBAN'), $allowed('PAY', 'PAY-OPERASIONAL-QURBAN', 'QURBAN'), $allowed('PAY', 'PAY-DISTRIBUSI-QURBAN', 'QURBAN')],
            'RAMADHAN' => [...$rules, $allowed('RCV', 'RCV-DONASI'), $allowed('PAY', 'PAY-RAMADHAN', 'KEGIATAN-RAMADHAN')],
            'SOSIAL-KEMATIAN' => [...$rules, $allowed('RCV', 'RCV-DONASI'), $allowed('PAY', 'PAY-BANTUAN-KEMATIAN', 'SOSIAL-KEMATIAN'), $allowed('PAY', 'PAY-BANTUAN-SOSIAL', 'SOSIAL-KEMATIAN')],
            'SEWA-AULA' => [...$rules, $allowed('RCV', 'RCV-SEWA-AULA', 'SEWA-AULA'), $allowed('PAY', 'PAY-SEWA-AULA', 'SEWA-AULA')],
            default => $rules,
        };
        foreach ($rules as $rule) {
            $exists = FundPolicyRule::query()->where('fund_policy_version_id', $version->id)->where('transaction_type_id', $rule['transaction_type_id'])->where('decision', $rule['decision'])->where('category_id', $rule['category_id'])->where('program_id', $rule['program_id'])->exists();
            if (! $exists) {
                $masters->createFundPolicyRule($this->entity->id, $version->id, $rule, $this->actorUserId);
            }
        }
    }

    /** @return array<string, int> */
    private function financialFactSnapshot(): array
    {
        return [
            'transactions' => FinancialTransaction::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'journals' => Journal::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'journal_lines' => JournalLine::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'opening_batches' => DB::table('financial_v2_opening_balance_batches')->where('accounting_entity_id', $this->entity->id)->count(),
            'opening_lines' => DB::table('financial_v2_opening_balance_lines')->where('accounting_entity_id', $this->entity->id)->count(),
        ];
    }

    private function recordNew(AuditTrailService $audit, object $model, string $targetType, string $event): void
    {
        if (! $model->wasRecentlyCreated) {
            return;
        }
        $audit->record($this->entity->id, $event, $targetType, $model->id, (string) Str::uuid(), $this->actorUserId, null, ['code' => $model->code ?? null, 'name' => $model->name ?? null]);
    }
}
