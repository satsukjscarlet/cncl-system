<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    // Role permissions aligned with the live configuration on 2026-10-03.
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',

            'distribution_center.view',
            'distribution_center.create',
            'distribution_center.update',
            'distribution_center.delete',

            'product_group.view',
            'product_group.create',
            'product_group.update',
            'product_group.delete',
            'product_group.import',
            'product_group.export',

            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'product.import',
            'product.export',

            'quality_standard.view',
            'quality_standard.create',
            'quality_standard.update',
            'quality_standard.delete',
            'quality_standard.import',
            'quality_standard.export',

            'urgent_reason.view',
            'urgent_reason.create',
            'urgent_reason.update',
            'urgent_reason.delete',

            'customer.view',
            'customer.create',
            'customer.update',
            'customer.delete',
            'customer.import',
            'customer.export',

            'sales_unit.view',
            'sales_unit.create',
            'sales_unit.update',
            'sales_unit.delete',
            'sales_unit.import',
            'sales_unit.export',

            'request.view',
            'request.create',
            'request.update',
            'request.delete',

            'dvkh.process',

            'ptn.process',
            'certificate.view',
            'certificate.create',
            'certificate.sign',
            'certificate.reject',
            'certificate.print',
            'certificate.email',

            'report.view',
            'report.export',

            'sla.view',
            'sla.create',
            'sla.update',
            'sla.delete',
            'sla.import',
            'sla.export',

            'setting.view',
            'setting.update',

            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.reset_password',
            'user.toggle_active',
            'device.manage',
            'role_permission.manage',

            'log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $leader = Role::firstOrCreate(['name' => 'LanhDao', 'guard_name' => 'web']);
        $center = Role::firstOrCreate(['name' => 'TrungTam', 'guard_name' => 'web']);
        $dvkh = Role::firstOrCreate(['name' => 'DVKH', 'guard_name' => 'web']);
        $ptn = Role::firstOrCreate(['name' => 'PTN', 'guard_name' => 'web']);
        $truongPtn = Role::firstOrCreate(['name' => 'TruongPTN', 'guard_name' => 'web']);
        $viewer = Role::firstOrCreate(['name' => 'Viewer', 'guard_name' => 'web']);

        // Do not use syncPermissions() here.
        // Production roles may be adjusted manually, so this seeder only adds missing permissions
        // and never removes permissions that already exist on a role.
        $admin->givePermissionTo(Permission::pluck('name')->all());

        $this->giveMissingPermissions($leader, [
            'dashboard.view',
            'request.view',
            'certificate.view',
            'report.view',
            'report.export',
            'log.view',
        ]);

        $this->giveMissingPermissions($center, [
            'dashboard.view',
            'customer.view',
            'customer.create',
            'customer.update',
            'customer.delete',
            'customer.import',
            'sales_unit.view',
            'request.view',
            'request.create',
            'request.update',
            'request.delete',
            'certificate.view',
        ]);

        $this->giveMissingPermissions($dvkh, [
            'dashboard.view',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'sales_unit.view',
            'sales_unit.create',
            'sales_unit.update',
            'sales_unit.delete',
            'sales_unit.import',
            'sales_unit.export',
            'request.view',
            'dvkh.process',
            'certificate.view',
            'product.export',
            'product.import',
            'product_group.create',
            'product_group.export',
            'product_group.import',
            'product_group.update',
            'product_group.view',
        ]);

        $this->giveMissingPermissions($ptn, [
            'dashboard.view',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'quality_standard.view',
            'quality_standard.create',
            'quality_standard.update',
            'quality_standard.delete',
            'sales_unit.view',
            'request.view',
            'ptn.process',
            'certificate.view',
            'certificate.create',
            'certificate.print',
            'report.export',
            'report.view',
        ]);

        $this->giveMissingPermissions($truongPtn, [
            'dashboard.view',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'quality_standard.view',
            'quality_standard.create',
            'quality_standard.update',
            'quality_standard.delete',
            'sales_unit.view',
            'request.view',
            'certificate.view',
            'certificate.sign',
            'certificate.reject',
            'certificate.print',
            'certificate.email',
            'report.export',
            'report.view',
        ]);

        $this->giveMissingPermissions($viewer, [
            'dashboard.view',
            'request.view',
            'certificate.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function giveMissingPermissions(Role $role, array $permissions): void
    {
        $currentPermissions = $role->permissions()->pluck('name')->all();
        $missingPermissions = array_values(array_diff($permissions, $currentPermissions));

        if ($missingPermissions !== []) {
            $role->givePermissionTo($missingPermissions);
        }
    }
}
