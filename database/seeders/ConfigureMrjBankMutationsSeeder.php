<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\ConfigureMrjBankMutationsService;
use Illuminate\Database\Seeder;

final class ConfigureMrjBankMutationsSeeder extends Seeder
{
    public function run(): void
    {
        app(ConfigureMrjBankMutationsService::class)->configure();
    }
}
