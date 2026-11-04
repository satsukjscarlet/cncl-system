<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();
        $permissionGroups = app(\App\Services\PermissionCatalog::class)->groups($permissions);
        $roleLabels = app(\App\Services\PermissionCatalog::class)->roleLabels();

        return view('role_permissions.index', compact('roles', 'permissionGroups', 'roleLabels'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $permissions = collect($data['permissions'] ?? []);

        if ($role->name === 'Admin') {
            $permissions = $permissions->merge(Permission::pluck('name'));
        }

        $oldPermissions = $role->permissions()->pluck('name')->sort()->values()->all();

        $role->syncPermissions($permissions->unique()->values()->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::log(
            'Phân quyền',
            'update',
            'Cập nhật phân quyền vai trò: ' . $role->name,
            ['permissions' => $oldPermissions],
            ['permissions' => $role->fresh()->permissions()->pluck('name')->sort()->values()->all()]
        );

        return redirect()
            ->route('role-permissions.index')
            ->with('success', 'Cập nhật phân quyền thành công.');
    }

}
