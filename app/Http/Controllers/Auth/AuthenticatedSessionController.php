<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $username = $request->input('username');

        try {
            $request->authenticate();

            $user = Auth::user();

            if ($user && !$user->is_active) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $this->logLogin($request, $user, 'failed', 'Tài khoản đã bị khóa');

                return back()->withErrors([
                    'username' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
                ])->onlyInput('username');
            }

            $deviceCheck = $this->checkLoginDevice($request, $user);

            if (!$deviceCheck['allowed']) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $this->logLogin($request, $user, 'failed', $deviceCheck['message']);

                return back()
                    ->withErrors(['username' => $deviceCheck['message']])
                    ->onlyInput('username')
                    ->withCookie(cookie('cncl_device_uid', $deviceCheck['device_uid'], 60 * 24 * 365 * 5));
            }

            $request->session()->regenerate();

            $this->logLogin($request, $user, 'success', 'Đăng nhập thành công');

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Throwable $e) {
            $user = User::where('username', $username)->first();

            $this->logLogin($request, $user, 'failed', 'Đăng nhập thất bại', $username);

            throw $e;
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $this->logLogin($request, $user, 'logout', 'Đăng xuất khỏi hệ thống');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function checkLoginDevice(Request $request, User $user): array
    {
        if (!$this->shouldRequireApprovedDevice($user)) {
            return ['allowed' => true];
        }

        $deviceUid = $request->cookie('cncl_device_uid') ?: (string) Str::uuid();

        $maxApprovedDevices = max(1, (int) SystemSetting::getValue('login_device_max_per_user', 3));

        $device = UserDevice::firstOrCreate(
            [
                'user_id' => $user->id,
                'device_uid' => $deviceUid,
            ],
            [
                'device_name' => $this->defaultDeviceName($request),
                'status' => UserDevice::STATUS_PENDING,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'requested_at' => now(),
            ]
        );

        if (
            $device->status === UserDevice::STATUS_PENDING
            && $user->devices()
                ->where('status', UserDevice::STATUS_APPROVED)
                ->where('id', '!=', $device->id)
                ->count() >= $maxApprovedDevices
        ) {
            return [
                'allowed' => false,
                'device_uid' => $deviceUid,
                'message' => 'Tài khoản đã đạt số thiết bị được duyệt tối đa. Vui lòng liên hệ quản trị viên để thu hồi thiết bị cũ hoặc cấp quyền thiết bị mới.',
            ];
        }

        $device->forceFill([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'requested_at' => $device->requested_at ?: now(),
        ])->save();

        if ($device->status === UserDevice::STATUS_APPROVED) {
            $device->update(['last_used_at' => now()]);

            return [
                'allowed' => true,
                'device_uid' => $deviceUid,
            ];
        }

        if ($device->status === UserDevice::STATUS_BLOCKED) {
            return [
                'allowed' => false,
                'device_uid' => $deviceUid,
                'message' => 'Thiết bị đăng nhập này đã bị khóa. Vui lòng liên hệ quản trị viên.',
            ];
        }

        return [
            'allowed' => false,
            'device_uid' => $deviceUid,
            'message' => 'Thiết bị đăng nhập này chưa được duyệt. Vui lòng liên hệ quản trị viên để được cấp quyền sử dụng trên máy này.',
        ];
    }

    private function shouldRequireApprovedDevice(User $user): bool
    {
        if (!SystemSetting::getValue('login_device_control_enabled', true)) {
            return false;
        }

        $roles = collect(preg_split('/[\s,;]+/', (string) SystemSetting::getValue('login_device_control_roles', 'TrungTam'), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($role) => trim($role))
            ->filter()
            ->values();

        if ($roles->isEmpty()) {
            return false;
        }

        return $roles->contains(fn ($role) => $user->hasRole($role));
    }

    private function defaultDeviceName(Request $request): string
    {
        $agent = (string) $request->userAgent();

        if ($agent === '') {
            return 'Thiết bị chưa xác định';
        }

        return Str::limit($agent, 120, '');
    }

    private function logLogin(
        Request $request,
        ?User $user,
        string $status,
        string $message,
        ?string $fallbackUsername = null
    ): void {
        LoginLog::create([
            'user_id' => $user?->id,
            'username' => $user?->username ?? $fallbackUsername,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status,
            'message' => $message,
            'logged_at' => now(),
        ]);
    }
}
