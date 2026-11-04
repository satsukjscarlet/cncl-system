<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UserPermissionService
{
    public function authorize(User $actor): void
    {
        abort_unless($actor->hasRole('Admin') && $actor->can('role_permission.manage'), 403);
    }

    public function catalog(): Collection
    {
        return Permission::where('guard_name', 'web')->orderBy('name')->get();
    }

    public function state(User $user, Collection $catalog): array
    {
        $user->loadMissing(['permissions', 'roles.permissions']);
        $inherited = $user->getPermissionsViaRoles()->where('guard_name', 'web')->pluck('name')->unique()->all();
        $direct = $user->permissions->where('guard_name', 'web')->pluck('name')->all();
        $denied = (array) $user->permission_denials;
        $rows = [];
        foreach ($catalog as $permission) {
            $name = $permission->name;
            $rows[$name] = ['mode' => in_array($name, $denied, true) ? 'deny' : (in_array($name, $direct, true) ? 'allow' : 'inherit'),
                'inherited' => in_array($name, $inherited, true), 'effective' => $user->hasPermissionTo($permission), 'restricted' => $user->isPermissionRestricted($permission)];
        }
        $roleState = $user->roles->sortBy('id')->map(fn ($role) => [$role->id, $role->permissions->map(fn ($permission) => $permission->guard_name.':'.$permission->name)->sort()->values()->all()])->values()->all();
        sort($direct);
        sort($denied);
        $version = hash('sha256', json_encode([$user->id, $catalog->pluck('name')->all(), $roleState, $direct, $denied]));

        return compact('rows', 'version');
    }

    public function update(User $actor, User $target, array $modes, string $version): void
    {
        $this->authorize($actor);
        if (! Schema::hasColumn('users', 'permission_denials')) {
            throw ValidationException::withMessages(['overrides' => 'Cần chạy migration phân quyền tài khoản trước khi lưu.']);
        }
        DB::transaction(function () use ($target, $modes, $version) {
            $target = User::lockForUpdate()->findOrFail($target->id);
            abort_if($target->hasRole('Admin'), 403, 'Không thay đổi quyền riêng của tài khoản Admin để tránh khóa hệ thống.');
            $catalog = $this->catalog();
            $old = $this->state($target, $catalog);
            if (! hash_equals($old['version'], $version)) {
                throw ValidationException::withMessages(['version' => 'Quyền nhóm hoặc tài khoản đã thay đổi. Vui lòng tải lại trang trước khi lưu.']);
            }
            $keys = array_keys($modes);
            sort($keys);
            if ($keys !== $catalog->pluck('name')->sort()->values()->all() || collect($modes)->contains(fn ($mode) => ! in_array($mode, ['inherit', 'allow', 'deny'], true))) {
                throw ValidationException::withMessages(['overrides' => 'Danh sách quyền không đầy đủ hoặc không hợp lệ. Vui lòng tải lại trang.']);
            }
            foreach ($catalog as $permission) {
                if ($modes[$permission->name] === 'allow' && $target->isPermissionRestricted($permission)) {
                    throw ValidationException::withMessages(['overrides' => 'Không cấp chức năng nội bộ/quản trị cho tài khoản Trung tâm. Các giới hạn dữ liệu vẫn được giữ nguyên.']);
                }
            }
            $allowIds = $catalog->filter(fn ($permission) => $modes[$permission->name] === 'allow')->pluck('id')->all();
            $denials = array_keys(array_filter($modes, fn ($mode) => $mode === 'deny'));
            sort($denials);
            // Touch only this account's web permissions, preserving roles and any other guard.
            $target->permissions()->detach($catalog->pluck('id')->all());
            $target->permissions()->syncWithoutDetaching($allowIds);
            $target->forceFill(['permission_denials' => $denials ?: null])->save();
            $target->unsetRelation('permissions');
            $new = $this->state($target, $catalog);
            ActivityLogger::log('Phân quyền tài khoản', 'update', 'Cập nhật quyền riêng tài khoản: '.$target->username,
                ['permissions' => $old['rows']], ['permissions' => $new['rows']], $target);
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
