<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Terminal-capable hosting command:
 * php artisan db:seed --class=Database\\Seeders\\ConfigureMrjFidyahAllocationSeeder --force
 */
final class ConfigureMrjFidyahAllocationSeeder extends Seeder
{
    public function run(): void
    {
        $arguments = ['--apply' => true];
        if (app()->environment('testing')) {
            $arguments['--allow-testing'] = true;
        }

        $exitCode = Artisan::call('financial-v2:configure-mrj-fidyah-allocation', $arguments);
        if ($exitCode !== 0) {
            throw new RuntimeException('Konfigurasi alokasi Fidyah gagal: '.trim(Artisan::output()));
        }

        $this->command?->line(Artisan::output());
    }
}
