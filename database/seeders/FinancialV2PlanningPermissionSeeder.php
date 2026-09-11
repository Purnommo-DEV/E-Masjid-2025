<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class FinancialV2PlanningPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'financial-v2.planning.view',
        'financial-v2.planning.create',
        'financial-v2.planning.update',
        'financial-v2.planning.approve',
        'financial-v2.planning.convert',
        'financial-v2.planning.cancel',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::query()->where('name', 'SuperAdmin')->where('guard_name', 'web')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo(self::PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
