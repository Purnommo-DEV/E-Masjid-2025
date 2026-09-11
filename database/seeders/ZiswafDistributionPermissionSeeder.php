<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Register capabilities and preserve the existing SuperAdmin all-permissions convention. */
class ZiswafDistributionPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'view penerima ziswaf', 'create penerima ziswaf', 'edit penerima ziswaf', 'delete penerima ziswaf',
        'view penyaluran ziswaf', 'create penyaluran ziswaf', 'edit penyaluran ziswaf', 'finalize penyaluran ziswaf',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }
        if ($superAdmin = Role::query()->where('name', 'SuperAdmin')->where('guard_name', 'web')->first()) {
            $superAdmin->givePermissionTo(self::PERMISSIONS);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
