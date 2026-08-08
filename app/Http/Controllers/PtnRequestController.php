<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\QualityCertificate;
use App\Models\UrgentReason;
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

    public function directCreate()
    {
        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('is_active', true)
            ->orderBy('customer_name')
            ->get();

        $products = Product::with(['group', 'qualityStandard'])
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get();

        $urgentReasons = UrgentReason::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('ptn_requests.direct_create', compact('centers', 'customers', 'products', 'urgentReasons'));
    }

    public function directStore(Request $request)
    {
        $data = $request->validate([
            'distribution_center_id' => ['required', 'exists:distribution_centers,id'],
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'new_customer_name' => ['required_if:customer_mode,new', 'nullable', 'string', 'max:500'],
            'new_customer_address' => ['nullable', 'string'],
            'new_tax_code' => ['nullable', 'string', 'max:100'],
            'new_contact_person' => ['nullable', 'string', 'max:255'],
            'new_phone' => ['nullable', 'string', 'max:100'],
            'new_email' => ['nullable', 'email', 'max:255'],
            'new_project_name' => ['nullable', 'string', 'max:500'],
            'new_project_address' => ['nullable', 'string'],
            'delivery_date' => ['nullable', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'require_hard_copy' => ['nullable'],
            'hard_copy_quantity' => ['nullable', 'integer', 'min:0'],
            'is_urgent' => ['nullable', 'boolean'],
            'urgent_reason_id' => ['nullable', 'required_if:is_urgent,1', 'exists:urgent_reasons,id'],
            'requester_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['required', 'numeric', 'min:0.01'],
        ]);

        DB::beginTransaction();

        try {
            $customerId = $this->resolveCustomerId($data);

            $certificateRequest = CertificateRequest::create([
                'request_no' => $this->generateDirectRequestNo(),
                'request_type' => 'DIRECT_PTN',
                'distribution_center_id' => $data['distribution_center_id'],
                'customer_id' => $customerId,
                'delivery_date' => $data['delivery_date'] ?? null,
                'invoice_no' => $data['invoice_no'] ?? null,
                'require_hard_copy' => $request->boolean('require_hard_copy'),
                'hard_copy_quantity' => $request->boolean('require_hard_copy')
                    ? ($data['hard_copy_quantity'] ?? 0)
                    : 0,
                'is_urgent' => $request->boolean('is_urgent'),
                'urgent_reason_id' => $request->boolean('is_urgent')
                    ? ($data['urgent_reason_id'] ?? null)
                    : null,
                'requester_name' => $data['requester_name'] ?? null,
                'note' => trim(($data['note'] ?? '') . "\n[PTN lập trực tiếp]"),
                'status' => 'PTN_PROCESSING',
                'created_by' => Auth::id(),
            ]);

            foreach ($data['product_id'] as $index => $productId) {
                $certificateRequest->details()->create([
                    'product_id' => $productId,
                    'quantity' => $data['quantity'][$index],
                ]);
            }

            $this->logDuplicateInvoiceWarning($certificateRequest);

            $certificate = $this->createQualityCertificateFromRequest($certificateRequest);

            ActivityLogger::log(
                'PTN lập phiếu trực tiếp',
                'direct_create_certificate',
                'PTN lập trực tiếp phiếu CNCL: ' . $certificate->certificate_no . ' từ yêu cầu nền: ' . $certificateRequest->request_no,
                null,
                [
                    'request' => $certificateRequest->fresh()->load('details')->toArray(),
                    'certificate' => $certificate->load('details')->toArray(),
                ]
            );

            DB::commit();

            return redirect()
                ->route('quality-certificates.show', $certificate)
                ->with('success', 'Đã lập trực tiếp phiếu CNCL. Bạn có thể kiểm tra PDF, ký số và gửi email cho khách hàng.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi khi PTN lập phiếu trực tiếp: ' . $e->getMessage());
        }
    }

    public function show(CertificateRequest $certificateRequest)
    {
        $this->authorizePtnRequest($certificateRequest);

        $certificateRequest->load([
            'distributionCenter',
            'customer',
            'details.product.group',
            'details.product.qualityStandard',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
            'qualityCertificate',
        ]);

        return view('ptn_requests.show', compact('certificateRequest'));
    }

    public function receive(CertificateRequest $certificateRequest)
    {
        $this->authorizePtnRequest($certificateRequest);

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

    public function receiveAndCreateCertificate(CertificateRequest $certificateRequest)
    {
        $this->authorizePtnRequest($certificateRequest);

        if (!in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING'])) {
            return redirect()
                ->route('ptn.requests.index')
                ->with('error', 'Yêu cầu không ở trạng thái được phép tiếp nhận và lập phiếu.');
        }

        if ($this->hasActiveQualityCertificate($certificateRequest)) {
            return redirect()
                ->route('ptn.requests.show', $certificateRequest)
                ->with('error', 'Yêu cầu này đã có phiếu CNCL đang hiệu lực.');
        }

        $certificateRequest->load([
            'details.product.qualityStandard',
            'reissueOfCertificate',
        ]);

        DB::beginTransaction();

        try {
            $oldData = $certificateRequest->toArray();

            if ($certificateRequest->status === 'WAIT_PTN') {
                $certificateRequest->update([
                    'status' => 'PTN_PROCESSING',
                ]);
            }

            $certificate = $this->createQualityCertificateFromRequest($certificateRequest->fresh());

            if ($certificateRequest->request_type === 'REISSUE' && $certificateRequest->reissueOfCertificate) {
                $certificateRequest->reissueOfCertificate->update([
                    'replaced_by_certificate_id' => $certificate->id,
                ]);
            }

            ActivityLogger::log(
                'PTN tiếp nhận và lập phiếu',
                'receive_and_create_certificate',
                'PTN tiếp nhận và lập phiếu CNCL từ yêu cầu: ' . $certificateRequest->request_no,
                $oldData,
                $certificate->load('details')->toArray()
            );

            DB::commit();

            return redirect()
                ->route('quality-certificates.show', $certificate)
                ->with('success', 'Đã tiếp nhận yêu cầu và lập phiếu CNCL thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('ptn.requests.show', $certificateRequest)
                ->with('error', 'Có lỗi khi tiếp nhận và lập phiếu CNCL: ' . $e->getMessage());
        }
    }

    public function createCertificate(CertificateRequest $certificateRequest)
    {
        $this->authorizePtnRequest($certificateRequest);

        if (!in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING'])) {
            return redirect()
                ->route('ptn.requests.index')
                ->with('error', 'Yêu cầu không ở trạng thái được phép lập phiếu.');
        }

        if ($this->hasActiveQualityCertificate($certificateRequest)) {
            return redirect()
                ->route('ptn.requests.show', $certificateRequest)
                ->with('error', 'Yêu cầu này đã có phiếu CNCL đang hiệu lực.');
        }

        $certificateRequest->load([
            'details.product.qualityStandard',
            'reissueOfCertificate',
        ]);

        DB::beginTransaction();

        try {
            $certificate = $this->createQualityCertificateFromRequest($certificateRequest);

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

    private function createQualityCertificateFromRequest(CertificateRequest $certificateRequest): QualityCertificate
    {
        $certificateRequest->loadMissing([
            'details.product.qualityStandard',
            'reissueOfCertificate',
        ]);

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

        return $certificate;
    }

    private function hasActiveQualityCertificate(CertificateRequest $certificateRequest): bool
    {
        return QualityCertificate::where('certificate_request_id', $certificateRequest->id)
            ->where('status', '!=', 'REJECTED')
            ->exists();
    }

    private function generateDirectRequestNo(): string
    {
        $prefix = 'PTN-' . date('Ymd') . '-';

        $count = CertificateRequest::withTrashed()
                ->where('request_no', 'like', $prefix . '%')
                ->count() + 1;

        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function resolveCustomerId(array $data): int
    {
        if (($data['customer_mode'] ?? 'existing') === 'existing') {
            $customer = Customer::findOrFail($data['customer_id']);

            if (
                $customer->distribution_center_id
                && (int) $customer->distribution_center_id !== (int) ($data['distribution_center_id'] ?? 0)
            ) {
                abort(403, 'Khách hàng đã chọn không thuộc trung tâm đang lập phiếu.');
            }

            return (int) $customer->id;
        }

        $customer = Customer::create([
            'distribution_center_id' => $data['distribution_center_id'] ?? null,
            'customer_code' => $this->generateCustomerCode(),
            'customer_name' => $data['new_customer_name'],
            'customer_address' => $data['new_customer_address'] ?? null,
            'tax_code' => $data['new_tax_code'] ?? null,
            'contact_person' => $data['new_contact_person'] ?? null,
            'phone' => $data['new_phone'] ?? null,
            'email' => $data['new_email'] ?? null,
            'project_name' => $data['new_project_name'] ?? null,
            'project_address' => $data['new_project_address'] ?? null,
            'is_active' => true,
        ]);

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'create_from_ptn_direct',
            'Tạo khách hàng từ luồng PTN lập phiếu trực tiếp: ' . $customer->customer_name,
            null,
            $customer->toArray()
        );

        return $customer->id;
    }

    private function generateCustomerCode(): string
    {
        $prefix = 'KH-' . date('Ymd') . '-';
        $count = Customer::withTrashed()
                ->where('customer_code', 'like', $prefix . '%')
                ->count() + 1;

        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function authorizePtnRequest(CertificateRequest $certificateRequest): void
    {
        if (!in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING'])) {
            abort(403, 'Yeu cau nay khong thuoc man xu ly cua PTN.');
        }
    }

    private function logDuplicateInvoiceWarning(CertificateRequest $certificateRequest): void
    {
        if (!$certificateRequest->invoice_no) {
            return;
        }

        $duplicateCount = CertificateRequest::duplicateInvoiceQuery(
            $certificateRequest->invoice_no,
            $certificateRequest->id
        )->count();

        if ($duplicateCount < 1) {
            return;
        }

        ActivityLogger::log(
            'PTN lập phiếu trực tiếp',
            'duplicate_invoice_warning',
            'Cảnh báo số hóa đơn trùng khi PTN lập trực tiếp yêu cầu ' . $certificateRequest->request_no . ': ' . $certificateRequest->invoice_no . ' (' . $duplicateCount . ' bản ghi trùng)'
        );
    }
}
