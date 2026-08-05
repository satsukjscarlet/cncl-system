<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\UrgentReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CertificateRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
        ]);

        if (Auth::user()->hasRole('TrungTam')) {
            $query->where('distribution_center_id', Auth::user()->distribution_center_id);
        }

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

        if ($request->filled('distribution_center_id') && !Auth::user()->hasRole('TrungTam')) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certificate_requests.index', compact('requests', 'centers'));
    }

    public function create()
    {
        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = Customer::where('is_active', true)
            ->when(Auth::user()->hasRole('TrungTam'), function ($query) {
                $query->where('distribution_center_id', Auth::user()->distribution_center_id);
            })
            ->orderBy('customer_name')
            ->get();

        $products = Product::with(['group', 'qualityStandard'])
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get();

        $urgentReasons = UrgentReason::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certificate_requests.create', compact('centers', 'customers', 'products', 'urgentReasons'));
    }

    public function checkInvoice(Request $request)
    {
        $data = $request->validate([
            'invoice_no' => ['nullable', 'string', 'max:255'],
            'exclude_id' => ['nullable', 'integer'],
        ]);

        $items = CertificateRequest::duplicateInvoiceQuery(
            $data['invoice_no'] ?? null,
            $data['exclude_id'] ?? null
        )
            ->latest()
            ->limit(10)
            ->get()
            ->map(function (CertificateRequest $item) {
                return [
                    'id' => $item->id,
                    'request_no' => $item->request_no,
                    'invoice_no' => $item->invoice_no,
                    'customer_name' => $item->customer->customer_name ?? '-',
                    'project_name' => $item->customer->project_name ?? '',
                    'distribution_center' => $item->distributionCenter->name ?? '-',
                    'status' => $item->status,
                    'created_at' => optional($item->created_at)->format('d/m/Y H:i'),
                    'certificate_no' => $item->qualityCertificate->certificate_no ?? null,
                    'url' => route('certificate-requests.show', $item),
                ];
            });

        return response()->json([
            'duplicated' => $items->isNotEmpty(),
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
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
        ];

        if (!Auth::user()->hasRole('TrungTam')) {
            $rules['distribution_center_id'] = ['required', 'exists:distribution_centers,id'];
        }

        $data = $request->validate($rules);

        $distributionCenterId = Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : $data['distribution_center_id'];

        if (!$distributionCenterId) {
            return back()
                ->withInput()
                ->with('error', 'Tài khoản Trung tâm chưa được gán Trung tâm phân phối.');
        }

        DB::beginTransaction();

        try {
            $customerId = $this->resolveCustomerId($data);

            $certificateRequest = CertificateRequest::create([
                'request_no' => $this->generateRequestNo(),
                'distribution_center_id' => $distributionCenterId,
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
                'note' => $data['note'] ?? null,
                'status' => 'WAIT_DVKH',
                'created_by' => Auth::id(),
            ]);

            foreach ($data['product_id'] as $index => $productId) {
                $certificateRequest->details()->create([
                    'product_id' => $productId,
                    'quantity' => $data['quantity'][$index],
                ]);
            }

            $this->logDuplicateInvoiceWarning($certificateRequest);

            ActivityLogger::log(
                'Yêu cầu cấp phiếu',
                'create',
                'Tạo yêu cầu cấp phiếu: ' . $certificateRequest->request_no,
                null,
                $certificateRequest->load('details')->toArray()
            );

            DB::commit();

            return redirect()
                ->route('certificate-requests.index')
                ->with('success', 'Tạo yêu cầu cấp phiếu thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi khi tạo yêu cầu: ' . $e->getMessage());
        }
    }

    public function show(CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        $certificateRequest->load([
            'distributionCenter',
            'customer',
            'details.product.group',
            'details.product.qualityStandard',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
        ]);

        $invoiceDuplicates = $this->invoiceDuplicates($certificateRequest);

        return view('certificate_requests.show', compact('certificateRequest', 'invoiceDuplicates'));
    }

    public function edit(CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if (!in_array($certificateRequest->status, ['DRAFT', 'WAIT_DVKH'])) {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chỉ được sửa yêu cầu ở trạng thái Nháp hoặc Chờ DVKH.');
        }

        $certificateRequest->load('details');

        $centers = DistributionCenter::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)
            ->when(Auth::user()->hasRole('TrungTam'), function ($query) {
                $query->where('distribution_center_id', Auth::user()->distribution_center_id);
            })
            ->orderBy('customer_name')
            ->get();
        $products = Product::with(['group', 'qualityStandard'])
            ->where('is_active', true)
            ->orderBy('product_name')
            ->get();
        $urgentReasons = UrgentReason::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certificate_requests.edit', compact(
            'certificateRequest',
            'centers',
            'customers',
            'products',
            'urgentReasons'
        ));
    }

    public function update(Request $request, CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if (!in_array($certificateRequest->status, ['DRAFT', 'WAIT_DVKH'])) {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chỉ được sửa yêu cầu ở trạng thái Nháp hoặc Chờ DVKH.');
        }

        $rules = [
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
        ];

        if (!Auth::user()->hasRole('TrungTam')) {
            $rules['distribution_center_id'] = ['required', 'exists:distribution_centers,id'];
        }

        $data = $request->validate($rules);

        $distributionCenterId = Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : $data['distribution_center_id'];

        DB::beginTransaction();

        try {
            $oldData = $certificateRequest->load('details')->toArray();
            $customerId = $this->resolveCustomerId($data);

            $certificateRequest->update([
                'distribution_center_id' => $distributionCenterId,
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
                'note' => $data['note'] ?? null,
            ]);

            $certificateRequest->details()->delete();

            foreach ($data['product_id'] as $index => $productId) {
                $certificateRequest->details()->create([
                    'product_id' => $productId,
                    'quantity' => $data['quantity'][$index],
                ]);
            }

            $this->logDuplicateInvoiceWarning($certificateRequest);

            ActivityLogger::log(
                'Yêu cầu cấp phiếu',
                'update',
                'Cập nhật yêu cầu cấp phiếu: ' . $certificateRequest->request_no,
                $oldData,
                $certificateRequest->fresh()->load('details')->toArray()
            );

            DB::commit();

            return redirect()
                ->route('certificate-requests.index')
                ->with('success', 'Cập nhật yêu cầu cấp phiếu thành công.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Có lỗi khi cập nhật yêu cầu: ' . $e->getMessage());
        }
    }

    public function destroy(CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if (!in_array($certificateRequest->status, ['DRAFT', 'WAIT_DVKH'])) {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chỉ được xóa yêu cầu ở trạng thái Nháp hoặc Chờ DVKH.');
        }

        $oldData = $certificateRequest->load('details')->toArray();

        $certificateRequest->delete();

        ActivityLogger::log(
            'Yêu cầu cấp phiếu',
            'delete',
            'Xóa yêu cầu cấp phiếu: ' . $certificateRequest->request_no,
            $oldData,
            null
        );

        return redirect()
            ->route('certificate-requests.index')
            ->with('success', 'Xóa yêu cầu cấp phiếu thành công.');
    }

    private function generateRequestNo(): string
    {
        $prefix = 'YC-' . date('Ymd') . '-';

        $count = CertificateRequest::whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function resolveCustomerId(array $data): int
    {
        if (($data['customer_mode'] ?? 'existing') === 'existing') {
            $customer = Customer::findOrFail($data['customer_id']);

            if (
                Auth::user()->hasRole('TrungTam')
                && (int) $customer->distribution_center_id !== (int) Auth::user()->distribution_center_id
            ) {
                abort(403, 'Anh không có quyền chọn khách hàng của trung tâm khác.');
            }

            return (int) $customer->id;
        }

        $customer = Customer::create([
            'distribution_center_id' => Auth::user()->hasRole('TrungTam')
                ? Auth::user()->distribution_center_id
                : ($data['distribution_center_id'] ?? null),
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
            'create_from_request',
            'Tạo khách hàng từ phiếu đề nghị: ' . $customer->customer_name,
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
            'Yêu cầu cấp phiếu',
            'duplicate_invoice_warning',
            'Cảnh báo số hóa đơn trùng khi lưu yêu cầu ' . $certificateRequest->request_no . ': ' . $certificateRequest->invoice_no . ' (' . $duplicateCount . ' bản ghi trùng)'
        );
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

    private function authorizeCenter(CertificateRequest $certificateRequest): void
    {
        if (
            Auth::user()->hasRole('TrungTam')
            && $certificateRequest->distribution_center_id != Auth::user()->distribution_center_id
        ) {
            abort(403, 'Anh không có quyền xem dữ liệu của trung tâm khác.');
        }
    }
}
