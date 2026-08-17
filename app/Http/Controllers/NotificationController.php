<?php

namespace App\Http\Controllers;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $latestUnread = $notifications->firstWhere('read_at', null);

        return response()->json([
            'label' => $unreadCount > 0 ? ($unreadCount > 99 ? '99+' : $unreadCount) : '',
            'label_color' => $unreadCount > 0 ? 'danger' : 'secondary',
            'icon_color' => $unreadCount > 0 ? 'warning' : 'muted',
            'dropdown' => view('notifications.dropdown', compact('notifications', 'unreadCount'))->render(),
            'browser_notification' => $latestUnread ? [
                'id' => $latestUnread->id,
                'title' => $latestUnread->title,
                'message' => $latestUnread->message,
                'url' => route('notifications.open', $latestUnread),
                'type' => $latestUnread->type,
                'created_at' => optional($latestUnread->created_at)->toIso8601String(),
            ] : null,
        ]);
    }

    public function open(Request $request, UserNotification $notification)
    {
        $this->authorizeNotification($request, $notification);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        $url = $this->resolveNotificationUrl($request, $notification);

        if (!$url) {
            return redirect()
                ->route('notifications.index')
                ->with('error', 'Thông báo này không còn mở được vì dữ liệu đã thay đổi hoặc tài khoản không còn quyền truy cập.');
        }

        return redirect($url);
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

    private function resolveNotificationUrl(Request $request, UserNotification $notification): ?string
    {
        $data = $notification->data ?? [];
        $user = $request->user();

        $requestId = data_get($data, 'request_id');
        $certificateId = data_get($data, 'certificate_id');

        if ($certificateId) {
            $certificate = QualityCertificate::with('request')->find($certificateId);

            if ($certificate && $this->canOpenCertificate($user, $certificate)) {
                return route('quality-certificates.show', $certificate);
            }

            if ($certificate?->request && $url = $this->requestUrlForUser($user, $certificate->request)) {
                return $url;
            }
        }

        if ($requestId) {
            $certificateRequest = CertificateRequest::find($requestId);

            if ($certificateRequest && $url = $this->requestUrlForUser($user, $certificateRequest)) {
                return $url;
            }
        }

        return $this->safeStoredUrl($request, $notification->url);
    }

    private function canOpenCertificate($user, QualityCertificate $certificate): bool
    {
        if (!$user->can('certificate.view')) {
            return false;
        }

        return !$user->hasRole('TrungTam')
            || (int) $certificate->request?->distribution_center_id === (int) $user->distribution_center_id;
    }

    private function requestUrlForUser($user, CertificateRequest $certificateRequest): ?string
    {
        if (
            $user->can('dvkh.process')
            && in_array($certificateRequest->status, ['WAIT_DVKH', 'WAIT_PTN', 'CANCELLED'], true)
        ) {
            return route('dvkh.requests.show', $certificateRequest);
        }

        if (
            $user->can('ptn.process')
            && in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING'], true)
        ) {
            return route('ptn.requests.show', $certificateRequest);
        }

        if (!$user->can('request.view')) {
            return null;
        }

        if (
            $user->hasRole('TrungTam')
            && (int) $certificateRequest->distribution_center_id !== (int) $user->distribution_center_id
        ) {
            return null;
        }

        return route('certificate-requests.show', $certificateRequest);
    }

    private function safeStoredUrl(Request $request, ?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (Str::startsWith($url, '/') && !Str::startsWith($url, '//')) {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (!$host || !hash_equals($request->getHost(), $host)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY);

        return $query ? $path . '?' . $query : $path;
    }
}
