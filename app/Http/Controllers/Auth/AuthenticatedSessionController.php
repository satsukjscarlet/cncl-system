<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
