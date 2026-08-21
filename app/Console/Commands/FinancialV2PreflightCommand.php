<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\ProductionReadiness\FinancialV2PreflightChecker;
use Illuminate\Console\Command;

final class FinancialV2PreflightCommand extends Command
{
    protected $signature = 'financial-v2:preflight
        {--expect-env=production : Environment name that represents the intended production target}
        {--simulate : Run only against the safeguarded testing/mrj_test_db simulation target}
        {--format=table : Output format: table or json}
        {--strict : Return a non-zero exit code unless every readiness dimension is pass}';

    protected $description = 'Run a read-only Financial V2 cutover preflight; it never runs migrations or writes financial data.';

    public function __construct(private readonly FinancialV2PreflightChecker $checker)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->checker->inspect((string) $this->option('expect-env'), (bool) $this->option('simulate'));
        if ($this->option('format') === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->line('Financial V2 preflight is READ-ONLY. No migration, posting, opening balance, legacy freeze, or cutover is executed.');
            $this->line("Mode: {$result['mode']} | environment: {$result['environment']} | database: {$result['database']}");
            $this->table(['Check', 'Status', 'Detail'], array_map(fn (array $check) => [$check['id'], strtoupper($check['status']), $check['detail']], $result['checks']));
            $this->table(['Master data', 'Status', 'Detail'], array_map(fn (array $check) => [$check['id'], strtoupper($check['status']), $check['detail']], $result['master_data']));
            $this->newLine();
            $this->line('Technical='.$result['technical_readiness'].' | Data='.$result['data_readiness'].' | Governance='.$result['governance_readiness'].' | Operational='.$result['operational_readiness'].' | Rollback='.$result['rollback_readiness']);
            $this->line('Overall='.$result['overall']);
        }

        if ($this->option('strict') && $result['overall'] !== 'pass') {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
