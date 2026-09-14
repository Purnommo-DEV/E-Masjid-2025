<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\ConfigureMrjFidyahAllocationService;
use Illuminate\Database\Seeder;

/**
 * Terminal-capable hosting command:
 * php artisan db:seed --class=Database\\Seeders\\ConfigureMrjFidyahAllocationSeeder --force
 */
final class ConfigureMrjFidyahAllocationSeeder extends Seeder
{
    public function run(): void
    {
        $result = app(ConfigureMrjFidyahAllocationService::class)->configure(null, 'DATABASE_SEEDER_PROVISION');
        $this->command?->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
