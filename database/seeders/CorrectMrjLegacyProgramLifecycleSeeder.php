<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\CorrectMrjLegacyProgramLifecycleService;
use Illuminate\Database\Seeder;

/**
 * Hosting-safe, idempotent master-data correction:
 * php artisan db:seed --class=Database\\Seeders\\CorrectMrjLegacyProgramLifecycleSeeder --force
 */
final class CorrectMrjLegacyProgramLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        app(CorrectMrjLegacyProgramLifecycleService::class)->correct();
    }
}
