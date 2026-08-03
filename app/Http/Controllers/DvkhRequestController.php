<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DvkhRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('dvkh_requests.index', compact('requests', 'centers'));
    }

    public function show(CertificateRequest $certificateRequest)
    {
        $certificateRequest->load([
            'distributionCenter',
            'customer',
            'details.product.group',
            'details.product.qualityStandard',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
        ]);

        return view('dvkh_requests.show', compact('certificateRequest'));
    }

    public function approve(Request $request, CertificateRequest $certificateRequest)
    {
        if ($certificateRequest->status !== 'WAIT_DVKH') {
            return redirect()
                ->route('dvkh.requests.index')
                ->with('error', 'Chỉ xác nhận được yêu cầu đang ở trạng thái Chờ DVKH.');
        }

        DB::beginTransaction();

        try {
            $certificateRequest->load('reissueOfCertificate');
            $oldData = $certificateRequest->toArray();

            if ($certificateRequest->request_type === 'REISSUE') {
                $oldCertificate = $certificateRequest->reissueOfCertificate;

                if (!$oldCertificate || !$oldCertificate->canRequestReissue()) {
                    DB::rollBack();

                    return redirect()
                        ->route('dvkh.requests.index')
                        ->with('error', 'Phiếu cũ của yêu cầu cấp lại không còn ở trạng thái có thể hủy/cấp lại.');
                }

                $oldCertificate->update([
                    'status' => 'REVOKED',
                    'revoked_at' => now(),
                    'revoked_by' => Auth::id(),
                    'revoked_reason' => $certificateRequest->reissue_reason,
                ]);
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
            $certificateRequest->fresh()->toArray()
        );

        return redirect()
            ->route('dvkh.requests.index')
            ->with('success', 'Đã xác nhận yêu cầu và chuyển sang PTN xử lý.');
    }

    public function reject(Request $request, CertificateRequest $certificateRequest)
    {
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
            $certificateRequest->fresh()->toArray()
        );

        return redirect()
            ->route('dvkh.requests.index')
            ->with('success', 'Đã trả lại yêu cầu.');
    }
}
