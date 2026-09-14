<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\ConfigureMrjFidyahAllocationService;
use Illuminate\Console\Command;

final class ConfigureMrjFidyahAllocationCommand extends Command
{
    protected $signature = 'financial-v2:configure-mrj-fidyah-allocation
                            {--apply : Persist configuration; without this flag the command is read-only}
                            {--actor= : User id recorded as provisioning actor}
                            {--allow-testing : Retained for isolated test invocation compatibility}';

    protected $description = 'Configure PAY-FIDYAH allocation policy for Dana Fidyah and Dana Infaq & Tromol without creating financial facts.';

    public function __construct(private readonly ConfigureMrjFidyahAllocationService $configuration)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->option('apply')) {
            $this->line(json_encode($this->configuration->status(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->warn('Dry-run selesai. Tambahkan --apply untuk menyimpan configuration saja.');

            return self::SUCCESS;
        }

        $result = $this->configuration->configure(
            $this->option('actor') !== null ? (int) $this->option('actor') : null,
            'ARTISAN_CONFIGURATION_PROVISION',
        );
        $this->info($result['changed'] ? 'Konfigurasi alokasi Fidyah berhasil diprovision.' : 'Konfigurasi alokasi Fidyah sudah aktif; seluruh komponen digunakan kembali.');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
