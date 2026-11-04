<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PermissionCatalog;
use App\Services\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserPermissionController extends Controller
{
    public function edit(Request $request, User $user, UserPermissionService $service)
    {
        $service->authorize($request->user());
        $catalog = $service->catalog();
        $state = $service->state($user, $catalog);

        return view('users.permissions', [
            'target' => $user, 'state' => $state, 'permissionGroups' => app(PermissionCatalog::class)->groups($catalog),
            'protectedAccount' => $user->hasRole('Admin'), 'ready' => Schema::hasColumn('users', 'permission_denials'),
        ]);
    }

    public function update(Request $request, User $user, UserPermissionService $service)
    {
        $service->authorize($request->user());
        $data = $request->validate(['version' => ['required', 'string', 'size:64'], 'overrides' => ['required', 'array'],
            'overrides.*' => ['required', Rule::in(['inherit', 'allow', 'deny'])]]);
        $service->update($request->user(), $user, $data['overrides'], $data['version']);

        return redirect()->route('users.permissions.edit', $user)->with('success', 'Đã cập nhật quyền riêng. Có hiệu lực từ yêu cầu tiếp theo; quyền nhóm không thay đổi.');
    }
}
