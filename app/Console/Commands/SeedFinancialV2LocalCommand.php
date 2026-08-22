<?php

namespace App\Console\Commands;

use Database\Seeders\FinancialV2Seeder;
use Illuminate\Console\Command;

/** Runs the coordinated Financial V2 seeder with an explicit local scenario. */
final class SeedFinancialV2LocalCommand extends Command
{
    protected $signature = 'financial-v2:seed-local';

    protected $description = 'Replay the guarded, idempotent current Financial V2 baseline through canonical writers.';

    public function handle(FinancialV2Seeder $seeder): int
    {
        $seeder->setContainer(app())->setCommand($this)->run();

        return self::SUCCESS;
    }
}
