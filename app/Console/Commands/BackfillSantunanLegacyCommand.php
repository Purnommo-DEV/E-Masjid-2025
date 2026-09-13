<?php

namespace App\Console\Commands;

use App\Services\SantunanLegacyBackfillService;
use Illuminate\Console\Command;

class BackfillSantunanLegacyCommand extends Command
{
    protected $signature = 'santunan:backfill-legacy {--apply : Persist master persons and yearly participations}';

    protected $description = 'Audit and non-destructively backfill legacy Santunan Ramadhan registrations.';

    public function handle(SantunanLegacyBackfillService $backfill): int
    {
        $report = $this->option('apply') ? $backfill->run() : $backfill->preflight();

        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (! $this->option('apply')) {
            $this->info('Preflight selesai. Jalankan kembali dengan --apply setelah hasil diverifikasi.');
        }

        return self::SUCCESS;
    }
}
