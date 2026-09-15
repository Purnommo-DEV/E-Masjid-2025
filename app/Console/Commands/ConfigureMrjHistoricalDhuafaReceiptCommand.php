<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\ConfigureMrjHistoricalDhuafaReceiptService;
use Illuminate\Console\Command;
use Throwable;

final class ConfigureMrjHistoricalDhuafaReceiptCommand extends Command
{
    protected $signature = 'financial-v2:configure-mrj-historical-dhuafa-receipt {--actor= : User id recorded as approver/author} {--apply : Persist configuration; otherwise read-only}';

    protected $description = 'Configure the evidence-backed July 2026 DHUAFA receipt without creating financial facts.';

    public function __construct(private readonly ConfigureMrjHistoricalDhuafaReceiptService $configuration)
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

        try {
            $result = $this->configuration->configure(
                $this->option('actor') !== null ? (int) $this->option('actor') : null,
                ConfigureMrjHistoricalDhuafaReceiptService::ORIGIN_ARTISAN,
            );
        } catch (Throwable $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Konfigurasi historis selesai. Financial fact tidak berubah.');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
