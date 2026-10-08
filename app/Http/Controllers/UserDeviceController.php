<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class UserDeviceController extends Controller
{
    public function index(Request $request)
    {
        $query = UserDevice::with(['user.roles', 'approvedBy', 'blockedBy']);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('device_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('device_uid', 'like', '%' . $request->keyword . '%')
                    ->orWhere('ip_address', 'like', '%' . $request->keyword . '%')
                    ->orWhere('user_agent', 'like', '%' . $request->keyword . '%')
                    ->orWhereHas('user', function ($user) use ($request) {
                        $user->where('name', 'like', '%' . $request->keyword . '%')
                            ->orWhere('username', 'like', '%' . $request->keyword . '%')
                            ->orWhere('email', 'like', '%' . $request->keyword . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $devices = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->latest('requested_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $deviceLogs = Activity::with('causer')
            ->where('subject_type', UserDevice::class)
            ->whereIn('subject_id', $devices->getCollection()->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('subject_id')
            ->map(fn ($logs) => $logs->take(4));

        $selectedUsers = $request->filled('user_id')
            ? User::where('id', $request->user_id)->get()->keyBy('id')
            : collect();

        return view('user_devices.index', compact('devices', 'selectedUsers', 'deviceLogs'));
    }

    public function approve(Request $request, UserDevice $userDevice)
    {
        $data = $request->validate([
            'device_name' => ['nullable', 'string', 'max:255'],
        ], [
            'device_name.max' => 'Tên thiết bị không được vượt quá 255 ký tự.',
        ]);

        $old = $userDevice->only(['device_name', 'status']);

        $userDevice->update([
            'device_name' => ($data['device_name'] ?? null) ?: $userDevice->device_name,
            'status' => UserDevice::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'blocked_at' => null,
            'blocked_by' => null,
        ]);

        ActivityLogger::log(
            'Thiết bị đăng nhập',
            'approve',
            'Duyệt thiết bị đăng nhập cho tài khoản: ' . ($userDevice->user->username ?? $userDevice->user_id),
            $old,
            $userDevice->fresh()->only(['device_name', 'status']),
            $userDevice
        );

        return back()->with('success', 'Đã duyệt thiết bị đăng nhập.');
    }

    public function block(UserDevice $userDevice)
    {
        $old = $userDevice->only(['status']);

        $userDevice->update([
            'status' => UserDevice::STATUS_BLOCKED,
            'blocked_at' => now(),
            'blocked_by' => Auth::id(),
        ]);

        ActivityLogger::log(
            'Thiết bị đăng nhập',
            'block',
            'Khóa thiết bị đăng nhập của tài khoản: ' . ($userDevice->user->username ?? $userDevice->user_id),
            $old,
            $userDevice->fresh()->only(['status']),
            $userDevice
        );

        return back()->with('success', 'Đã khóa thiết bị đăng nhập.');
    }

    public function destroy(UserDevice $userDevice)
    {
        $username = $userDevice->user->username ?? $userDevice->user_id;

        ActivityLogger::log(
            'Thiết bị đăng nhập',
            'delete',
            'Xóa thiết bị đăng nhập của tài khoản: ' . $username,
            $userDevice->toArray(),
            null,
            $userDevice
        );

        $userDevice->delete();

        return back()->with('success', 'Đã xóa thiết bị. Nếu người dùng đăng nhập lại trên máy này, hệ thống sẽ tạo yêu cầu duyệt mới.');
    }
}
