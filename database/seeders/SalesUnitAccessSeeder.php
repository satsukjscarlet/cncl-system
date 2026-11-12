<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SalesUnitAccessSeeder extends Seeder
{
    public function run(): void
    {
        // Additive only: never synchronize existing production roles or create accounts.
        $permissions = ['dashboard.view', 'request.view', 'request.create', 'request.update', 'request.delete', 'certificate.view'];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'DonViBanHang', 'guard_name' => 'web'])->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
