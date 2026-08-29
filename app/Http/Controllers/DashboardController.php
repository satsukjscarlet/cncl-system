<?php

namespace App\Http\Controllers;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Models\SlaConfig;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $role = $this->primaryRole($user);
        $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

        $requestQuery = $this->requestScope($user);
        $certificateQuery = $this->certificateScope($user);

        $metrics = [
            'total_requests' => (clone $requestQuery)->count(),
            'request_draft' => (clone $requestQuery)->where('status', 'DRAFT')->count(),
            'wait_dvkh' => (clone $requestQuery)->where('status', 'WAIT_DVKH')->count(),
            'wait_ptn' => (clone $requestQuery)->where('status', 'WAIT_PTN')->count(),
            'ptn_processing' => (clone $requestQuery)->where('status', 'PTN_PROCESSING')->count(),
            'completed' => (clone $requestQuery)->where('status', 'COMPLETED')->count(),
            'cancelled' => (clone $requestQuery)->where('status', 'CANCELLED')->count(),
            'urgent' => (clone $requestQuery)->where('is_urgent', true)->whereNotIn('status', ['COMPLETED', 'CANCELLED'])->count(),
            'urgent_dvkh' => (clone $requestQuery)->where('status', 'WAIT_DVKH')->where('is_urgent', true)->count(),
            'urgent_ptn' => (clone $requestQuery)->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING'])->where('is_urgent', true)->count(),
            'duplicate_invoice' => $this->duplicateInvoiceCount(clone $requestQuery),
            'duplicate_invoice_dvkh' => $this->duplicateInvoiceCount((clone $requestQuery)->where('status', 'WAIT_DVKH')),
            'total_certificates' => (clone $certificateQuery)->count(),
            'signed_certificates' => (clone $certificateQuery)->whereNotNull('signed_at')->count(),
            'issued_certificates' => (clone $certificateQuery)->whereNotNull('signed_at')->where('status', 'ISSUED')->count(),
            'revoked_certificates' => (clone $certificateQuery)->where('status', 'REVOKED')->count(),
            'rejected_certificates' => (clone $certificateQuery)->where('status', 'REJECTED')->count(),
            'unsigned_certificates' => (clone $certificateQuery)->whereNull('signed_at')->whereNotIn('status', ['REJECTED', 'REVOKED'])->count(),
            'sign_waiting_approval' => (clone $certificateQuery)
                ->whereNull('signed_at')
                ->whereIn('status', ['DRAFT', 'WAIT_PTN_MANAGER_APPROVAL'])
                ->where(function ($q) {
                    $q->whereNull('smartca_status')
                        ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                })
                ->count(),
            'sign_ready' => (clone $certificateQuery)
                ->whereNull('signed_at')
                ->where('status', 'READY_TO_SIGN')
                ->where(function ($q) {
                    $q->whereNull('smartca_status')
                        ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                })
                ->count(),
            'sign_pending' => (clone $certificateQuery)
                ->whereNull('signed_at')
                ->where('smartca_status', 'PENDING')
                ->where('smartca_requested_at', '>', $expiredBefore)
                ->count(),
            'sign_expired' => (clone $certificateQuery)
                ->whereNull('signed_at')
                ->where(function ($q) use ($expiredBefore) {
                    $q->where('smartca_status', 'EXPIRED')
                        ->orWhere(function ($pending) use ($expiredBefore) {
                            $pending->where('smartca_status', 'PENDING')
                                ->where('smartca_requested_at', '<=', $expiredBefore);
                        });
                })
                ->count(),
        ];

        $cards = $this->cardsForRole($role, $metrics);
        $primaryList = $this->primaryListForRole($role, $user, $expiredBefore);
        $secondaryList = $this->secondaryListForRole($role, $user);
        $slaAlerts = $this->slaAlerts($user);

        $monthExpression = $this->monthExpression('created_at');
        $monthlyCertificates = (clone $certificateQuery)
            ->selectRaw($monthExpression . ' as month, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month')
            ->toArray();

        $chartLabels = [];
        $chartValues = [];

        for ($month = 1; $month <= 12; $month++) {
            $chartLabels[] = 'T' . $month;
            $chartValues[] = $monthlyCertificates[$month] ?? 0;
        }

        return view('dashboard.index', compact(
            'role',
            'cards',
            'primaryList',
            'secondaryList',
            'slaAlerts',
            'chartLabels',
            'chartValues'
        ));
    }

    private function primaryRole($user): string
    {
        foreach (['Admin', 'LanhDao', 'TrungTam', 'DVKH', 'PTN', 'TruongPTN'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return 'Viewer';
    }

    private function requestScope($user): Builder
    {
        $query = CertificateRequest::query();

        if ($user->hasRole('TrungTam')) {
            $query->where('distribution_center_id', $user->distribution_center_id);
        }

        return $query;
    }

    private function certificateScope($user): Builder
    {
        $query = QualityCertificate::query();

        if ($user->hasRole('TrungTam')) {
            $query->whereHas('request', function ($q) use ($user) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            });
        }

        return $query;
    }

    private function cardsForRole(string $role, array $metrics): array
    {
        return match ($role) {
            'Admin' => [
                $this->card('Tổng yêu cầu', $metrics['total_requests'], 'fas fa-layer-group', 'primary', route('certificate-requests.index', ['status_group' => 'all'])),
                $this->card('Yêu cầu nháp', $metrics['request_draft'], 'fas fa-edit', 'secondary', route('certificate-requests.index', ['status_group' => 'draft'])),
                $this->card('Chờ DVKH', $metrics['wait_dvkh'], 'fas fa-user-check', 'warning', route('certificate-requests.index', ['status' => 'WAIT_DVKH'])),
                $this->card('Chờ PTN lập phiếu', $metrics['wait_ptn'], 'fas fa-vials', 'info', route('certificate-requests.index', ['status' => 'WAIT_PTN'])),
                $this->card('Phiếu nháp / chờ ký', $metrics['sign_ready'], 'fas fa-pen-nib', 'primary', route('quality-certificates.index', ['status' => 'SIGN_READY'])),
                $this->card('Đang chờ app ký', $metrics['sign_pending'], 'fas fa-mobile-alt', 'warning', route('quality-certificates.index', ['status' => 'SMARTCA_PENDING'])),
                $this->card('Quá hạn ký số', $metrics['sign_expired'], 'fas fa-hourglass-end', 'danger', route('quality-certificates.index', ['status' => 'SMARTCA_EXPIRED'])),
                $this->card('Đã phát hành', $metrics['issued_certificates'], 'fas fa-check-circle', 'success', route('quality-certificates.index', ['status' => 'SIGNED'])),
                $this->card('Đã hủy / thu hồi', $metrics['revoked_certificates'], 'fas fa-ban', 'danger', route('quality-certificates.index', ['status' => 'REVOKED'])),
                $this->card('Trưởng PTN trả lại', $metrics['rejected_certificates'], 'fas fa-undo', 'secondary', route('quality-certificates.index', ['status' => 'REJECTED'])),
            ],
            'TrungTam' => [
                $this->card('Yêu cầu của tôi', $metrics['total_requests'], 'fas fa-file-alt', 'primary', route('certificate-requests.index')),
                $this->card('Chờ DVKH', $metrics['wait_dvkh'], 'fas fa-user-check', 'warning', route('certificate-requests.index', ['status' => 'WAIT_DVKH'])),
                $this->card('Chờ PTN lập phiếu', $metrics['wait_ptn'], 'fas fa-vials', 'info', route('certificate-requests.index', ['status' => 'WAIT_PTN'])),
                $this->card('Đã lập phiếu - chờ ký', $metrics['ptn_processing'], 'fas fa-file-signature', 'primary', route('certificate-requests.index', ['status' => 'PTN_PROCESSING'])),
                $this->card('Phiếu đã cấp', $metrics['issued_certificates'], 'fas fa-check-circle', 'success', route('quality-certificates.index', ['status' => 'SIGNED'])),
            ],
            'DVKH' => [
                $this->card('Chờ DVKH kiểm tra', $metrics['wait_dvkh'], 'fas fa-user-check', 'warning', route('dvkh.requests.index', ['status' => 'WAIT_DVKH'])),
                $this->card('Yêu cầu gấp', $metrics['urgent_dvkh'], 'fas fa-bolt', 'danger', route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'urgent' => '1'])),
                $this->card('Trùng số hóa đơn', $metrics['duplicate_invoice_dvkh'], 'fas fa-copy', 'orange', route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'duplicate_invoice' => '1'])),
                $this->card('Đã chuyển PTN', $metrics['wait_ptn'], 'fas fa-vials', 'info', route('dvkh.requests.index', ['status' => 'WAIT_PTN'])),
            ],
            'PTN' => [
                $this->card('Chờ PTN lập phiếu', $metrics['wait_ptn'], 'fas fa-inbox', 'warning', route('ptn.requests.index', ['status' => 'WAIT_PTN'])),
                $this->card('Đã lập phiếu - chờ ký', $metrics['ptn_processing'], 'fas fa-vials', 'info', route('ptn.requests.index', ['status' => 'PTN_PROCESSING'])),
                $this->card('Phiếu chờ Trưởng PTN ký', $metrics['sign_ready'], 'fas fa-file-signature', 'primary', route('quality-certificates.index', ['status' => 'SIGN_READY'])),
                $this->card('Yêu cầu gấp', $metrics['urgent_ptn'], 'fas fa-bolt', 'danger', route('ptn.requests.index', ['status' => '', 'urgent' => '1'])),
            ],
            'TruongPTN' => [
                $this->card('Chờ duyệt', $metrics['sign_waiting_approval'], 'fas fa-user-check', 'info', route('quality-certificates.signing-queue', ['status' => 'WAIT_APPROVAL'])),
                $this->card('Chờ gửi ký', $metrics['sign_ready'], 'fas fa-paper-plane', 'primary', route('quality-certificates.ready-to-sign')),
                $this->card('Đang chờ app', $metrics['sign_pending'], 'fas fa-mobile-alt', 'warning', route('quality-certificates.signing-queue', ['status' => 'PENDING'])),
                $this->card('Quá hạn ký', $metrics['sign_expired'], 'fas fa-hourglass-end', 'danger', route('quality-certificates.signing-queue', ['status' => 'EXPIRED'])),
                $this->card('Đã ký', $metrics['issued_certificates'], 'fas fa-check-circle', 'success', route('quality-certificates.index', ['status' => 'SIGNED'])),
            ],
            default => [
                $this->card('Tổng yêu cầu', $metrics['total_requests'], 'fas fa-file-alt', 'primary', route('certificate-requests.index')),
                $this->card('Chờ DVKH', $metrics['wait_dvkh'], 'fas fa-user-check', 'warning', route('certificate-requests.index', ['status' => 'WAIT_DVKH'])),
                $this->card('Chờ PTN lập phiếu', $metrics['wait_ptn'], 'fas fa-vials', 'info', route('certificate-requests.index', ['status' => 'WAIT_PTN'])),
                $this->card('Đã lập phiếu - chờ ký', $metrics['ptn_processing'], 'fas fa-file-signature', 'primary', route('certificate-requests.index', ['status' => 'PTN_PROCESSING'])),
                $this->card('Phiếu đã cấp', $metrics['issued_certificates'], 'fas fa-check-circle', 'success', route('quality-certificates.index', ['status' => 'SIGNED'])),
            ],
        };
    }

    private function primaryListForRole(string $role, $user, Carbon $expiredBefore): array
    {
        if ($role === 'TruongPTN') {
            $items = $this->certificateScope($user)
                ->with(['request.distributionCenter', 'request.customer'])
                ->whereNull('signed_at')
                ->whereNotIn('status', ['ISSUED', 'REVOKED'])
                ->where(function ($q) use ($expiredBefore) {
                    $q->whereIn('status', ['DRAFT', 'WAIT_PTN_MANAGER_APPROVAL', 'READY_TO_SIGN'])
                        ->orWhere('smartca_status', 'PENDING')
                        ->orWhere('smartca_status', 'EXPIRED')
                        ->orWhere(function ($pending) use ($expiredBefore) {
                            $pending->where('smartca_status', 'PENDING')
                                ->where('smartca_requested_at', '<=', $expiredBefore);
                        });
                })
                ->latest()
                ->take(8)
                ->get()
                ->map(function (QualityCertificate $certificate) {
                    $request = $certificate->request;
                    $customer = $request?->customer;

                    return [
                        'code' => $certificate->certificate_no,
                        'title' => $customer?->customer_name ?? '-',
                        'subtitle' => $request?->distributionCenter?->name ?? '',
                        'status' => $certificate->displayStatusMeta()['text'],
                        'date' => optional($certificate->created_at)->format('d/m/Y H:i'),
                        'url' => route('quality-certificates.show', $certificate),
                    ];
                });

            return [
                'title' => 'Phiếu cần Trưởng PTN xử lý ký số',
                'icon' => 'fas fa-user-check',
                'empty' => 'Không có phiếu cần xử lý ký số.',
                'items' => $items,
            ];
        }

        $statuses = match ($role) {
            'DVKH' => ['WAIT_DVKH'],
            'PTN' => ['WAIT_PTN', 'PTN_PROCESSING'],
            'TrungTam' => ['DRAFT', 'WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING', 'CANCELLED'],
            'Admin' => ['DRAFT', 'WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING', 'CANCELLED'],
            default => ['WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING'],
        };

        $items = $this->requestScope($user)
            ->with(['distributionCenter', 'customer', 'urgentReason'])
            ->whereIn('status', $statuses)
            ->latest()
            ->take(8)
            ->get()
            ->map(function (CertificateRequest $request) use ($role) {
                $customer = $request->customer;
                $centerName = $request->distributionCenter?->name ?? '';
                $projectName = $customer?->project_name;

                return [
                    'code' => $request->request_no,
                    'title' => $customer?->customer_name ?? '-',
                    'subtitle' => trim($centerName . ($projectName ? ' - ' . $projectName : '')),
                    'status' => $request->displayStatusMeta()['text'],
                    'date' => optional($request->created_at)->format('d/m/Y H:i'),
                    'url' => $this->requestUrlForRole($role, $request),
                    'urgent' => $request->is_urgent,
                ];
            });

        return [
            'title' => match ($role) {
                'TrungTam' => 'Yêu cầu của trung tâm cần theo dõi',
                'DVKH' => 'Yêu cầu chờ DVKH kiểm tra',
                'PTN' => 'Yêu cầu chờ PTN lập phiếu / chờ ký',
                'Admin' => 'Yêu cầu cần theo dõi',
                default => 'Công việc đang chờ thực hiện',
            },
            'icon' => match ($role) {
                'DVKH' => 'fas fa-user-check',
                'PTN' => 'fas fa-vials',
                default => 'fas fa-tasks',
            },
            'empty' => 'Không có công việc cần thực hiện.',
            'items' => $items,
        ];
    }

    private function secondaryListForRole(string $role, $user): array
    {
        $items = $this->certificateScope($user)
            ->with(['request.distributionCenter', 'request.customer'])
            ->latest()
            ->take(8)
            ->get()
            ->map(function (QualityCertificate $certificate) {
                $customer = $certificate->request?->customer;

                return [
                    'code' => $certificate->certificate_no,
                    'title' => $customer?->customer_name ?? '-',
                    'subtitle' => $customer?->project_name ?? '',
                    'status' => $certificate->displayStatusMeta()['text'],
                    'date' => optional($certificate->created_at)->format('d/m/Y H:i'),
                    'url' => route('quality-certificates.show', $certificate),
                ];
            });

        return [
            'title' => $role === 'TrungTam' ? 'Phiếu CNCL của trung tâm' : 'Phiếu CNCL gần đây',
            'icon' => 'fas fa-file-signature',
            'empty' => 'Chưa có phiếu CNCL.',
            'items' => $items,
        ];
    }

    private function requestUrlForRole(string $role, CertificateRequest $request): string
    {
        return match ($role) {
            'DVKH' => route('dvkh.requests.show', $request),
            'PTN' => route('ptn.requests.show', $request),
            default => route('certificate-requests.show', $request),
        };
    }

    private function slaAlerts($user)
    {
        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();
        $alerts = collect();

        $waitingRequests = $this->requestScope($user)
            ->with(['distributionCenter', 'customer'])
            ->whereIn('status', ['WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING'])
            ->oldest()
            ->take(50)
            ->get();

        foreach ($waitingRequests as $item) {
            $minutes = Carbon::parse($item->created_at)->diffInMinutes(now());
            $sla = $item->status === 'WAIT_DVKH' ? $slaDvkh : $slaPtn;

            if (!$sla || $minutes < $sla->warning_minutes) {
                continue;
            }

            $item->sla_level = $minutes >= $sla->limit_minutes ? 'danger' : 'warning';
            $item->sla_minutes = $minutes;
            $item->sla_step_name = $item->status === 'WAIT_DVKH' ? 'DVKH kiểm tra' : 'PTN lập phiếu';
            $item->sla_limit_minutes = $sla->limit_minutes;

            $alerts->push($item);
        }

        return $alerts->take(8);
    }

    private function duplicateInvoiceCount(Builder $query): int
    {
        return $query
            ->whereNotNull('invoice_no_normalized')
            ->whereExists(function ($subQuery) {
                $subQuery
                    ->selectRaw(1)
                    ->from('certificate_requests as duplicate_requests')
                    ->whereColumn('duplicate_requests.invoice_no_normalized', 'certificate_requests.invoice_no_normalized')
                    ->whereColumn('duplicate_requests.id', '!=', 'certificate_requests.id')
                    ->whereNull('duplicate_requests.deleted_at');
            })
            ->count();
    }

    private function card(string $label, int $value, string $icon, string $color, string $url): array
    {
        return compact('label', 'value', 'icon', 'color', 'url');
    }

    private function monthExpression(string $column): string
    {
        if (config('database.default') === 'sqlite') {
            return "CAST(strftime('%m', {$column}) AS INTEGER)";
        }

        return "MONTH({$column})";
    }

    private function smartCaPendingTtlMinutes(): int
    {
        return max((int) config('services.smartca.pending_ttl_minutes', 5), 1);
    }
}
