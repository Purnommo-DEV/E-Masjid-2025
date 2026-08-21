<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\OpeningBalanceService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\LegacyMapping;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Controlled local onboarding for the owner-approved MRJ ZISWAF opening
 * position. It never migrates historical transactions: the only accounting
 * fact is the approved 27 June 2026 opening balance posted by PostingEngine.
 */
final class OnboardMrjZiswafActualCommand extends Command
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    private const SAMPLE_ENTITY_CODE = 'MRJ-SAMPLE-QA';

    private const MAPPING_CODE = 'MRJ-ZISWAF-OB-20260627-V1';

    private const BATCH_REFERENCE = 'MRJ-ZISWAF-OPENING-2026-06-27-V1';

    protected $signature = 'financial-v2:onboard-mrj-ziswaf
        {source : Absolute path of the final ZISWAF UPDATE 3.xlsx source archive}
        {evidence : Absolute path of the source-evidence PDF}
        {--purge-sample-qa : Remove only the exact MRJ-SAMPLE-QA local fixture after successful onboarding}
        {--dry-run : Validate source, source mapping, and SAMPLE/QA inventory without database writes}
        {--allow-testing : Permit execution only when the isolated mrj_test_db test database is active}';

    protected $description = 'Onboard the approved MRJ ZISWAF 27 June 2026 opening position through the Financial V2 Posting Engine.';

    /** @var array<string, Account> */
    private array $accounts = [];

    /** @var array<string, FinancialAccount> */
    private array $financialAccounts = [];

    /** @var array<string, Fund> */
    private array $funds = [];

    private int $actorUserId;

    private AccountingEntity $entity;

    public function handle(
        FinancialMasterDataService $masters,
        MasterDataGovernanceService $governance,
        OpeningBalanceService $openingBalances,
        EvidenceService $evidence,
        BalanceInquiryService $balances,
        FinancialReportService $reports,
    ): int {
        $this->assertPermittedEnvironment();
        MrjZiswafOpeningPosition::assertIntegrity();

        $source = $this->requiredFile((string) $this->argument('source'), 'final source workbook');
        $evidencePdf = $this->requiredFile((string) $this->argument('evidence'), 'source-evidence PDF');
        $sourceHealth = $this->sourceHealth();
        $sampleBefore = $this->sampleInventory();

        if ($this->option('dry-run')) {
            $this->info('DRY RUN ONLY - no database or storage write was performed.');
            $this->table(['Check', 'Status'], [
                ['Manifest reconciles', 'PASS'],
                ['Source workbook present', basename($source)],
                ['Evidence PDF present', basename($evidencePdf)],
                ['BNI + Cash = total', $sourceHealth['liquidity_total']],
                ['SAMPLE/QA entity', $sampleBefore['exists'] ? 'INVENTORIED' : 'NOT PRESENT'],
            ]);
            $this->printInventory($sampleBefore);

            return self::SUCCESS;
        }

        $this->actorUserId = $this->qaUser()->id;
        $this->entity = $this->ensureActualEntity();
        $this->ensureCalendarAndPeriod();
        $this->ensureAccounts();
        $this->ensureOpeningTransactionTypeAndRule($governance);
        $this->ensureFinancialAccounts($masters, $governance);
        $this->ensureFundsAndOpeningPolicies($masters, $governance);

        $archives = $this->archiveEvidence($source, $evidencePdf);
        $batch = $this->ensureOpeningBalance($openingBalances, $evidence, $archives);
        $reconciliation = $this->assertPostedPosition($batch, $balances, $reports);

        $cleanup = ['performed' => false, 'before' => $sampleBefore, 'after' => $sampleBefore];
        if ($this->option('purge-sample-qa')) {
            $cleanup = $this->purgeExactSampleEntity($sampleBefore);
        }

        $this->info('MRJ ZISWAF opening balance has been posted through the V2 Posting Engine.');
        $this->table(['Control', 'Result'], [
            ['Opening-balance batch', $batch->fresh()->status],
            ['Journal / Ledger', $reconciliation['journal_id'].' / '.$reconciliation['ledger_count'].' entries'],
            ['BNI ZISWAF', self::idr($reconciliation['bni'])],
            ['Cash Tromol Yatim', self::idr($reconciliation['cash'])],
            ['Fund total', self::idr($reconciliation['fund_total'])],
            ['Trial balance', $reconciliation['trial_balance'] ? 'BALANCED' : 'FAILED'],
            ['SAMPLE/QA cleanup', $cleanup['performed'] ? 'COMPLETED' : 'NOT REQUESTED'],
        ]);

        return self::SUCCESS;
    }

    private function assertPermittedEnvironment(): void
    {
        $database = (string) config('database.connections.'.config('database.default').'.database');
        if ($this->option('allow-testing')) {
            if (! app()->environment('testing') || $database !== 'mrj_test_db') {
                throw new RuntimeException('Testing onboarding is permitted only in APP_ENV=testing on mrj_test_db.');
            }

            return;
        }

        if (! app()->environment('local') || $database !== 'mrj_prod_db') {
            throw new RuntimeException('Actual onboarding is permitted only in local development on mrj_prod_db.');
        }
    }

    private function requiredFile(string $path, string $label): string
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("The {$label} is missing or unreadable: {$path}");
        }

        return $path;
    }

    /** @return array{liquidity_total:string,source_reference:string} */
    private function sourceHealth(): array
    {
        $cash = MrjZiswafOpeningPosition::cashResolution();

        return [
            'liquidity_total' => DecimalAmount::add(MrjZiswafOpeningPosition::BNI_TOTAL, MrjZiswafOpeningPosition::CASH_TOTAL),
            'source_reference' => $cash['source_reference'],
        ];
    }

    private function qaUser(): User
    {
        return User::query()->firstOrCreate(
            ['email' => 'superadmin@emasjid.com'],
            [
                'name' => 'Super Admin (Local QA)',
                'password' => Hash::make('password'),
            ],
        );
    }

    private function ensureActualEntity(): AccountingEntity
    {
        return AccountingEntity::query()->firstOrCreate(
            ['code' => self::ENTITY_CODE],
            [
                'name' => 'Masjid Raudhotul Jannah',
                'legal_name' => 'Masjid Raudhotul Jannah',
                'functional_currency' => 'IDR',
                'timezone' => 'Asia/Jakarta',
                'fiscal_year_start_month' => 1,
                'status' => 'active',
                'created_by_user_id' => $this->actorUserId,
                'updated_by_user_id' => $this->actorUserId,
            ],
        );
    }

    private function ensureCalendarAndPeriod(): void
    {
        $calendar = AccountingCalendar::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'MRJ-2026'],
            [
                'name' => 'Kalender Keuangan MRJ 2026',
                'fiscal_year_label' => '2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'active',
                'created_by_user_id' => $this->actorUserId,
                'updated_by_user_id' => $this->actorUserId,
            ],
        );

        foreach (range(1, 12) as $month) {
            $start = CarbonImmutable::create(2026, $month, 1);
            AccountingPeriod::query()->firstOrCreate(
                ['accounting_calendar_id' => $calendar->id, 'period_no' => $month],
                [
                    'accounting_entity_id' => $this->entity->id,
                    'period_name' => 'MRJ '.$start->translatedFormat('F Y'),
                    'start_date' => $start->toDateString(),
                    'end_date' => $start->endOfMonth()->toDateString(),
                    'status' => 'open',
                    'created_by_user_id' => $this->actorUserId,
                    'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    private function ensureAccounts(): void
    {
        $asset = AccountGroup::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'AST'],
            [
                'name' => 'Aset', 'group_class' => 'asset', 'status' => 'active', 'valid_from' => '2026-01-01',
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );
        $netAsset = AccountGroup::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'NET'],
            [
                'name' => 'Aset Bersih Dana', 'group_class' => 'net_asset', 'status' => 'active', 'valid_from' => '2026-01-01',
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );

        $definitions = [
            'LIQ-ZIS' => [$asset->id, 'Likuiditas ZISWAF', 'asset', 'debit', true],
            'NET-ZIS' => [$netAsset->id, 'Saldo Dana ZISWAF', 'net_asset', 'credit', false],
        ];
        foreach ($definitions as $code => [$groupId, $name, $class, $normalBalance, $liquidity]) {
            $this->accounts[$code] = Account::query()->firstOrCreate(
                ['accounting_entity_id' => $this->entity->id, 'code' => $code],
                [
                    'account_group_id' => $groupId, 'name' => $name, 'account_class' => $class,
                    'normal_balance' => $normalBalance, 'is_posting_account' => true,
                    'is_liquidity_account' => $liquidity, 'is_control_account' => false,
                    'allow_manual_posting' => false, 'status' => 'active', 'valid_from' => '2026-01-01',
                    'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
                ],
            );
        }
    }

    private function ensureOpeningTransactionTypeAndRule(MasterDataGovernanceService $governance): void
    {
        $type = TransactionType::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'OPB'],
            [
                'name' => 'Saldo Awal', 'voucher_prefix' => 'OPB', 'has_financial_impact' => true,
                'status' => 'active', 'valid_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );

        $rule = PostingRule::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'MRJ-ZIS-OPB'],
            [
                'transaction_type_id' => $type->id, 'name' => 'Saldo Awal ZISWAF', 'rule_family' => 'opening-balance',
                'status' => 'active', 'valid_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );
        $version = PostingRuleVersion::query()->firstOrCreate(
            ['posting_rule_id' => $rule->id, 'version_no' => 1],
            [
                'accounting_entity_id' => $this->entity->id, 'effective_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'input_contract_ref' => self::BATCH_REFERENCE, 'journal_template_ref' => 'OPENING-BALANCE-SERVICE',
                'business_rule_refs' => 'OpeningBalanceService; PostingEngine', 'status' => 'draft',
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );
        if ($version->status === 'draft') {
            foreach ([
                [1, $this->accounts['LIQ-ZIS']->id, 'debit'],
                [2, $this->accounts['NET-ZIS']->id, 'credit'],
            ] as [$lineNo, $accountId, $entrySide]) {
                PostingRuleLine::query()->firstOrCreate(
                    ['posting_rule_version_id' => $version->id, 'line_no' => $lineNo],
                    [
                        'accounting_entity_id' => $this->entity->id, 'account_id' => $accountId, 'entry_side' => $entrySide,
                        'amount_source' => 'split_amount', 'financial_account_source' => 'none', 'fund_source' => 'split',
                        'program_source' => 'none', 'cost_center_source' => 'none', 'counterparty_source' => 'none', 'category_source' => 'none',
                        'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
                    ],
                );
            }
            $governance->makePostingRuleVersionEffective($version->id, $this->actorUserId);
        }

        DocumentSequence::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'MRJ-ZIS-OPB'],
            [
                'transaction_type_id' => $type->id, 'name' => 'Voucher Saldo Awal ZISWAF', 'prefix' => 'OPB',
                'scope_key' => 'mrj-ziswaf-opening-balance', 'next_value' => 1, 'reset_rule' => 'never', 'status' => 'active',
                'valid_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );
    }

    private function ensureFinancialAccounts(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        $definitions = [
            'BNI-ZISWAF' => ['BNI ZISWAF', 'bank', ['bank_name' => 'BNI', 'account_number_masked' => 'N/A - source position only']],
            'CASH-ZISWAF' => ['Cash Tromol Yatim', 'cash', ['cash_location' => 'Kas Tromol Yatim', 'cash_count_frequency' => 'ad_hoc']],
        ];
        foreach ($definitions as $code => [$name, $type, $detail]) {
            $account = FinancialAccount::query()->where('accounting_entity_id', $this->entity->id)->where('code', $code)->first();
            if (! $account) {
                $account = $masters->createFinancialAccount($this->entity->id, [
                    'account_id' => $this->accounts['LIQ-ZIS']->id, 'code' => $code, 'name' => $name,
                    'account_type' => $type, 'currency_code' => 'IDR', 'opening_date' => MrjZiswafOpeningPosition::AS_OF_DATE,
                ] + $detail, $this->actorUserId);
            }
            if ($account->status === 'draft') {
                $account = $governance->activateFinancialAccount($account->id, MrjZiswafOpeningPosition::AS_OF_DATE, $this->actorUserId);
            }
            $this->financialAccounts[$code] = $account->fresh();
        }
    }

    private function ensureFundsAndOpeningPolicies(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        $type = FundType::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'RESTRICTED'],
            [
                'name' => 'Dana Terikat', 'classification' => 'restricted', 'status' => 'active',
                'valid_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );
        $restriction = FundRestriction::query()->firstOrCreate(
            ['accounting_entity_id' => $this->entity->id, 'code' => 'ZISWAF-RESTRICTED'],
            [
                'fund_type_id' => $type->id, 'name' => 'Terikat sesuai kebijakan Dana ZISWAF', 'severity' => 'high',
                'policy_basis' => 'Opening source ZISWAF UPDATE 3.xlsx; operational use remains fail-closed until an approved policy matrix is added.',
                'status' => 'active', 'valid_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'created_by_user_id' => $this->actorUserId, 'updated_by_user_id' => $this->actorUserId,
            ],
        );
        $opbType = TransactionType::query()->where('accounting_entity_id', $this->entity->id)->where('code', 'OPB')->firstOrFail();

        foreach (MrjZiswafOpeningPosition::funds() as $definition) {
            $fund = Fund::query()->where('accounting_entity_id', $this->entity->id)->where('code', $definition['code'])->first();
            if (! $fund) {
                $fund = $masters->createFund($this->entity->id, [
                    'fund_type_id' => $type->id, 'fund_restriction_id' => $restriction->id,
                    'code' => $definition['code'], 'name' => $definition['name'],
                    'purpose_statement' => 'Opening position from '.MrjZiswafOpeningPosition::SOURCE_FILENAME.' '.$definition['source_range'].'.',
                    'prohibited_use_statement' => 'Operational use is fail-closed until a governed Fund policy permits it.',
                    'allow_negative_balance' => false,
                ], $this->actorUserId);
            }
            $policy = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('version_no', 1)->first();
            if (! $policy) {
                $policy = $masters->createFundPolicyVersion($this->entity->id, [
                    'fund_id' => $fund->id, 'effective_from' => MrjZiswafOpeningPosition::AS_OF_DATE,
                    'policy_document_ref' => MrjZiswafOpeningPosition::SOURCE_FILENAME.'|'.$definition['source_range'],
                    'allowed_matrix_ref' => 'OPENING-BALANCE-ONLY; operational transactions are fail-closed pending governance.',
                    'exception_approval_level' => 'financial-governance',
                ], $this->actorUserId);
            }
            if ($policy->status === 'draft') {
                $hasOpeningRule = FundPolicyRule::query()
                    ->where('fund_policy_version_id', $policy->id)
                    ->where('transaction_type_id', $opbType->id)
                    ->where('decision', 'allowed')
                    ->exists();
                if (! $hasOpeningRule) {
                    $masters->createFundPolicyRule($this->entity->id, $policy->id, [
                        'transaction_type_id' => $opbType->id, 'decision' => 'allowed',
                        'rationale' => 'Permits only the evidence-backed Opening Balance V2 posting; operational use remains fail-closed.',
                    ], $this->actorUserId);
                }
                $governance->makeFundPolicyVersionEffective($policy->id, $this->actorUserId);
            }
            if ($fund->status === 'draft') {
                $fund = $governance->activateFund($fund->id, MrjZiswafOpeningPosition::AS_OF_DATE, $this->actorUserId);
            }
            $this->funds[$definition['code']] = $fund->fresh();
        }
    }

    /** @return array{source_archive:string,evidence_archive:string,evidence_hash:string,evidence_size:int} */
    private function archiveEvidence(string $source, string $evidencePdf): array
    {
        $sourceHash = hash_file('sha256', $source);
        $evidenceHash = hash_file('sha256', $evidencePdf);
        if ($sourceHash === false || $evidenceHash === false) {
            throw new RuntimeException('Source evidence hashing failed.');
        }
        $disk = Storage::disk('local');
        $sourceArchive = 'financial-v2/source-archive/mrj-ziswaf/'.MrjZiswafOpeningPosition::AS_OF_DATE.'/'.$sourceHash.'.xlsx';
        $evidenceArchive = 'financial-v2/opening-evidence/mrj-ziswaf/'.MrjZiswafOpeningPosition::AS_OF_DATE.'/'.$evidenceHash.'.pdf';
        if (! $disk->exists($sourceArchive)) {
            $disk->put($sourceArchive, file_get_contents($source));
        }
        if (! $disk->exists($evidenceArchive)) {
            $disk->put($evidenceArchive, file_get_contents($evidencePdf));
        }

        return [
            'source_archive' => $sourceArchive,
            'evidence_archive' => $evidenceArchive,
            'evidence_hash' => $evidenceHash,
            'evidence_size' => (int) filesize($evidencePdf),
        ];
    }

    /** @param array{source_archive:string,evidence_archive:string,evidence_hash:string,evidence_size:int} $archives */
    private function ensureOpeningBalance(OpeningBalanceService $openingBalances, EvidenceService $evidence, array $archives): OpeningBalanceBatch
    {
        $mapping = MappingSet::query()->where('accounting_entity_id', $this->entity->id)->where('code', self::MAPPING_CODE)->first();
        if (! $mapping) {
            $mapping = $openingBalances->createMappingSet([
                'accounting_entity_id' => $this->entity->id, 'code' => self::MAPPING_CODE,
                'name' => 'Mapping Saldo Awal ZISWAF MRJ 27 Juni 2026',
                'source_system_name' => MrjZiswafOpeningPosition::SOURCE_FILENAME,
                'position_date' => MrjZiswafOpeningPosition::AS_OF_DATE,
            ], $this->actorUserId);
        }
        $this->ensureMappings($openingBalances, $mapping);
        if ($mapping->fresh()->mapping_status === 'draft') {
            $mapping = $openingBalances->reviewMappingSet($mapping->id, $this->actorUserId);
        }
        if ($mapping->fresh()->mapping_status === 'reviewed') {
            $mapping = $openingBalances->approveMappingSet($mapping->id, $this->actorUserId);
        }
        if (! in_array($mapping->fresh()->mapping_status, ['approved', 'frozen'], true)) {
            throw new RuntimeException('Opening-balance mapping set is not approved.');
        }

        $period = AccountingPeriod::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('start_date', '<=', MrjZiswafOpeningPosition::AS_OF_DATE)
            ->where('end_date', '>=', MrjZiswafOpeningPosition::AS_OF_DATE)
            ->firstOrFail();
        $batch = OpeningBalanceBatch::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('cutover_reference', self::BATCH_REFERENCE)
            ->first();
        if (! $batch) {
            $batch = $openingBalances->createDraft([
                'accounting_entity_id' => $this->entity->id, 'accounting_period_id' => $period->id,
                'mapping_set_id' => $mapping->id, 'position_date' => MrjZiswafOpeningPosition::AS_OF_DATE,
                'rehearsal_reference' => self::BATCH_REFERENCE,
                'evidence_package_ref' => 'source_xlsx='.$archives['source_archive'].'; evidence_pdf='.$archives['evidence_archive'].'; cash_resolution='.MrjZiswafOpeningPosition::cashResolution()['source_reference'],
            ], $this->actorUserId);
        }
        if ($batch->status === 'posted') {
            return $batch;
        }
        if ($batch->status !== 'draft') {
            throw new RuntimeException("Opening balance batch is in unexpected {$batch->status} state.");
        }

        foreach (MrjZiswafOpeningPosition::liquidityLines() as $line) {
            $this->ensureOpeningLine($openingBalances, $batch, [
                'account_id' => $this->accounts['LIQ-ZIS']->id, 'fund_id' => $this->funds[$line['fund_code']]->id,
                'financial_account_id' => $this->financialAccounts[$line['financial_account_code']]->id,
                'debit_amount' => $line['amount'], 'credit_amount' => '0.00',
                'source_debit_amount' => $line['amount'], 'source_credit_amount' => '0.00',
                'source_reference' => $line['source_reference'],
                'evidence_ref' => $this->evidenceRef($archives), 'line_description' => $line['description'],
            ]);
        }
        foreach (MrjZiswafOpeningPosition::fundNetAssetLines() as $line) {
            $this->ensureOpeningLine($openingBalances, $batch, [
                'account_id' => $this->accounts['NET-ZIS']->id, 'fund_id' => $this->funds[$line['fund_code']]->id,
                'debit_amount' => '0.00', 'credit_amount' => $line['amount'],
                'source_debit_amount' => '0.00', 'source_credit_amount' => $line['amount'],
                'source_reference' => $line['source_reference'],
                'evidence_ref' => $this->evidenceRef($archives), 'line_description' => $line['description'],
            ]);
        }

        $lines = OpeningBalanceLine::query()->where('opening_balance_batch_id', $batch->id)->orderBy('line_no')->get();
        if ($lines->count() !== 13) {
            throw new RuntimeException('MRJ ZISWAF opening balance must contain exactly 13 source-backed lines.');
        }
        foreach ($lines as $line) {
            if (! AttachmentLink::query()->where('accounting_entity_id', $this->entity->id)->where('target_type', 'opening_balance_line')->where('target_id', $line->id)->where('status', 'active')->exists()) {
                $evidence->attachToOpeningBalanceLine(
                    $this->entity->id, $line->id, basename($archives['evidence_archive']), 'application/pdf',
                    $archives['evidence_size'], $archives['evidence_hash'], $archives['evidence_archive'], 'statement', $this->actorUserId,
                );
            }
        }

        $openingBalances->reconcile($batch->id, $this->actorUserId);
        $batch = $openingBalances->review($batch->id, $this->actorUserId);
        $batch = $openingBalances->approve($batch->id, $this->actorUserId);
        $openingBalances->post($batch->id, $this->actorUserId);

        return $batch->fresh();
    }

    private function ensureMappings(OpeningBalanceService $openingBalances, MappingSet $mapping): void
    {
        foreach (MrjZiswafOpeningPosition::liquidityLines() as $line) {
            $this->ensureMapping($openingBalances, $mapping, $line['source_reference'], 'account', $this->accounts['LIQ-ZIS']->id, 'LIQ-ZIS');
            $this->ensureMapping($openingBalances, $mapping, $line['source_reference'], 'fund', $this->funds[$line['fund_code']]->id, $line['fund_code']);
            $this->ensureMapping($openingBalances, $mapping, $line['source_reference'], 'financial_account', $this->financialAccounts[$line['financial_account_code']]->id, $line['financial_account_code']);
        }
        foreach (MrjZiswafOpeningPosition::fundNetAssetLines() as $line) {
            $this->ensureMapping($openingBalances, $mapping, $line['source_reference'], 'account', $this->accounts['NET-ZIS']->id, 'NET-ZIS');
            $this->ensureMapping($openingBalances, $mapping, $line['source_reference'], 'fund', $this->funds[$line['fund_code']]->id, $line['fund_code']);
        }
    }

    private function ensureMapping(OpeningBalanceService $openingBalances, MappingSet $mapping, string $sourceReference, string $dimension, string $targetId, string $sourceValue): void
    {
        $reference = $sourceReference.'|'.$dimension;
        $existing = LegacyMapping::query()->where('mapping_set_id', $mapping->id)->where('legacy_record_ref', $reference)->first();
        if ($existing) {
            if ($existing->target_entity_type !== $dimension || $existing->target_entity_id !== $targetId || ! in_array($existing->mapping_status, ['confirmed', 'frozen'], true)) {
                throw new RuntimeException("Existing mapping {$reference} does not match the approved MRJ ZISWAF source mapping.");
            }

            return;
        }
        $openingBalances->recordMapping($mapping->id, $sourceReference, $dimension, 'mapped', $targetId, $sourceValue, 'Cell-level source mapping from '.MrjZiswafOpeningPosition::SOURCE_FILENAME.'.', $this->actorUserId);
    }

    /** @param array<string, string> $input */
    private function ensureOpeningLine(OpeningBalanceService $openingBalances, OpeningBalanceBatch $batch, array $input): void
    {
        $existing = OpeningBalanceLine::query()->where('opening_balance_batch_id', $batch->id)->where('source_reference', $input['source_reference'])->first();
        if ($existing) {
            if ($existing->account_id !== $input['account_id']
                || $existing->fund_id !== $input['fund_id']
                || ($input['financial_account_id'] ?? null) !== $existing->financial_account_id
                || ! DecimalAmount::equals($existing->debit_amount, $input['debit_amount'])
                || ! DecimalAmount::equals($existing->credit_amount, $input['credit_amount'])) {
                throw new RuntimeException('Existing opening line does not match the approved source manifest.');
            }

            return;
        }
        $openingBalances->addLine($batch->id, $input, $this->actorUserId);
    }

    /** @param array{source_archive:string,evidence_archive:string,evidence_hash:string,evidence_size:int} $archives */
    private function evidenceRef(array $archives): string
    {
        return 'source_xlsx='.$archives['source_archive'].'; evidence_pdf='.$archives['evidence_archive'].'; as_of='.MrjZiswafOpeningPosition::AS_OF_DATE;
    }

    /** @return array{journal_id:string,ledger_count:int,bni:string,cash:string,fund_total:string,trial_balance:bool} */
    private function assertPostedPosition(OpeningBalanceBatch $batch, BalanceInquiryService $balances, FinancialReportService $reports): array
    {
        $batch = $batch->fresh();
        if ($batch->status !== 'posted' || ! $batch->journal_id) {
            throw new RuntimeException('Opening balance did not reach posted state.');
        }
        $bni = $balances->financialAccountBalance($this->entity->id, $this->financialAccounts['BNI-ZISWAF']->id, MrjZiswafOpeningPosition::AS_OF_DATE)['balance'];
        $cash = $balances->financialAccountBalance($this->entity->id, $this->financialAccounts['CASH-ZISWAF']->id, MrjZiswafOpeningPosition::AS_OF_DATE)['balance'];
        $fundReport = $reports->report('fund-balance', $this->entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data'];
        $actualFunds = collect($fundReport['rows'])->keyBy('code');
        foreach (MrjZiswafOpeningPosition::funds() as $fund) {
            $row = $actualFunds->get($fund['code']);
            if (! $row || ! DecimalAmount::equals($row['fund_balance'], $fund['total'])) {
                throw new RuntimeException("Fund {$fund['code']} does not reconcile to the approved source position.");
            }
        }
        $fundTotal = DecimalAmount::sum($actualFunds->map(fn (array $row) => $row['fund_balance'])->all());
        $trial = $reports->report('trial-balance', $this->entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data'];
        $journal = Journal::query()->whereKey($batch->journal_id)->firstOrFail();
        $ledgerCount = LedgerEntry::query()->where('accounting_entity_id', $this->entity->id)->whereIn('journal_line_id', JournalLine::query()->where('journal_id', $journal->id)->select('id'))->count();

        if (! DecimalAmount::equals($bni, MrjZiswafOpeningPosition::BNI_TOTAL)
            || ! DecimalAmount::equals($cash, MrjZiswafOpeningPosition::CASH_TOTAL)
            || ! DecimalAmount::equals($fundTotal, MrjZiswafOpeningPosition::TOTAL)
            || ! DecimalAmount::equals(DecimalAmount::add($bni, $cash), $fundTotal)
            || ! $trial['is_balanced']
            || ! DecimalAmount::equals($journal->total_debit, $journal->total_credit)
            || $ledgerCount !== 13) {
            throw new RuntimeException('Posted MRJ ZISWAF position failed a ledger, trial-balance, fund, or liquidity reconciliation.');
        }

        return ['journal_id' => $journal->id, 'ledger_count' => $ledgerCount, 'bni' => $bni, 'cash' => $cash, 'fund_total' => $fundTotal, 'trial_balance' => $trial['is_balanced']];
    }

    /** @return array{exists:bool,entity_id:?string,counts:array<string,int>} */
    private function sampleInventory(): array
    {
        $sample = AccountingEntity::query()->where('code', self::SAMPLE_ENTITY_CODE)->first();
        if (! $sample) {
            return ['exists' => false, 'entity_id' => null, 'counts' => []];
        }

        return ['exists' => true, 'entity_id' => $sample->id, 'counts' => $this->entityCounts($sample->id)];
    }

    /** @return array{performed:bool,before:array{exists:bool,entity_id:?string,counts:array<string,int>},after:array{exists:bool,entity_id:?string,counts:array<string,int>}} */
    private function purgeExactSampleEntity(array $before): array
    {
        if (! $before['exists'] || ! $before['entity_id']) {
            return ['performed' => false, 'before' => $before, 'after' => $before];
        }
        $actualBefore = $this->entityCounts($this->entity->id);
        $tables = $this->financialV2Tables();
        $entityLessTables = array_values(array_filter(
            $tables,
            fn (string $table): bool => $table !== 'financial_v2_accounting_entities' && ! Schema::hasColumn($table, 'accounting_entity_id'),
        ));
        $supportedEntityLess = ['financial_v2_bank_account_details', 'financial_v2_cash_account_details'];
        sort($entityLessTables);
        sort($supportedEntityLess);
        if ($entityLessTables !== $supportedEntityLess) {
            throw new RuntimeException('Refusing SAMPLE/QA cleanup because the scoped Financial V2 table inventory changed.');
        }

        $sampleId = $before['entity_id'];
        $financialAccountIds = DB::table('financial_v2_financial_accounts')->where('accounting_entity_id', $sampleId)->pluck('id')->all();
        DB::transaction(function () use ($tables, $sampleId, $financialAccountIds): void {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                DB::table('financial_v2_bank_account_details')->whereIn('financial_account_id', $financialAccountIds)->delete();
                DB::table('financial_v2_cash_account_details')->whereIn('financial_account_id', $financialAccountIds)->delete();
                foreach ($tables as $table) {
                    if (in_array($table, ['financial_v2_accounting_entities', 'financial_v2_bank_account_details', 'financial_v2_cash_account_details'], true)) {
                        continue;
                    }
                    if (Schema::hasColumn($table, 'accounting_entity_id')) {
                        DB::table($table)->where('accounting_entity_id', $sampleId)->delete();
                    }
                }
                DB::table('financial_v2_accounting_entities')->where('id', $sampleId)->where('code', self::SAMPLE_ENTITY_CODE)->delete();
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }, 3);

        $after = $this->sampleInventory();
        if ($after['exists'] || $this->entityCounts($this->entity->id) !== $actualBefore) {
            throw new RuntimeException('SAMPLE/QA cleanup verification failed; actual Financial V2 records were not accepted as changed.');
        }

        return ['performed' => true, 'before' => $before, 'after' => $after];
    }

    /** @return array<string, int> */
    private function entityCounts(string $entityId): array
    {
        $counts = [];
        foreach ($this->financialV2Tables() as $table) {
            if ($table !== 'financial_v2_accounting_entities' && Schema::hasColumn($table, 'accounting_entity_id')) {
                $counts[$table] = DB::table($table)->where('accounting_entity_id', $entityId)->count();
            }
        }

        return $counts;
    }

    /** @return array<int, string> */
    private function financialV2Tables(): array
    {
        return collect(Schema::getTables())
            ->pluck('name')
            ->filter(fn (string $name): bool => Str::startsWith($name, 'financial_v2_'))
            ->sort()
            ->values()
            ->all();
    }

    /** @param array{exists:bool,entity_id:?string,counts:array<string,int>} $inventory */
    private function printInventory(array $inventory): void
    {
        if (! $inventory['exists']) {
            $this->line('SAMPLE/QA inventory: no MRJ-SAMPLE-QA entity exists.');

            return;
        }
        $rows = collect($inventory['counts'])->filter()->map(fn (int $count, string $table): array => [$table, $count])->values()->all();
        $this->table(['SAMPLE/QA table', 'Records'], $rows ?: [['No V2 child records', 0]]);
    }

    private static function idr(string $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}
