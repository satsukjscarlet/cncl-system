<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ActivityLogger;
use App\Http\Controllers\Controller;
use App\Services\SessionSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $revokedSessions = app(SessionSecurityService::class)->revokeUserSessions($request->user(), $request->session()->getId());

        ActivityLogger::log(
            'Tài khoản cá nhân',
            'change_password',
            'Người dùng tự đổi mật khẩu',
            null,
            [
                'username' => $request->user()->username,
                'logout_other_devices' => true,
                'revoked_sessions' => $revokedSessions,
            ],
            $request->user()
        );

        return back()->with('status', 'password-updated');
    }
}
