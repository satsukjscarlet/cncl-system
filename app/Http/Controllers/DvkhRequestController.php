<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\SlaConfig;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DvkhRequestController extends Controller
{
    public function index(Request $request)
    {
        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
            'reissueCertificates',
            'qualityCertificate',
        ])->whereIn('status', [
            'WAIT_DVKH',
            'CANCELLED',
            'WAIT_PTN',
        ]);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('request_no', 'like', '%' . $request->keyword . '%')
                    ->orWhere('invoice_no', 'like', '%' . $request->keyword . '%')
                    ->orWhereHas('customer', function ($c) use ($request) {
                        $c->where('customer_name', 'like', '%' . $request->keyword . '%')
                            ->orWhere('project_name', 'like', '%' . $request->keyword . '%');
                    });
            });
        }

        if ($request->filled('distribution_center_id')) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        $statusFilter = $request->has('status') ? $request->input('status') : 'WAIT_DVKH';

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('duplicate_invoice')) {
            $this->applyDuplicateInvoiceFilter($query, $request->duplicate_invoice);
        }

        if ($request->filled('urgent')) {
            $query->where('is_urgent', $request->urgent);
        }

        if ($request->filled('sla')) {
            $this->applySlaFilter($query, $request->sla, $slaDvkh);
        }

        $requests = $query
            ->orderByRaw("CASE WHEN status = 'WAIT_DVKH' THEN 0 WHEN status = 'WAIT_PTN' THEN 1 ELSE 2 END")
            ->orderByDesc('is_urgent')
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        $this->attachInvoiceDuplicateCounts($requests->getCollection());
        $this->attachSlaMeta($requests->getCollection(), $slaDvkh);

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        $metrics = $this->metrics($request, $slaDvkh);

        return view('dvkh_requests.index', compact('requests', 'centers', 'metrics', 'statusFilter'));
    }

    public function show(CertificateRequest $certificateRequest)
    {
        $this->authorizeDvkhRequest($certificateRequest);

        $certificateRequest->load([
            'distributionCenter',
            'customer',
            'details.product.group',
            'details.product.qualityStandard',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
            'reissueCertificates',
            'qualityCertificate',
        ]);

        $invoiceDuplicates = $this->invoiceDuplicates($certificateRequest);
        $requestWorkflowSteps = $this->requestWorkflowSteps($certificateRequest);

        return view('dvkh_requests.show', compact('certificateRequest', 'invoiceDuplicates', 'requestWorkflowSteps'));
    }

    public function approve(Request $request, CertificateRequest $certificateRequest)
    {
        $this->authorizeDvkhRequest($certificateRequest);

        if ($certificateRequest->status !== 'WAIT_DVKH') {
            return redirect()
                ->route('dvkh.requests.index')
                ->with('error', 'Chỉ xác nhận được yêu cầu đang ở trạng thái Chờ DVKH.');
        }

        DB::beginTransaction();

        try {
            $certificateRequest->load(['reissueOfCertificate', 'reissueCertificates']);
            $oldData = $certificateRequest->toArray();

            if ($certificateRequest->request_type === 'REISSUE') {
                $oldCertificates = $certificateRequest->reissueCertificates;

                if ($oldCertificates->isEmpty() && $certificateRequest->reissueOfCertificate) {
                    $oldCertificates = collect([$certificateRequest->reissueOfCertificate]);
                }

                if ($oldCertificates->isEmpty() || $oldCertificates->contains(fn ($certificate) => !$certificate->canRequestReissue())) {
                    DB::rollBack();

                    return redirect()
                        ->route('dvkh.requests.index')
                        ->with('error', 'Phiếu cũ của yêu cầu cấp lại không còn ở trạng thái có thể hủy/cấp lại.');
                }

                foreach ($oldCertificates as $oldCertificate) {
                    $oldCertificate->update([
                        'status' => 'REVOKED',
                        'revoked_at' => now(),
                        'revoked_by' => Auth::id(),
                        'revoked_reason' => $certificateRequest->reissue_reason,
                    ]);
                }
            }

            $certificateRequest->update([
                'status' => 'WAIT_PTN',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('dvkh.requests.index')
                ->with('error', 'Không thể xác nhận yêu cầu: ' . $e->getMessage());
        }

        ActivityLogger::log(
            'DVKH kiểm tra yêu cầu',
            'approve',
            'DVKH xác nhận yêu cầu: ' . $certificateRequest->request_no,
            $oldData,
            $certificateRequest->fresh()->toArray(),
            $certificateRequest
        );

        app(NotificationService::class)->notifyRequestApproved(
            $certificateRequest->fresh(['distributionCenter', 'customer'])
        );

        return redirect()
            ->route('dvkh.requests.index')
            ->with('success', 'Đã xác nhận yêu cầu và chuyển sang PTN xử lý.');
    }

    public function reject(Request $request, CertificateRequest $certificateRequest)
    {
        $this->authorizeDvkhRequest($certificateRequest);

        if ($certificateRequest->status !== 'WAIT_DVKH') {
            return redirect()
                ->route('dvkh.requests.index')
                ->with('error', 'Chỉ trả lại được yêu cầu đang ở trạng thái Chờ DVKH.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $oldData = $certificateRequest->toArray();

        $certificateRequest->update([
            'status' => 'CANCELLED',
            'note' => trim(($certificateRequest->note ? $certificateRequest->note . "\n" : '') . '[DVKH trả lại]: ' . $data['reason']),
        ]);

        ActivityLogger::log(
            'DVKH kiểm tra yêu cầu',
            'reject',
            'DVKH trả lại yêu cầu: ' . $certificateRequest->request_no . '. Lý do: ' . $data['reason'],
            $oldData,
            $certificateRequest->fresh()->toArray(),
            $certificateRequest
        );

        app(NotificationService::class)->notifyRequestRejected(
            $certificateRequest->fresh(['distributionCenter', 'customer'])
        );

        return redirect()
            ->route('dvkh.requests.index')
            ->with('success', 'Đã trả lại yêu cầu.');
    }

    private function attachInvoiceDuplicateCounts($requests): void
    {
        $normalizedInvoices = $requests
            ->pluck('invoice_no_normalized')
            ->filter()
            ->unique()
            ->values();

        if ($normalizedInvoices->isEmpty()) {
            return;
        }

        $counts = CertificateRequest::query()
            ->select('invoice_no_normalized', DB::raw('COUNT(*) as total'))
            ->whereIn('invoice_no_normalized', $normalizedInvoices)
            ->groupBy('invoice_no_normalized')
            ->pluck('total', 'invoice_no_normalized');

        $requests->each(function (CertificateRequest $item) use ($counts) {
            $total = (int) ($counts[$item->invoice_no_normalized] ?? 0);
            $item->setAttribute('invoice_duplicate_count', max(0, $total - 1));
        });
    }

    private function attachSlaMeta($requests, ?SlaConfig $sla): void
    {
        $requests->each(function (CertificateRequest $item) use ($sla) {
            $item->setAttribute('sla_level', $this->slaLevel($item, $sla));
            $item->setAttribute('sla_elapsed_minutes', $item->created_at ? $item->created_at->diffInMinutes(now()) : null);
        });
    }

    private function metrics(Request $request, ?SlaConfig $sla): array
    {
        $base = CertificateRequest::query()
            ->when($request->filled('distribution_center_id'), function ($query) use ($request) {
                $query->where('distribution_center_id', $request->distribution_center_id);
            });

        return [
            'waiting' => (clone $base)->where('status', 'WAIT_DVKH')->count(),
            'urgent' => (clone $base)->where('status', 'WAIT_DVKH')->where('is_urgent', true)->count(),
            'duplicate' => tap(clone $base, function ($query) {
                $query->where('status', 'WAIT_DVKH');
                $this->applyDuplicateInvoiceFilter($query, '1');
            })->count(),
            'warning' => $this->slaCount(clone $base, $sla, 'warning'),
            'overdue' => $this->slaCount(clone $base, $sla, 'overdue'),
            'transferred_today' => (clone $base)
                ->where('status', 'WAIT_PTN')
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
        ];
    }

    private function slaCount($query, ?SlaConfig $sla, string $level): int
    {
        if (!$sla) {
            return 0;
        }

        $query->where('status', 'WAIT_DVKH');
        $this->applySlaFilter($query, $level, $sla);

        return $query->count();
    }

    private function applyDuplicateInvoiceFilter($query, string $mode): void
    {
        if ($mode === '1') {
            $query
                ->whereNotNull('invoice_no_normalized')
                ->whereExists(function ($subQuery) {
                    $this->duplicateInvoiceExistsSubQuery($subQuery);
                });

            return;
        }

        if ($mode === '0') {
            $query->where(function ($q) {
                $q->whereNull('invoice_no_normalized')
                    ->orWhereNotExists(function ($subQuery) {
                        $this->duplicateInvoiceExistsSubQuery($subQuery);
                    });
            });
        }
    }

    private function applySlaFilter($query, string $mode, ?SlaConfig $sla): void
    {
        if (!$sla) {
            return;
        }

        $limitAt = now()->subMinutes((int) $sla->limit_minutes);
        $warningAt = now()->subMinutes((int) $sla->warning_minutes);

        if ($mode === 'overdue') {
            $query->where('status', 'WAIT_DVKH')
                ->where('created_at', '<=', $limitAt);

            return;
        }

        if ($mode === 'warning') {
            $query->where('status', 'WAIT_DVKH')
                ->where('created_at', '<=', $warningAt)
                ->where('created_at', '>', $limitAt);

            return;
        }

        if ($mode === 'normal') {
            $query->where(function ($q) use ($warningAt) {
                $q->where('status', '!=', 'WAIT_DVKH')
                    ->orWhere('created_at', '>', $warningAt);
            });
        }
    }

    private function slaLevel(CertificateRequest $item, ?SlaConfig $sla): ?string
    {
        if (!$sla || $item->status !== 'WAIT_DVKH' || !$item->created_at) {
            return null;
        }

        $minutes = $item->created_at->diffInMinutes(now());

        if ($minutes >= $sla->limit_minutes) {
            return 'overdue';
        }

        if ($minutes >= $sla->warning_minutes) {
            return 'warning';
        }

        return 'normal';
    }

    private function duplicateInvoiceExistsSubQuery($subQuery): void
    {
        $subQuery
            ->select(DB::raw(1))
            ->from('certificate_requests as duplicate_requests')
            ->whereColumn('duplicate_requests.invoice_no_normalized', 'certificate_requests.invoice_no_normalized')
            ->whereColumn('duplicate_requests.id', '!=', 'certificate_requests.id')
            ->whereNull('duplicate_requests.deleted_at');
    }

    private function invoiceDuplicates(CertificateRequest $certificateRequest)
    {
        if (!$certificateRequest->invoice_no) {
            return collect();
        }

        return CertificateRequest::duplicateInvoiceQuery(
            $certificateRequest->invoice_no,
            $certificateRequest->id
        )
            ->latest()
            ->limit(10)
            ->get();
    }

    private function authorizeDvkhRequest(CertificateRequest $certificateRequest): void
    {
        if (!in_array($certificateRequest->status, ['WAIT_DVKH', 'WAIT_PTN', 'CANCELLED'])) {
            abort(403, 'Yêu cầu này không thuộc màn xử lý của DVKH.');
        }
    }

    private function requestWorkflowSteps(CertificateRequest $certificateRequest): array
    {
        $certificate = $certificateRequest->qualityCertificate;

        $steps = [
            [
                'title' => 'Trung tâm gửi yêu cầu',
                'icon' => 'fas fa-paper-plane',
                'status' => 'done',
                'description' => 'Trung tâm đã tạo và gửi yêu cầu cấp phiếu.',
                'time' => $certificateRequest->created_at,
            ],
            [
                'title' => 'DVKH kiểm tra',
                'icon' => 'fas fa-check-circle',
                'status' => 'pending',
                'description' => 'DVKH kiểm tra thông tin khách hàng, hóa đơn và danh sách sản phẩm.',
                'time' => null,
            ],
            [
                'title' => 'PTN lập phiếu',
                'icon' => 'fas fa-vials',
                'status' => 'pending',
                'description' => 'PTN lập phiếu CNCL sau khi DVKH xác nhận.',
                'time' => null,
            ],
            [
                'title' => 'Trưởng PTN ký số',
                'icon' => 'fas fa-pen-nib',
                'status' => 'pending',
                'description' => 'Trưởng PTN kiểm tra phiếu và gửi ký VNPT SmartCA.',
                'time' => null,
            ],
        ];

        if ($certificateRequest->status === 'WAIT_DVKH') {
            $steps[1]['status'] = 'current';
            $steps[1]['description'] = 'Đang chờ DVKH xác nhận hoặc trả lại yêu cầu.';
        }

        if ($certificateRequest->status === 'CANCELLED') {
            $steps[1]['status'] = 'danger';
            $steps[1]['description'] = 'DVKH đã trả lại/hủy yêu cầu. Xem ghi chú để biết lý do.';
            $steps[1]['time'] = $certificateRequest->updated_at;
        }

        if (in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING', 'COMPLETED'], true) || $certificate) {
            $steps[1]['status'] = 'done';
            $steps[1]['description'] = 'DVKH đã xác nhận và chuyển yêu cầu sang PTN.';
            $steps[1]['time'] = $certificateRequest->updated_at;

            $steps[2]['status'] = $certificate ? 'done' : 'current';
            $steps[2]['description'] = $certificate
                ? 'PTN đã lập phiếu ' . $certificate->certificate_no . '.'
                : 'Đang chờ PTN lập phiếu CNCL.';
            $steps[2]['time'] = $certificate?->created_at;
        }

        if ($certificate) {
            $steps[3]['status'] = $certificate->signed_at ? 'done' : 'current';
            $steps[3]['description'] = $certificate->signed_at
                ? 'Phiếu đã được ký số/phát hành.'
                : 'Phiếu đã lập, đang chờ Trưởng PTN ký số.';
            $steps[3]['time'] = $certificate->signed_at;
        }

        return $steps;
    }
}
