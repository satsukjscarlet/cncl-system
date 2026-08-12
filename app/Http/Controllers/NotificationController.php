<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->get('status') === 'unread') {
            $query->unread();
        }

        if ($request->get('status') === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function feed(Request $request)
    {
        $user = $request->user();
        $unreadCount = UserNotification::where('user_id', $user->id)
            ->unread()
            ->count();

        $notifications = UserNotification::where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get();

        return response()->json([
            'label' => $unreadCount > 0 ? ($unreadCount > 99 ? '99+' : $unreadCount) : '',
            'label_color' => $unreadCount > 0 ? 'danger' : 'secondary',
            'icon_color' => $unreadCount > 0 ? 'warning' : 'muted',
            'dropdown' => view('notifications.dropdown', compact('notifications', 'unreadCount'))->render(),
        ]);
    }

    public function open(Request $request, UserNotification $notification)
    {
        $this->authorizeNotification($request, $notification);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return redirect($notification->url ?: route('notifications.index'));
    }

    public function markAsRead(Request $request, UserNotification $notification)
    {
        $this->authorizeNotification($request, $notification);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return back()->with('success', 'Đã đánh dấu thông báo là đã đọc.');
    }

    public function markAllAsRead(Request $request)
    {
        UserNotification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }

    private function authorizeNotification(Request $request, UserNotification $notification): void
    {
        if ((int) $notification->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
