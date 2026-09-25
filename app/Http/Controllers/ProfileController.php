<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $oldData = $request->user()->only(['name', 'email', 'smartca_user_id']);

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        ActivityLogger::log(
            'Tài khoản cá nhân',
            'update_profile',
            'Cập nhật thông tin tài khoản cá nhân',
            $oldData,
            $request->user()->only(['name', 'email', 'smartca_user_id']),
            $request->user()
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

}
