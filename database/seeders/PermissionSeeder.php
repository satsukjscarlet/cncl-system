<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $legacyPermissions = [
            'distribution_center.manage',
            'product_group.manage',
            'product.manage',
            'quality_standard.manage',
            'customer.manage',
            'sla.manage',
            'setting.manage',
            'user.manage',
        ];

        Permission::whereIn('name', $legacyPermissions)->delete();

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

            'request.view',
            'request.create',
            'request.update',
            'request.delete',

            'dvkh.process',

            'ptn.process',
            'certificate.view',
            'certificate.create',
            'certificate.sign',
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
            'role_permission.manage',

            'log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $leader = Role::firstOrCreate(['name' => 'LanhDao']);
        $center = Role::firstOrCreate(['name' => 'TrungTam']);
        $dvkh = Role::firstOrCreate(['name' => 'DVKH']);
        $ptn = Role::firstOrCreate(['name' => 'PTN']);
        $viewer = Role::firstOrCreate(['name' => 'Viewer']);

        $admin->syncPermissions($permissions);

        $leader->syncPermissions([
            'dashboard.view',
            'request.view',
            'certificate.view',
            'report.view',
            'report.export',
            'log.view',
        ]);

        $center->syncPermissions([
            'dashboard.view',
            'request.view',
            'request.create',
            'request.update',
            'request.delete',
            'certificate.view',
        ]);

        $dvkh->syncPermissions([
            'dashboard.view',
            'request.view',
            'dvkh.process',
            'certificate.view',
            'report.view',
        ]);

        $ptn->syncPermissions([
            'dashboard.view',
            'request.view',
            'ptn.process',
            'certificate.view',
            'certificate.create',
            'certificate.sign',
            'certificate.print',
            'certificate.email',
            'report.view',
        ]);

        $viewer->syncPermissions([
            'dashboard.view',
            'request.view',
            'certificate.view',
            'report.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
