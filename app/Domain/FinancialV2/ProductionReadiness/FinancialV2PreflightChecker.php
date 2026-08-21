<?php

namespace App\Domain\FinancialV2\ProductionReadiness;

use App\Domain\FinancialV2\OpeningBalanceService;
use App\Domain\FinancialV2\PeriodClosingService;
use App\Domain\FinancialV2\PostingEngine;
use App\Domain\FinancialV2\ReconciliationService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only readiness inspection. It never invokes a migration, DDL, posting,
 * opening-balance, or operational workflow. A non-production environment is
 * deliberately reported as a simulation, not as a production approval.
 */
final class FinancialV2PreflightChecker
{
    /** @var list<string> */
    private const REQUIRED_TABLES = [
        'financial_v2_accounting_entities',
        'financial_v2_accounting_calendars',
        'financial_v2_accounting_periods',
        'financial_v2_accounts',
        'financial_v2_financial_accounts',
        'financial_v2_funds',
        'financial_v2_fund_policy_versions',
        'financial_v2_programs',
        'financial_v2_categories',
        'financial_v2_posting_rule_versions',
        'financial_v2_document_sequences',
        'financial_v2_transactions',
        'financial_v2_journals',
        'financial_v2_journal_lines',
        'financial_v2_ledger_entries',
        'financial_v2_opening_balance_batches',
        'financial_v2_opening_balance_lines',
        'financial_v2_reconciliations',
    ];

    /** @var array<string, list<string>> */
    private const REQUIRED_INDEXES = [
        'financial_v2_journals' => ['fv2_journal_entity_sequence_uq'],
        'financial_v2_vouchers' => ['fv2_voucher_entity_number_uq'],
        'financial_v2_idempotency_keys' => ['fv2_idempotency_scope_key_uq'],
        'financial_v2_document_sequences' => ['fv2_doc_seq_scope_uq'],
        'financial_v2_opening_balance_lines' => ['fv2_open_line_fin_acc_ix', 'fv2_open_line_program_ix', 'fv2_open_line_recon_status_ix'],
    ];

    /** @var list<string> */
    private const REQUIRED_CONSTRAINTS = [
        'fv2_journal_total_ck',
        'fv2_jl_one_side_ck',
        'fv2_tx_amount_ck',
        'fv2_open_line_one_side_ck',
        'fv2_treasury_transfer_accounts_ck',
        'fv2_interfund_transfer_funds_ck',
    ];

    /** @var list<class-string> */
    private const REQUIRED_SERVICES = [
        PostingEngine::class,
        FinancialReportService::class,
        PeriodClosingService::class,
        ReconciliationService::class,
        OpeningBalanceService::class,
    ];

    public function __construct(
        private readonly Application $app,
        private readonly ConnectionInterface $connection,
    ) {}

    /** @return array<string, mixed> */
    public function inspect(string $expectedEnvironment = 'production', bool $simulation = false): array
    {
        $environment = (string) $this->app->environment();
        $database = (string) $this->connection->getDatabaseName();
        if ($simulation && ($environment !== 'testing' || $database !== 'mrj_test_db')) {
            return $this->refusal($expectedEnvironment, $environment, $database, 'Simulation is permitted only for APP_ENV=testing and DB_DATABASE=mrj_test_db. No database query was executed.');
        }
        if (! $simulation && $environment !== $expectedEnvironment) {
            return $this->refusal($expectedEnvironment, $environment, $database, "Target preflight requires APP_ENV={$expectedEnvironment}. Use --simulate only with testing/mrj_test_db. No database query was executed.");
        }

        $schema = $this->schemaChecks();
        $checks = [
            $this->check('environment', $simulation ? 'simulation' : 'pass', $simulation ? 'Read-only simulation is running against the safeguarded testing environment.' : "Target environment {$expectedEnvironment} is active."),
            $this->check('database', $database !== '' ? 'pass' : 'fail', $database !== '' ? "Connected database: {$database}." : 'Database name is unavailable.'),
            $this->check('application_version', 'pass', 'Laravel '.\Illuminate\Foundation\Application::VERSION.'; application version is supplied by the deployed build.'),
            ...$this->migrationChecks(),
            ...$schema,
            ...$this->serviceChecks(),
            ...$this->testIsolationChecks(),
            ...$this->legacyIsolationChecks(),
        ];

        $masterData = $this->masterDataChecks($schema);
        $technicalStatus = collect($checks)->contains(fn (array $check) => $check['status'] === 'fail') ? 'not_ready' : 'pass';
        $dataStatus = collect($masterData)->contains(fn (array $check) => in_array($check['status'], ['missing', 'conflict'], true)) ? 'not_ready' : 'not_verified';
        $governanceStatus = 'not_verified';
        $operationalStatus = $simulation ? 'simulation' : ($technicalStatus === 'pass' ? 'not_verified' : 'not_ready');

        return [
            'read_only' => true,
            'mode' => $simulation ? 'simulation' : 'target',
            'expected_environment' => $expectedEnvironment,
            'environment' => $environment,
            'database' => $database,
            'checked_at' => now()->toIso8601String(),
            'technical_readiness' => $technicalStatus,
            'data_readiness' => $dataStatus,
            'governance_readiness' => $governanceStatus,
            'operational_readiness' => $operationalStatus,
            'rollback_readiness' => 'not_verified',
            'checks' => $checks,
            'master_data' => $masterData,
            'overall' => $technicalStatus === 'pass' && $dataStatus === 'pass' && $governanceStatus === 'pass' && $operationalStatus === 'pass' ? 'pass' : 'not_ready',
        ];
    }

    /** @return array<string, mixed> */
    private function refusal(string $expectedEnvironment, string $environment, string $database, string $detail): array
    {
        return [
            'read_only' => true,
            'mode' => 'refused',
            'expected_environment' => $expectedEnvironment,
            'environment' => $environment,
            'database' => $database,
            'checked_at' => now()->toIso8601String(),
            'technical_readiness' => 'not_ready',
            'data_readiness' => 'not_verified',
            'governance_readiness' => 'not_verified',
            'operational_readiness' => 'not_verified',
            'rollback_readiness' => 'not_verified',
            'checks' => [
                $this->check('environment_gate', 'fail', $detail),
            ],
            'master_data' => [],
            'overall' => 'not_ready',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function migrationChecks(): array
    {
        if (! Schema::hasTable('migrations')) {
            return [$this->check('financial_v2_migrations', 'fail', 'Migration repository table is missing.')];
        }

        $expected = collect(File::files(database_path('migrations/financial_v2')))
            ->map(fn (\SplFileInfo $file) => $file->getFilenameWithoutExtension())
            ->sort()
            ->values();
        $ran = $this->connection->table('migrations')->pluck('migration');
        $missing = $expected->reject(fn (string $migration) => $ran->contains($migration))->values();

        return [$this->check(
            'financial_v2_migrations',
            $missing->isEmpty() ? 'pass' : 'fail',
            $missing->isEmpty() ? $expected->count().' Financial V2 migrations are recorded as ran.' : 'Missing Financial V2 migrations: '.$missing->implode(', '),
        )];
    }

    /** @return list<array<string, mixed>> */
    private function schemaChecks(): array
    {
        $missingTables = collect(self::REQUIRED_TABLES)->reject(fn (string $table) => Schema::hasTable($table))->values();
        $checks = [$this->check(
            'financial_v2_tables',
            $missingTables->isEmpty() ? 'pass' : 'fail',
            $missingTables->isEmpty() ? count(self::REQUIRED_TABLES).' required Financial V2 tables are present.' : 'Missing tables: '.$missingTables->implode(', '),
        )];
        if ($missingTables->isNotEmpty()) {
            return $checks;
        }

        $missingIndexes = [];
        foreach (self::REQUIRED_INDEXES as $table => $indexes) {
            $available = collect($this->connection->select("SHOW INDEX FROM {$table}"))->pluck('Key_name');
            foreach ($indexes as $index) {
                if (! $available->contains($index)) {
                    $missingIndexes[] = "{$table}.{$index}";
                }
            }
        }
        $checks[] = $this->check('financial_v2_indexes', $missingIndexes === [] ? 'pass' : 'fail', $missingIndexes === [] ? 'Required unique and operational indexes are present.' : 'Missing indexes: '.implode(', ', $missingIndexes));

        $constraints = collect($this->connection->select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'financial\\_v2\\_%'"))->pluck('CONSTRAINT_NAME');
        $missingConstraints = collect(self::REQUIRED_CONSTRAINTS)->reject(fn (string $constraint) => $constraints->contains($constraint))->values();
        $checks[] = $this->check('financial_v2_constraints', $missingConstraints->isEmpty() ? 'pass' : 'fail', $missingConstraints->isEmpty() ? 'Required Financial V2 CHECK/FK constraint names are present.' : 'Missing constraints: '.$missingConstraints->implode(', '));

        $providerSource = collect(File::allFiles(app_path('Providers')))
            ->map(fn (\SplFileInfo $file) => File::get($file->getPathname()))
            ->implode("\n");
        $autoMutationMarkers = ['Artisan::call(', 'Schema::', 'DB::statement(', 'Migrator'];
        $found = collect($autoMutationMarkers)->filter(fn (string $marker) => str_contains($providerSource, $marker));
        $checks[] = $this->check('no_boot_migration', $found->isEmpty() ? 'pass' : 'fail', $found->isEmpty() ? 'Application providers contain no migration/DDL invocation at boot.' : 'Potential boot-time mutation marker(s): '.$found->implode(', '));

        return $checks;
    }

    /** @return list<array<string, mixed>> */
    private function serviceChecks(): array
    {
        return collect(self::REQUIRED_SERVICES)->map(function (string $service): array {
            try {
                $this->app->make($service);

                return $this->check('service:'.$service, 'pass', class_basename($service).' resolves without a write operation.');
            } catch (\Throwable $exception) {
                return $this->check('service:'.$service, 'fail', class_basename($service).' is unavailable: '.$exception->getMessage());
            }
        })->all();
    }

    /** @param list<array<string, mixed>> $schema @return list<array<string, mixed>> */
    private function masterDataChecks(array $schema): array
    {
        if (collect($schema)->contains(fn (array $check) => $check['id'] === 'financial_v2_tables' && $check['status'] === 'fail')) {
            return [$this->check('master_data', 'not_verified', 'Master-data inspection is unavailable until required Financial V2 tables exist.')];
        }

        $checks = [
            ['id' => 'accounting_entity', 'table' => 'financial_v2_accounting_entities', 'status' => 'active'],
            ['id' => 'calendar', 'table' => 'financial_v2_accounting_calendars', 'status' => 'active'],
            ['id' => 'chart_of_accounts', 'table' => 'financial_v2_accounts', 'status' => 'active'],
            ['id' => 'financial_account', 'table' => 'financial_v2_financial_accounts', 'status' => 'active'],
            ['id' => 'fund', 'table' => 'financial_v2_funds', 'status' => 'active'],
            ['id' => 'program', 'table' => 'financial_v2_programs', 'status' => 'active'],
            ['id' => 'category', 'table' => 'financial_v2_categories', 'status' => 'active'],
            ['id' => 'posting_rule', 'table' => 'financial_v2_posting_rule_versions', 'status' => 'effective'],
            ['id' => 'voucher_sequence', 'table' => 'financial_v2_document_sequences', 'status' => 'active'],
            ['id' => 'evidence_configuration', 'table' => 'financial_v2_evidence_requirements', 'status' => null],
        ];

        $result = collect($checks)->map(function (array $definition): array {
            $query = $this->connection->table($definition['table']);
            if ($definition['status'] !== null) {
                $query->where('status', $definition['status']);
            }
            $count = $query->count();

            return [
                'id' => $definition['id'],
                'status' => $count > 0 ? 'ready' : 'missing',
                'detail' => $count > 0 ? "{$count} eligible record(s) found; governance approval still requires evidence." : 'No eligible record found; do not infer or create production data.',
            ];
        });

        $restrictedFunds = $this->connection->table('financial_v2_funds as fund')
            ->join('financial_v2_fund_types as type', 'type.id', '=', 'fund.fund_type_id')
            ->where('fund.status', 'active')
            ->where('type.classification', 'restricted')
            ->count();
        $coveredRestrictedFunds = $this->connection->table('financial_v2_funds as fund')
            ->join('financial_v2_fund_types as type', 'type.id', '=', 'fund.fund_type_id')
            ->where('fund.status', 'active')
            ->where('type.classification', 'restricted')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('financial_v2_fund_policy_versions as policy')
                    ->whereColumn('policy.fund_id', 'fund.id')
                    ->where('policy.status', 'effective');
            })
            ->count();
        $result->push([
            'id' => 'fund_policy',
            'status' => $restrictedFunds === $coveredRestrictedFunds ? 'ready' : 'conflict',
            'detail' => "{$coveredRestrictedFunds} of {$restrictedFunds} active restricted Fund(s) have an effective policy; policy-rule approval must be evidenced separately.",
        ]);

        return $result->all();
    }

    /** @return list<array<string, mixed>> */
    private function testIsolationChecks(): array
    {
        $bootstrap = File::exists(base_path('tests/bootstrap.php')) ? File::get(base_path('tests/bootstrap.php')) : '';
        $safety = File::exists(base_path('tests/Support/TestDatabaseSafety.php')) ? File::get(base_path('tests/Support/TestDatabaseSafety.php')) : '';
        $requiredMarkers = ['APP_ENV', 'mrj_test_db', 'mrj_prod_db'];
        $allMarkersPresent = collect($requiredMarkers)->every(fn (string $marker) => str_contains($bootstrap.$safety, $marker));

        return [$this->check('test_isolation', $allMarkersPresent ? 'pass' : 'fail', $allMarkersPresent ? 'Test bootstrap and runtime safety guard require testing/mrj_test_db and reject the production database.' : 'Test-isolation guard markers are incomplete.')];
    }

    /** @return list<array<string, mixed>> */
    private function legacyIsolationChecks(): array
    {
        $directories = [app_path('Domain/FinancialV2'), app_path('Http/Controllers/FinancialV2'), app_path('Models/FinancialV2')];
        $markers = ["DB::table('jurnal'", "DB::table('transaksis'", "DB::table('penerimaan_pemasukans'", "DB::table('pengeluaran_umums'", "DB::table('saldo_awals'", 'App\\Models\\Jurnal', 'App\\Models\\Transaksi'];
        $matches = [];
        foreach ($directories as $directory) {
            foreach (File::allFiles($directory) as $file) {
                if ($file->getFilename() === 'FinancialV2PreflightChecker.php') {
                    continue;
                }
                $contents = File::get($file->getPathname());
                foreach ($markers as $marker) {
                    if (str_contains($contents, $marker)) {
                        $matches[] = $file->getRelativePathname().": {$marker}";
                    }
                }
            }
        }

        return [$this->check('legacy_isolation', $matches === [] ? 'pass' : 'fail', $matches === [] ? 'No Financial V2 runtime source access to legacy financial tables/models was found.' : 'Legacy access marker(s): '.implode('; ', $matches))];
    }

    /** @return array{id: string, status: string, detail: string} */
    private function check(string $id, string $status, string $detail): array
    {
        return compact('id', 'status', 'detail');
    }
}
