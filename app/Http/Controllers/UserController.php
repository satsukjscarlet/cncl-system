<?php

namespace App\Http\Controllers;

use App\Models\DistributionCenter;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                $q->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('username', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('distribution_center_id')) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        if ($request->status !== null && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $users = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

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
        $data = $request->validate([
            'name' => 'required|max:255',
            'username' => 'required|max:100|unique:users,username',
            'email' => 'nullable|email',
            'smartca_user_id' => 'nullable|string|max:100',
            'distribution_center_id' => 'nullable|exists:distribution_centers,id',
            'role' => 'required|exists:roles,name',
            'password' => 'required|min:6|confirmed',
        ]);

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
        $data = $request->validate([
            'name' => 'required|max:255',
            'username' => 'required|max:100|unique:users,username,' . $user->id,
            'email' => 'nullable|email',
            'smartca_user_id' => 'nullable|string|max:100',
            'distribution_center_id' => 'nullable|exists:distribution_centers,id',
            'role' => 'required|exists:roles,name',
        ]);

        $user->update([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'] ?? null,
            'smartca_user_id' => $data['smartca_user_id'] ?? null,
            'distribution_center_id' => $data['distribution_center_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->syncRoles([$data['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Cập nhật người dùng thành công.');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Xóa người dùng thành công.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => 'required|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('users.edit', $user)
            ->with('success', 'Reset mật khẩu thành công.');
    }

    public function toggleActive(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Không thể khóa chính tài khoản đang đăng nhập.');
        }

        $user->update([
            'is_active' => !$user->is_active,
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Cập nhật trạng thái tài khoản thành công.');
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
}
