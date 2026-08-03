<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PtnRequestController extends Controller
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
            'WAIT_PTN',
            'PTN_PROCESSING',
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

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('ptn_requests.index', compact('requests'));
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

        return view('ptn_requests.show', compact('certificateRequest'));
    }

    public function receive(CertificateRequest $certificateRequest)
    {
        if ($certificateRequest->status !== 'WAIT_PTN') {
            return redirect()
                ->route('ptn.requests.index')
                ->with('error', 'Chỉ tiếp nhận được yêu cầu đang ở trạng thái Chờ PTN.');
        }

        $oldData = $certificateRequest->toArray();

        $certificateRequest->update([
            'status' => 'PTN_PROCESSING',
        ]);

        ActivityLogger::log(
            'PTN tiếp nhận yêu cầu',
            'receive',
            'PTN tiếp nhận yêu cầu: ' . $certificateRequest->request_no,
            $oldData,
            $certificateRequest->fresh()->toArray()
        );

        return redirect()
            ->route('ptn.requests.show', $certificateRequest)
            ->with('success', 'PTN đã tiếp nhận yêu cầu.');
    }

    public function createCertificate(CertificateRequest $certificateRequest)
    {
        if (!in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING'])) {
            return redirect()
                ->route('ptn.requests.index')
                ->with('error', 'Yêu cầu không ở trạng thái được phép lập phiếu.');
        }

        if (QualityCertificate::where('certificate_request_id', $certificateRequest->id)->exists()) {
            return redirect()
                ->route('ptn.requests.show', $certificateRequest)
                ->with('error', 'Yêu cầu này đã được tạo phiếu CNCL.');
        }

        $certificateRequest->load([
            'details.product.qualityStandard',
            'reissueOfCertificate',
        ]);

        DB::beginTransaction();

        try {
            $certificate = QualityCertificate::create([
                'certificate_no' => $this->generateCertificateNo(),
                'certificate_request_id' => $certificateRequest->id,
                'status' => 'DRAFT',
                'replaces_certificate_id' => $certificateRequest->request_type === 'REISSUE'
                    ? $certificateRequest->reissue_of_certificate_id
                    : null,
                'created_by' => Auth::id(),
                'signed_at' => null,
                'signed_by' => null,
                'pdf_path' => null,
                'print_count' => 0,
            ]);

            foreach ($certificateRequest->details as $detail) {
                $product = $detail->product;

                $certificate->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $detail->quantity,
                    'nominal_size' => $product->nominal_size,
                    'technical_requirements' => $product->technical_requirements,
                    'quality_standard' => $product->qualityStandard?->code,
                ]);
            }

            $oldData = $certificateRequest->toArray();

            $certificateRequest->update([
                'status' => 'PTN_PROCESSING',
            ]);

            if ($certificateRequest->request_type === 'REISSUE' && $certificateRequest->reissueOfCertificate) {
                $certificateRequest->reissueOfCertificate->update([
                    'replaced_by_certificate_id' => $certificate->id,
                ]);
            }

            ActivityLogger::log(
                'PTN lập phiếu',
                'create_certificate',
                'Tạo phiếu CNCL từ yêu cầu: ' . $certificateRequest->request_no,
                $oldData,
                $certificate->load('details')->toArray()
            );

            DB::commit();

            return redirect()
                ->route('quality-certificates.show', $certificate)
                ->with('success', 'Đã tạo phiếu CNCL thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('ptn.requests.show', $certificateRequest)
                ->with('error', 'Có lỗi khi tạo phiếu CNCL: ' . $e->getMessage());
        }
    }

    private function generateCertificateNo(): string
    {
        $prefix = 'CNCL-' . date('Ymd') . '-';

        $count = QualityCertificate::whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
