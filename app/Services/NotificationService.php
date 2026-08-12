<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function sendToUser(User|int|null $user, array $payload): void
    {
        if (!$user) {
            return;
        }

        $userId = $user instanceof User ? $user->id : $user;
        $this->insert([$userId], $payload);
    }

    public function sendToUsers($users, array $payload): void
    {
        $ids = collect($users)
            ->map(fn ($user) => $user instanceof User ? $user->id : $user)
            ->filter()
            ->unique()
            ->values();

        $this->insert($ids, $payload);
    }

    public function sendToRole(string $role, array $payload): void
    {
        $ids = User::role($role)
            ->where('is_active', true)
            ->pluck('id');

        $this->insert($ids, $payload);
    }

    public function sendToRoles(array $roles, array $payload): void
    {
        $ids = User::role($roles)
            ->where('is_active', true)
            ->pluck('id');

        $this->insert($ids, $payload);
    }

    public function sendToCenter(?int $distributionCenterId, array $payload): void
    {
        if (!$distributionCenterId) {
            return;
        }

        $ids = User::role('TrungTam')
            ->where('is_active', true)
            ->where('distribution_center_id', $distributionCenterId)
            ->pluck('id');

        $this->insert($ids, $payload);
    }

    public function notifyRequestCreated(CertificateRequest $request): void
    {
        $this->sendToRole('DVKH', [
            'type' => $request->is_urgent ? 'request_urgent_created' : 'request_created',
            'title' => $request->is_urgent ? 'Có yêu cầu gấp mới' : 'Có yêu cầu cấp phiếu mới',
            'message' => 'Yêu cầu ' . $request->request_no . ' đang chờ DVKH kiểm tra.',
            'url' => route('dvkh.requests.show', $request),
            'data' => $this->requestData($request),
        ]);
    }

    public function notifyRequestApproved(CertificateRequest $request): void
    {
        $this->sendToRole('PTN', [
            'type' => 'request_approved',
            'title' => 'Yêu cầu đã chuyển sang PTN',
            'message' => 'Yêu cầu ' . $request->request_no . ' đã được DVKH duyệt.',
            'url' => route('ptn.requests.show', $request),
            'data' => $this->requestData($request),
        ]);

        $this->sendToCenter($request->distribution_center_id, [
            'type' => 'request_approved_for_center',
            'title' => 'Yêu cầu đã được DVKH duyệt',
            'message' => 'Yêu cầu ' . $request->request_no . ' đã chuyển sang PTN.',
            'url' => route('certificate-requests.show', $request),
            'data' => $this->requestData($request),
        ]);
    }

    public function notifyRequestRejected(CertificateRequest $request): void
    {
        $this->sendToCenter($request->distribution_center_id, [
            'type' => 'request_rejected',
            'title' => 'Yêu cầu bị trả lại',
            'message' => 'Yêu cầu ' . $request->request_no . ' đã được DVKH trả lại.',
            'url' => route('certificate-requests.show', $request),
            'data' => $this->requestData($request),
        ]);
    }

    public function notifyCertificateCreated(QualityCertificate $certificate): void
    {
        $certificate->loadMissing('request');

        $this->sendToRole('TruongPTN', [
            'type' => 'certificate_created',
            'title' => 'Có phiếu chờ ký',
            'message' => 'Phiếu ' . $certificate->certificate_no . ' đã được PTN lập và chờ Trưởng PTN duyệt ký.',
            'url' => route('quality-certificates.show', $certificate),
            'data' => $this->certificateData($certificate),
        ]);

        if ($certificate->request) {
            $this->sendToCenter($certificate->request->distribution_center_id, [
                'type' => 'certificate_created_for_center',
                'title' => 'PTN đã lập phiếu',
                'message' => 'Phiếu ' . $certificate->certificate_no . ' đã được lập từ yêu cầu ' . $certificate->request->request_no . '.',
                'url' => route('quality-certificates.show', $certificate),
                'data' => $this->certificateData($certificate),
            ]);
        }
    }

    public function notifyCertificateReturned(QualityCertificate $certificate, string $target): void
    {
        $certificate->loadMissing('request');
        $payload = [
            'type' => 'certificate_returned',
            'title' => 'Phiếu bị Trưởng PTN trả lại',
            'message' => 'Phiếu ' . $certificate->certificate_no . ' đã được trả lại về ' . $target . '.',
            'url' => route('quality-certificates.show', $certificate),
            'data' => $this->certificateData($certificate),
        ];

        if ($target === 'DVKH') {
            $this->sendToRole('DVKH', $payload);
        } elseif ($target === 'PTN') {
            $this->sendToRole('PTN', $payload);
        }

        if ($certificate->request) {
            $this->sendToCenter($certificate->request->distribution_center_id, $payload);
        }
    }

    public function notifyCertificateSigned(QualityCertificate $certificate): void
    {
        $certificate->loadMissing('request');

        if ($certificate->request) {
            $this->sendToCenter($certificate->request->distribution_center_id, [
                'type' => 'certificate_signed',
                'title' => 'Phiếu CNCL đã ký',
                'message' => 'Phiếu ' . $certificate->certificate_no . ' đã ký số và sẵn sàng tra cứu.',
                'url' => route('quality-certificates.show', $certificate),
                'data' => $this->certificateData($certificate),
            ]);
        }

        $this->sendToRoles(['DVKH', 'PTN'], [
            'type' => 'certificate_signed_internal',
            'title' => 'Phiếu CNCL đã phát hành',
            'message' => 'Phiếu ' . $certificate->certificate_no . ' đã ký số thành công.',
            'url' => route('quality-certificates.show', $certificate),
            'data' => $this->certificateData($certificate),
        ]);
    }

    private function insert($userIds, array $payload): void
    {
        $ids = $userIds instanceof Collection ? $userIds : collect($userIds);
        $ids = $ids->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return;
        }

        $now = now();
        $rows = $ids->map(fn ($id) => [
            'user_id' => $id,
            'type' => $payload['type'] ?? 'system',
            'title' => $payload['title'] ?? 'Thông báo',
            'message' => $payload['message'] ?? null,
            'url' => $payload['url'] ?? null,
            'data' => isset($payload['data']) ? json_encode($payload['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table((new UserNotification())->getTable())->insert($rows);
    }

    private function requestData(CertificateRequest $request): array
    {
        return [
            'request_id' => $request->id,
            'request_no' => $request->request_no,
            'distribution_center_id' => $request->distribution_center_id,
        ];
    }

    private function certificateData(QualityCertificate $certificate): array
    {
        return [
            'certificate_id' => $certificate->id,
            'certificate_no' => $certificate->certificate_no,
            'request_id' => $certificate->certificate_request_id,
            'distribution_center_id' => $certificate->request?->distribution_center_id,
        ];
    }
}
