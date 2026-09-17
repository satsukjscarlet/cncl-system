<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class WorkflowStepService
{
    public function forRequest(CertificateRequest $certificateRequest, ?Collection $logs = null): array
    {
        $certificateRequest->loadMissing(['qualityCertificate', 'qualityCertificates']);

        $certificate = $certificateRequest->qualityCertificate
            ?: $certificateRequest->qualityCertificates
                ->sortByDesc('created_at')
                ->first();

        return $this->build($certificateRequest, $certificate, $logs);
    }

    public function forCertificate(QualityCertificate $qualityCertificate, ?Collection $logs = null): array
    {
        $qualityCertificate->loadMissing(['request.qualityCertificate', 'request.qualityCertificates']);

        return $this->build($qualityCertificate->request, $qualityCertificate, $logs);
    }

    private function build(?CertificateRequest $request, ?QualityCertificate $certificate, ?Collection $logs = null): array
    {
        $logs ??= $this->loadLogs($request, $certificate);

        $steps = [
            $this->step(
                'Trung tâm tạo yêu cầu',
                'done',
                'fas fa-file-alt',
                $request?->created_at ?: $certificate?->created_at,
                $request
                    ? 'Yêu cầu ' . $request->request_no . ' được khởi tạo.'
                    : 'Phiếu được PTN lập trực tiếp.'
            ),
            $this->step(
                'DVKH kiểm tra',
                'pending',
                'fas fa-user-check',
                $this->logTime($logs, ['approve']),
                'Chờ DVKH xác nhận thông tin yêu cầu.'
            ),
            $this->step(
                'PTN lập phiếu',
                'pending',
                'fas fa-vials',
                $certificate?->created_at,
                'Chờ PTN lập phiếu CNCL.'
            ),
            $this->step(
                'Trưởng PTN duyệt / ký số',
                'pending',
                'fas fa-file-signature',
                $certificate?->smartca_requested_at ?: $this->logTime($logs, ['approve_for_signing', 'smartca_request', 'smartca_request_resend']),
                'Chờ Trưởng PTN duyệt nội dung hoặc gửi yêu cầu ký VNPT SmartCA.'
            ),
            $this->step(
                'Phát hành / thu hồi',
                'pending',
                'fas fa-paper-plane',
                $certificate?->signed_at ?: $certificate?->revoked_at,
                'Chờ ký số thành công và phát hành phiếu.'
            ),
        ];

        $this->applyRequestState($steps, $request, $certificate);

        if ($certificate) {
            $this->applyCertificateState($steps, $certificate);
        }

        return $steps;
    }

    private function applyRequestState(array &$steps, ?CertificateRequest $request, ?QualityCertificate $certificate): void
    {
        if (!$request) {
            $steps[0]['title'] = 'PTN lập trực tiếp';
            $steps[0]['description'] = 'Phiếu được PTN lập trực tiếp, không qua yêu cầu từ Trung tâm.';
            $steps[1]['status'] = 'skipped';
            $steps[1]['description'] = 'Không qua bước DVKH do PTN lập trực tiếp.';

            return;
        }

        if ($request->request_type === 'DIRECT_PTN') {
            $steps[0]['title'] = 'PTN lập trực tiếp';
            $steps[0]['description'] = 'Phiếu được PTN lập trực tiếp, không qua yêu cầu từ Trung tâm.';
            $steps[1]['status'] = 'skipped';
            $steps[1]['description'] = 'Không qua bước DVKH do PTN lập trực tiếp.';

            if (!$certificate) {
                $steps[2]['status'] = 'current';
                $steps[2]['description'] = 'PTN đang lập phiếu CNCL trực tiếp.';
            }

            return;
        }

        if ($request->status === 'DRAFT') {
            $steps[0]['status'] = 'current';
            $steps[0]['description'] = 'Yêu cầu đang được lưu nháp, chưa gửi sang DVKH.';

            return;
        }

        if ($request->status === 'WAIT_DVKH') {
            $steps[1]['status'] = 'current';
            $steps[1]['description'] = 'Đang chờ DVKH xác nhận hoặc trả lại yêu cầu.';

            return;
        }

        if ($request->status === 'CANCELLED') {
            $steps[1]['status'] = 'danger';
            $steps[1]['time'] = $request->updated_at;
            $steps[1]['description'] = 'Yêu cầu đã bị trả lại / hủy. Xem ghi chú để biết lý do.';
            $steps[2]['status'] = 'skipped';
            $steps[3]['status'] = 'skipped';
            $steps[4]['status'] = 'skipped';

            return;
        }

        $steps[1]['status'] = 'done';
        $steps[1]['time'] ??= $request->updated_at;
        $steps[1]['description'] = 'DVKH đã xác nhận và chuyển yêu cầu sang PTN.';

        if ($request->status === 'WAIT_PTN') {
            $steps[2]['status'] = 'current';
            $steps[2]['description'] = 'Đang chờ PTN lập phiếu CNCL.';

            return;
        }

        if ($certificate || in_array($request->status, ['PTN_PROCESSING', 'COMPLETED'], true)) {
            $steps[2]['status'] = 'done';
            $steps[2]['description'] = $certificate
                ? 'PTN đã lập phiếu ' . $certificate->certificate_no . '.'
                : 'PTN đã tiếp nhận xử lý yêu cầu.';
        }
    }

    private function applyCertificateState(array &$steps, QualityCertificate $certificate): void
    {
        if ($certificate->status === QualityCertificate::STATUS_REVOKED) {
            $steps[3]['status'] = 'done';
            $steps[3]['description'] = 'Phiếu cũ đã được ký số trước khi bị thu hồi.';

            $steps[4]['status'] = 'danger';
            $steps[4]['time'] = $certificate->revoked_at;
            $steps[4]['description'] = 'Phiếu đã hủy / thu hồi. Lý do: ' . ($certificate->revoked_reason ?: '-');

            return;
        }

        if ($certificate->status === QualityCertificate::STATUS_REJECTED) {
            $steps[2]['status'] = 'done';
            $steps[2]['description'] = 'PTN đã lập phiếu ' . $certificate->certificate_no . '.';

            $steps[3]['status'] = 'danger';
            $steps[3]['time'] = $certificate->rejected_at;
            $steps[3]['description'] = 'Trưởng PTN đã trả lại phiếu'
                . ($certificate->rejected_to === 'DVKH' ? ' về DVKH' : '')
                . ': ' . ($certificate->rejected_reason ?: '-');

            $steps[4]['status'] = 'skipped';
            $steps[4]['description'] = 'Chưa phát hành vì phiếu đã bị trả lại.';

            return;
        }

        $steps[2]['status'] = 'done';
        $steps[2]['description'] = 'PTN đã lập phiếu ' . $certificate->certificate_no . '.';

        if ($certificate->signed_at || $certificate->status === QualityCertificate::STATUS_ISSUED) {
            $steps[3]['status'] = 'done';
            $steps[3]['time'] = $certificate->signed_at;
            $steps[3]['description'] = 'Phiếu đã ký số thành công.';

            $steps[4]['status'] = 'done';
            $steps[4]['time'] = $certificate->signed_at;
            $steps[4]['description'] = 'Phiếu đã phát hành. Hệ thống gửi email theo cấu hình hiện tại.';

            return;
        }

        if ($certificate->smartcaStatusExpired()) {
            $steps[3]['status'] = 'danger';
            $steps[3]['description'] = 'Yêu cầu ký đã quá hạn, cần kiểm tra kết quả cũ hoặc gửi lại yêu cầu ký.';

            return;
        }

        if ($certificate->smartca_status === 'PENDING' || $certificate->status === QualityCertificate::STATUS_SIGN_PENDING) {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Đang chờ Trưởng PTN xác nhận trên app VNPT SmartCA.';

            return;
        }

        if ($certificate->status === QualityCertificate::STATUS_READY_TO_SIGN) {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Trưởng PTN đã duyệt nội dung, phiếu đang nằm trong danh sách chờ gửi ký.';

            return;
        }

        if ($certificate->isAwaitingManagerApproval()) {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Chờ Trưởng PTN kiểm tra nội dung phiếu.';

            return;
        }

        $steps[3]['status'] = 'current';
        $steps[3]['description'] = 'Chờ Trưởng PTN kiểm tra và gửi yêu cầu ký số.';
    }

    private function step(string $title, string $status, string $icon, mixed $time, string $description): array
    {
        return compact('title', 'status', 'icon', 'time', 'description');
    }

    private function logTime(Collection $logs, array $actions): mixed
    {
        $log = $logs->first(fn ($item) => in_array($item->properties['action'] ?? null, $actions, true));

        return $log?->created_at;
    }

    private function loadLogs(?CertificateRequest $request, ?QualityCertificate $certificate): Collection
    {
        $query = Activity::query()->latest();

        $requestId = $request?->getKey();
        $certificateId = $certificate?->getKey();

        if (!$requestId && !$certificateId) {
            return collect();
        }

        return $query
            ->where(function ($query) use ($requestId, $certificateId) {
                if ($requestId) {
                    $query->orWhere(function ($subjectQuery) use ($requestId) {
                        $subjectQuery
                            ->where('subject_type', CertificateRequest::class)
                            ->where('subject_id', $requestId);
                    });
                }

                if ($certificateId) {
                    $query->orWhere(function ($subjectQuery) use ($certificateId) {
                        $subjectQuery
                            ->where('subject_type', QualityCertificate::class)
                            ->where('subject_id', $certificateId);
                    });
                }
            })
            ->limit(30)
            ->get();
    }
}
