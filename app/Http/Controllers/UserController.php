<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\DistributionCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with([
            'roles',
            'distributionCenter',
        ]);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('users.name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('users.username', 'like', '%' . $request->keyword . '%')
                    ->orWhere('users.email', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('distribution_center_id')) {
            $query->where('users.distribution_center_id', $request->distribution_center_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('users.is_active', $request->status);
        }

        [$sort, $direction] = $this->sortInput($request, [
            'name',
            'username',
            'email',
            'is_active',
            'created_at',
            'center',
        ]);

        if ($sort === 'center') {
            $query->leftJoin('distribution_centers as sort_centers', 'sort_centers.id', '=', 'users.distribution_center_id')
                ->select('users.*')
                ->orderBy('sort_centers.name', $direction)
                ->orderBy('users.name');
        } else {
            $query->orderBy('users.' . $sort, $direction)
                ->orderBy('users.id', 'desc');
        }

        $users = $query->paginate(15)->withQueryString();

        $roles = Role::orderBy('name')->get();
        $centers = DistributionCenter::orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'centers'));
    }

    public function options(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $users = User::with('distributionCenter')
            ->when(auth()->user()->hasRole('TrungTam'), function ($query) {
                $query->where('distribution_center_id', auth()->user()->distribution_center_id);
            })
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('username', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $users
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'text' => $this->userOptionText($user),
                ])
                ->values(),
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $centers = DistributionCenter::orderBy('name')->get();

        return view('users.create', compact('roles', 'centers'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedUserData($request, null, true);

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'smartca_user_id' => $data['smartca_user_id'] ?? null,
            'distribution_center_id' => $data['distribution_center_id'] ?? null,
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles([$data['role']]);

        ActivityLogger::log(
            'Người dùng',
            'create',
            'Tạo người dùng: ' . $user->username,
            null,
            $this->userAuditData($user),
            $user
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Thêm người dùng thành công.');
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $centers = DistributionCenter::orderBy('name')->get();

        return view('users.edit', compact('user', 'roles', 'centers'));
    }

    public function update(Request $request, User $user)
    {
        $oldData = $this->userAuditData($user);
        $data = $this->validatedUserData($request, $user);

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'smartca_user_id' => $data['smartca_user_id'] ?? null,
            'distribution_center_id' => $data['distribution_center_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles([$data['role']]);

        ActivityLogger::log(
            'Người dùng',
            'update',
            'Cập nhật người dùng: ' . $user->username,
            $oldData,
            $this->userAuditData($user->fresh(['roles', 'distributionCenter'])),
            $user
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Cập nhật người dùng thành công.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }

        $oldData = $this->userAuditData($user);

        $user->delete();

        ActivityLogger::log(
            'Người dùng',
            'delete',
            'Xóa người dùng: ' . $oldData['username'],
            $oldData
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Xóa người dùng thành công.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        ActivityLogger::log(
            'Người dùng',
            'reset_password',
            'Reset mật khẩu người dùng: ' . $user->username,
            null,
            ['username' => $user->username],
            $user
        );

        return redirect()
            ->route('users.edit', $user)
            ->with('success', 'Reset mật khẩu thành công.');
    }

    public function toggleActive(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Không thể khóa chính tài khoản đang đăng nhập.');
        }

        $oldData = $this->userAuditData($user);

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        ActivityLogger::log(
            'Người dùng',
            $user->is_active ? 'activate' : 'deactivate',
            ($user->is_active ? 'Mở khóa tài khoản: ' : 'Khóa tài khoản: ') . $user->username,
            $oldData,
            $this->userAuditData($user->fresh(['roles', 'distributionCenter'])),
            $user
        );

        return redirect()
            ->route('users.index')
            ->with('success', 'Cập nhật trạng thái tài khoản thành công.');
    }

    private function validatedUserData(Request $request, ?User $user = null, bool $creating = false): array
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'username' => 'required|max:100|unique:users,username' . ($user ? ',' . $user->id : ''),
            'email' => 'nullable|email',
            'smartca_user_id' => 'nullable|string|max:100',
            'distribution_center_id' => 'nullable|exists:distribution_centers,id',
            'role' => 'required|exists:roles,name',
            'password' => $creating ? ['required', Password::min(8), 'confirmed'] : ['nullable'],
        ]);

        if ($data['role'] === 'TrungTam' && empty($data['distribution_center_id'])) {
            validator([], [])->after(function ($validator) {
                $validator->errors()->add('distribution_center_id', 'Tài khoản Trung tâm phải được gán trung tâm phân phối.');
            })->validate();
        }

        if ($data['role'] !== 'TrungTam') {
            $data['distribution_center_id'] = null;
        }

        return $data;
    }

    private function userOptionText(User $user): string
    {
        return collect([
            $user->name,
            $user->username,
            $user->distributionCenter?->code,
        ])
            ->filter()
            ->implode(' - ');
    }

    private function userAuditData(User $user): array
    {
        $user->loadMissing(['roles', 'distributionCenter']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'smartca_user_id' => $user->smartca_user_id,
            'role' => $user->roles->pluck('name')->implode(', '),
            'distribution_center' => $user->distributionCenter?->code,
            'is_active' => $user->is_active,
        ];
    }
}
