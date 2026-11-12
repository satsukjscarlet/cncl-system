<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CertificateReportQuery
{
    public const DATE_OPTIONS = ['request_created_at' => 'Ngày tạo yêu cầu', 'certificate_created_at' => 'Ngày lập phiếu', 'signed_at' => 'Ngày ký số'];

    public function validate(Request $request): void
    {
        $request->validate([
            'date_basis' => ['nullable', Rule::in(array_keys(self::DATE_OPTIONS))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('date_from') ? 'after_or_equal:date_from' : null]),
            'include_history' => ['nullable', 'boolean'],
            'request_status' => ['nullable', Rule::in(['DRAFT', 'WAIT_CENTER', 'RETURNED_SALES_UNIT', 'WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING', 'SIGNED', 'COMPLETED', 'CANCELLED'])],
            'certificate_status' => ['nullable', Rule::in(['NO_CERTIFICATE', 'WAIT_PTN_MANAGER_APPROVAL', 'READY_TO_SIGN', 'SIGN_PENDING', 'SIGN_EXPIRED', 'ISSUED', 'REJECTED', 'REVOKED', 'SIGN_READY', 'UNSIGNED'])],
        ]);
    }

    public function requestBase(Request $request, User $user)
    {
        $this->validate($request);
        $query = CertificateRequest::visibleTo($user);
        $this->centers($query, $request, $user, false);
        if ($request->filled('request_status')) {
            $query->where('certificate_requests.status', $request->request_status);
        }
        $this->dates($query, $request, false);

        return $query;
    }

    public function certificateBase(Request $request, User $user)
    {
        $this->validate($request);
        $query = QualityCertificate::query()->whereHas('request');
        if ($user->isSalesUnit()) $query->where('quality_certificates.status', 'ISSUED')->whereNotNull('signed_at')
            ->whereHas('request', fn ($q) => $q->visibleTo($user));
        $this->centers($query, $request, $user, true);
        if ($request->filled('request_status')) {
            $query->whereHas('request', fn ($q) => $q->where('status', $request->request_status));
        }
        $this->dates($query, $request, true);

        return $query;
    }

    private function centers($query, Request $request, User $user, bool $certificate): void
    {
        $center = $request->input('distribution_center_id');
        if ($user->isExternalAccount()) {
            abort_unless($user->distribution_center_id, 403, 'Tài khoản Trung tâm chưa được gắn Trung tâm phân phối.');
            $center = $user->distribution_center_id;
        }
        if ($center) {
            if ($certificate) {
                $query->whereHas('request', fn ($q) => $q->where('distribution_center_id', $center));
            } else {
                $query->where('distribution_center_id', $center);
            }
        }
    }

    private function dates($query, Request $request, bool $certificate): void
    {
        $basis = $request->input('date_basis') ?: 'request_created_at';
        $apply = function ($q, string $column) use ($request) {
            if ($request->filled('date_from')) {
                $q->whereDate($column, '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $q->whereDate($column, '<=', $request->date_to);
            }
        };
        if (! $request->filled('date_from') && ! $request->filled('date_to')) {
            return;
        }
        if ($certificate) {
            if ($basis === 'request_created_at') {
                $query->whereHas('request', fn ($q) => $apply($q, 'certificate_requests.created_at'));
            } else {
                $apply($query, 'quality_certificates.'.($basis === 'signed_at' ? 'signed_at' : 'created_at'));
            }
        } else {
            if ($basis === 'request_created_at') {
                $apply($query, 'certificate_requests.created_at');
            } else {
                $query->whereHas('reportCertificate', fn ($q) => $apply($q, 'quality_certificates.'.($basis === 'signed_at' ? 'signed_at' : 'created_at')));
            }
        }
    }

    public function requestStatus($query, string $status): void
    {
        if ($status === 'NO_CERTIFICATE') {
            $query->whereDoesntHave('reportCertificate');
        } else {
            $query->whereHas('reportCertificate', fn ($q) => $this->certificateStatus($q, $status));
        }
    }

    public function certificateStatus($query, string $status): void
    {
        $expired = now()->subMinutes(max(1, (int) config('services.smartca.pending_ttl_minutes', 5)));
        switch ($status) {
            case 'NO_CERTIFICATE': $query->whereRaw('1 = 0');
                break;
            case 'WAIT_PTN_MANAGER_APPROVAL': $query->waitingForManagerApproval();
                break;
            case 'READY_TO_SIGN':
            case 'SIGN_READY':
                $query->whereNull('signed_at')->whereIn('status', $status === 'SIGN_READY' ? ['DRAFT', 'WAIT_PTN_MANAGER_APPROVAL', 'READY_TO_SIGN'] : ['READY_TO_SIGN'])
                    ->where(fn ($q) => $q->whereNull('smartca_status')->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']));
                break;
            case 'SIGN_PENDING': $query->whereNull('signed_at')->where('smartca_status', 'PENDING')->where('smartca_requested_at', '>', $expired);
                break;
            case 'SIGN_EXPIRED':
                $query->whereNull('signed_at')->where(fn ($q) => $q->where('status', 'SIGN_EXPIRED')->orWhere('smartca_status', 'EXPIRED')
                    ->orWhere(fn ($p) => $p->where('smartca_status', 'PENDING')->where('smartca_requested_at', '<=', $expired)));
                break;
            case 'ISSUED': $query->where('status', 'ISSUED')->whereNotNull('signed_at');
                break;
            case 'REJECTED':
            case 'REVOKED': $query->where('status', $status);
                break;
            case 'UNSIGNED': $query->whereNull('signed_at')->whereNotIn('status', ['REJECTED', 'REVOKED']);
                break;
        }
    }
}
