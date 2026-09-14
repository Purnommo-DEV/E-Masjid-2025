<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\ConfigureMrjHistoricalDhuafaReceiptService;
use Illuminate\Database\Seeder;

/**
 * Terminal-capable hosting command:
 * php artisan db:seed --class=Database\\Seeders\\ConfigureMrjHistoricalDhuafaReceiptSeeder --force
 */
final class ConfigureMrjHistoricalDhuafaReceiptSeeder extends Seeder
{
    public function run(): void
    {
        app(ConfigureMrjHistoricalDhuafaReceiptService::class)->configure();
    }
}
