<?php

namespace App\Http\Middleware;

use App\Models\LoginLog;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginDeviceIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || $this->shouldSkipRoute($request)) {
            return $next($request);
        }

        if (!$user->is_active) {
            $message = 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';

            $this->logForcedLogout($request, $user, $message);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['username' => $message]);
        }

        if (!$this->shouldCheck($user)) {
            return $next($request);
        }

        $deviceUid = $request->cookie('cncl_device_uid');
        $device = $deviceUid
            ? UserDevice::where('user_id', $user->id)->where('device_uid', $deviceUid)->first()
            : null;

        if (!$device || $device->status !== UserDevice::STATUS_APPROVED) {
            $message = $device && $device->status === UserDevice::STATUS_BLOCKED
                ? 'Thiết bị đăng nhập này đã bị khóa. Vui lòng liên hệ quản trị viên.'
                : 'Thiết bị đăng nhập này chưa được duyệt. Vui lòng liên hệ quản trị viên để được cấp quyền sử dụng trên máy này.';

            $this->logForcedLogout($request, $user, $message);

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['username' => $message]);
        }

        if (!$device->last_used_at || $device->last_used_at->lt(now()->subMinutes(5))) {
            $device->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
            ]);
        }

        return $next($request);
    }

    private function shouldCheck(User $user): bool
    {
        if (!SystemSetting::getValue('login_device_control_enabled', true)) {
            return false;
        }

        // Admin/operator accounts that manage devices must not be locked out by
        // a mistaken role selection in system settings.
        if ($user->can('device.manage')) {
            return false;
        }

        $roles = collect(preg_split('/[\s,;]+/', (string) SystemSetting::getValue('login_device_control_roles', 'TrungTam'), -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($role) => trim($role))
            ->filter()
            ->values();

        return $roles->isNotEmpty() && $roles->contains(fn ($role) => $user->hasRole($role));
    }

    private function shouldSkipRoute(Request $request): bool
    {
        return $request->routeIs('login') || $request->routeIs('logout');
    }

    private function logForcedLogout(Request $request, User $user, string $message): void
    {
        LoginLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'failed',
            'message' => $message,
            'logged_at' => now(),
        ]);
    }
}
