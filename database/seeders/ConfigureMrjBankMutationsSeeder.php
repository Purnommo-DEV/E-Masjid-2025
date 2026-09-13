<?php

namespace Database\Seeders;

use App\Domain\FinancialV2\ConfigureFinancialV2DefaultsService;
use Illuminate\Database\Seeder;

final class ConfigureMrjBankMutationsSeeder extends Seeder
{
    public function run(): void
    {
        app(ConfigureFinancialV2DefaultsService::class)->configureMrjBankMutations();
    }
}
