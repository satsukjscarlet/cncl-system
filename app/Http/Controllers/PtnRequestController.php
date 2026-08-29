<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\QualityCertificate;
use App\Models\SlaConfig;
use App\Models\UrgentReason;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PtnRequestController extends Controller
{
    public function index(Request $request)
    {
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();
        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
            'qualityCertificate',
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

        if ($request->filled('distribution_center_id')) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        $statusFilter = $request->has('status') ? $request->input('status') : 'WAIT_PTN';

        if ($statusFilter !== null && $statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        if ($request->filled('urgent')) {
            $query->where('is_urgent', $request->urgent);
        }

        if ($request->filled('sla')) {
            $this->applySlaFilter($query, $request->sla, $slaPtn);
        }

        $requests = $query
            ->orderByRaw("CASE WHEN status = 'WAIT_PTN' THEN 0 ELSE 1 END")
            ->orderByDesc('is_urgent')
            ->orderBy('created_at')
            ->paginate(15)
            ->withQueryString();

        $this->attachSlaMeta($requests->getCollection(), $slaPtn);

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        $metrics = $this->metrics($request, $slaPtn);

        return view('ptn_requests.index', compact('requests', 'centers', 'metrics', 'statusFilter'));
    }

    public function directCreate()
    {
        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedCustomers = $this->selectedCustomersForForm();
        $selectedProducts = $this->selectedProductsForForm();
        $urgentReasons = UrgentReason::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('ptn_requests.direct_create', compact('centers', 'selectedCustomers', 'selectedProducts', 'urgentReasons'));
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
                ],
                $certificate,
                ['request_id' => $certificateRequest->id, 'request_no' => $certificateRequest->request_no]
            );

            DB::commit();

            app(NotificationService::class)->notifyCertificateCreated(
                $certificate->fresh(['request.distributionCenter', 'request.customer'])
            );

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
            'reissueCertificates',
            'qualityCertificate',
        ]);

        return view('ptn_requests.show', compact('certificateRequest'));
    }

    public function receive(CertificateRequest $certificateRequest)
    {
        $this->authorizePtnRequest($certificateRequest);

        return redirect()
            ->route('ptn.requests.show', $certificateRequest)
            ->with('error', 'Luồng hiện tại không dùng bước trung gian riêng. Vui lòng bấm Lập phiếu CNCL để tạo phiếu từ yêu cầu này.');
    }

    public function receiveAndCreateCertificate(CertificateRequest $certificateRequest)
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
            'reissueCertificates',
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

            $this->markReissueCertificatesReplaced($certificateRequest, $certificate);

            ActivityLogger::log(
                'PTN lập phiếu',
                'create_certificate_from_request',
                'PTN lập phiếu CNCL từ yêu cầu: ' . $certificateRequest->request_no,
                $oldData,
                $certificate->load('details')->toArray(),
                $certificate,
                ['request_id' => $certificateRequest->id, 'request_no' => $certificateRequest->request_no]
            );

            DB::commit();

            app(NotificationService::class)->notifyCertificateCreated(
                $certificate->fresh(['request.distributionCenter', 'request.customer'])
            );

            return redirect()
                ->route('quality-certificates.show', $certificate)
                ->with('success', 'Đã lập phiếu CNCL thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('ptn.requests.show', $certificateRequest)
                ->with('error', 'Có lỗi khi lập phiếu CNCL: ' . $e->getMessage());
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
            'reissueCertificates',
        ]);

        DB::beginTransaction();

        try {
            $certificate = $this->createQualityCertificateFromRequest($certificateRequest);

            $oldData = $certificateRequest->toArray();

            $certificateRequest->update([
                'status' => 'PTN_PROCESSING',
            ]);

            $this->markReissueCertificatesReplaced($certificateRequest, $certificate);

            ActivityLogger::log(
                'PTN lập phiếu',
                'create_certificate',
                'Tạo phiếu CNCL từ yêu cầu: ' . $certificateRequest->request_no,
                $oldData,
                $certificate->load('details')->toArray(),
                $certificate,
                ['request_id' => $certificateRequest->id, 'request_no' => $certificateRequest->request_no]
            );

            DB::commit();

            app(NotificationService::class)->notifyCertificateCreated(
                $certificate->fresh(['request.distributionCenter', 'request.customer'])
            );

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
            'reissueCertificates',
        ]);

        $certificate = QualityCertificate::create([
            'certificate_no' => $this->generateCertificateNo(),
            'certificate_request_id' => $certificateRequest->id,
            'status' => 'WAIT_PTN_MANAGER_APPROVAL',
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

    private function markReissueCertificatesReplaced(CertificateRequest $certificateRequest, QualityCertificate $newCertificate): void
    {
        if ($certificateRequest->request_type !== 'REISSUE') {
            return;
        }

        $oldCertificates = $certificateRequest->reissueCertificates;

        if ($oldCertificates->isEmpty() && $certificateRequest->reissueOfCertificate) {
            $oldCertificates = collect([$certificateRequest->reissueOfCertificate]);
        }

        foreach ($oldCertificates as $oldCertificate) {
            $oldCertificate->update([
                'replaced_by_certificate_id' => $newCertificate->id,
            ]);
        }
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
            $customer->toArray(),
            $customer
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
            ->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING'])
            ->when($request->filled('distribution_center_id'), function ($query) use ($request) {
                $query->where('distribution_center_id', $request->distribution_center_id);
            });

        return [
            'waiting' => (clone $base)->where('status', 'WAIT_PTN')->count(),
            'urgent' => (clone $base)->where('status', 'WAIT_PTN')->where('is_urgent', true)->count(),
            'warning' => $this->slaCount(clone $base, $sla, 'warning'),
            'overdue' => $this->slaCount(clone $base, $sla, 'overdue'),
            'created_today' => (clone $base)
                ->where('status', 'PTN_PROCESSING')
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'processing' => (clone $base)->where('status', 'PTN_PROCESSING')->count(),
        ];
    }

    private function slaCount($query, ?SlaConfig $sla, string $level): int
    {
        if (!$sla) {
            return 0;
        }

        $query->where('status', 'WAIT_PTN');
        $this->applySlaFilter($query, $level, $sla);

        return $query->count();
    }

    private function applySlaFilter($query, string $mode, ?SlaConfig $sla): void
    {
        if (!$sla) {
            return;
        }

        $limitAt = now()->subMinutes((int) $sla->limit_minutes);
        $warningAt = now()->subMinutes((int) $sla->warning_minutes);

        if ($mode === 'overdue') {
            $query->where('status', 'WAIT_PTN')
                ->where('created_at', '<=', $limitAt);

            return;
        }

        if ($mode === 'warning') {
            $query->where('status', 'WAIT_PTN')
                ->where('created_at', '<=', $warningAt)
                ->where('created_at', '>', $limitAt);

            return;
        }

        if ($mode === 'normal') {
            $query->where(function ($q) use ($warningAt) {
                $q->where('status', '!=', 'WAIT_PTN')
                    ->orWhere('created_at', '>', $warningAt);
            });
        }
    }

    private function slaLevel(CertificateRequest $item, ?SlaConfig $sla): ?string
    {
        if (!$sla || $item->status !== 'WAIT_PTN' || !$item->created_at) {
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

    private function selectedProductsForForm(): \Illuminate\Support\Collection
    {
        $productIds = collect(old('product_id', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::with('qualityStandard')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');
    }

    private function selectedCustomersForForm(): \Illuminate\Support\Collection
    {
        $customerIds = collect([old('customer_id')])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($customerIds->isEmpty()) {
            return collect();
        }

        $centerId = old('distribution_center_id');

        return Customer::whereIn('id', $customerIds)
            ->when($centerId, function ($query) use ($centerId) {
                $query->where('distribution_center_id', $centerId);
            })
            ->get()
            ->keyBy('id');
    }
}
