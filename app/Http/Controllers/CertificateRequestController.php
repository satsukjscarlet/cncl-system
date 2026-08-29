<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Exports\CertificateRequestProductsTemplateExport;
use App\Imports\CertificateRequestProductsImport;
use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\UrgentReason;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CertificateRequestController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = CertificateRequest::query();

        if (Auth::user()->hasRole('TrungTam')) {
            $baseQuery->where('certificate_requests.distribution_center_id', Auth::user()->distribution_center_id);
        } elseif (!Auth::user()->hasRole('Admin')) {
            $baseQuery->where('certificate_requests.status', '!=', 'DRAFT');
        }

        $tabCounts = $this->requestTabCounts(clone $baseQuery);
        $currentGroup = $request->input('status_group');

        if (!$request->filled('status') && !$request->has('status_group')) {
            $currentGroup = 'processing';
        }

        $query = (clone $baseQuery)->with([
            'distributionCenter',
            'customer',
            'creator',
            'urgentReason',
            'reissueOfCertificate',
            'reissueCertificates',
            'qualityCertificate',
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
            $this->applyStatusFilter($query, $request->status);
        } elseif ($currentGroup && $currentGroup !== 'all') {
            $this->applyStatusGroupFilter($query, $currentGroup);
        }

        if ($request->filled('distribution_center_id') && !Auth::user()->hasRole('TrungTam')) {
            $query->where('certificate_requests.distribution_center_id', $request->distribution_center_id);
        }

        $allowedSorts = [
            'request_no',
            'center',
            'customer',
            'delivery_date',
            'invoice_no',
            'hard_copy_quantity',
            'status',
            'created_at',
        ];

        $sort = in_array($request->input('sort'), $allowedSorts, true)
            ? $request->input('sort')
            : 'created_at';
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        if ($sort === 'center') {
            $query->leftJoin('distribution_centers as sort_centers', 'sort_centers.id', '=', 'certificate_requests.distribution_center_id')
                ->select('certificate_requests.*')
                ->orderBy('sort_centers.name', $direction)
                ->orderBy('certificate_requests.created_at', 'desc');
        } elseif ($sort === 'customer') {
            $query->leftJoin('customers as sort_customers', 'sort_customers.id', '=', 'certificate_requests.customer_id')
                ->select('certificate_requests.*')
                ->orderBy('sort_customers.customer_name', $direction)
                ->orderBy('sort_customers.project_name', $direction)
                ->orderBy('certificate_requests.created_at', 'desc');
        } else {
            $query->orderBy('certificate_requests.' . $sort, $direction)
                ->orderBy('certificate_requests.created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, [15, 30, 50, 100], true) ? $perPage : 15;

        $requests = $query->paginate($perPage)->withQueryString();

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certificate_requests.index', compact(
            'requests',
            'centers',
            'sort',
            'direction',
            'perPage',
            'tabCounts',
            'currentGroup'
        ));
    }

    public function create()
    {
        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedCustomers = $this->selectedCustomersForForm();
        $selectedProducts = $this->selectedProductsForForm();

        $urgentReasons = UrgentReason::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certificate_requests.create', compact('centers', 'selectedCustomers', 'selectedProducts', 'urgentReasons'));
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

    public function productOptions(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $products = Product::with('qualityStandard')
            ->where('is_active', true)
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('product_code', 'like', '%' . $term . '%')
                        ->orWhere('product_name', 'like', '%' . $term . '%')
                        ->orWhere('nominal_size', 'like', '%' . $term . '%')
                        ->orWhereHas('qualityStandard', function ($standardQuery) use ($term) {
                            $standardQuery->where('code', 'like', '%' . $term . '%')
                                ->orWhere('name', 'like', '%' . $term . '%');
                        });
                });
            })
            ->orderBy('product_code')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $products
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'text' => $this->productOptionDisplayText($product),
                    'code' => $product->product_code,
                    'name' => $product->product_name,
                ])
                ->values(),
        ]);
    }

    public function customerOptions(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $centerId = Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : $request->input('distribution_center_id');

        $customers = Customer::query()
            ->where('is_active', true)
            ->when($centerId, function ($query) use ($centerId) {
                $query->where('distribution_center_id', $centerId);
            })
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('customer_code', 'like', '%' . $term . '%')
                        ->orWhere('customer_name', 'like', '%' . $term . '%')
                        ->orWhere('project_name', 'like', '%' . $term . '%')
                        ->orWhere('email', 'like', '%' . $term . '%')
                        ->orWhere('tax_code', 'like', '%' . $term . '%');
                });
            })
            ->orderBy('customer_name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $customers
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'text' => $this->customerOptionText($customer),
                ])
                ->values(),
        ]);
    }

    public function productsTemplate()
    {
        return Excel::download(
            new CertificateRequestProductsTemplateExport(),
            'template_san_pham_yeu_cau_cap_phieu.xlsx'
        );
    }

    public function importProducts(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new CertificateRequestProductsImport();
        Excel::import($import, $request->file('file'));

        return $this->productsImportResponse(
            $this->parseProductRows($import->rows(), 'File import')
        );
    }

    public function pasteProducts(Request $request)
    {
        $data = $request->validate([
            'products_text' => ['required', 'string', 'max:200000'],
        ]);

        $rows = collect(preg_split('/\R/u', trim($data['products_text'])))
            ->map(function ($line) {
                $line = rtrim((string) $line);

                if (trim($line) === '') {
                    return [
                        'ma_san_pham' => '',
                        'so_luong' => null,
                    ];
                }

                $columns = preg_split('/\t+|[,;]+|\s{2,}/u', $line);

                if (count($columns) < 2) {
                    $columns = preg_split('/\s+/u', $line, 2);
                }

                return [
                    'ma_san_pham' => trim((string) ($columns[0] ?? '')),
                    'so_luong' => trim((string) ($columns[1] ?? '')),
                ];
            });

        return $this->productsImportResponse(
            $this->parseProductRows($rows, 'Nội dung dán')
        );
    }

    private function parseProductRows($rows, string $sourceLabel): array|\Illuminate\Http\JsonResponse
    {
        $errors = [];
        $lineNo = 1;
        $parsedRows = collect();

        if ($rows->count() > 1000) {
            return response()->json([
                'message' => $sourceLabel . ' tối đa 1000 dòng/lần.',
                'errors' => [$sourceLabel . ' tối đa 1000 dòng/lần.'],
            ], 422);
        }

        foreach ($rows as $row) {
            $productCode = trim((string) ($row['ma_san_pham'] ?? $row['product_code'] ?? ''));
            $quantityRaw = $row['so_luong'] ?? $row['quantity'] ?? null;

            if (
                $lineNo === 1
                && in_array(mb_strtolower($productCode), ['ma_san_pham', 'mã sản phẩm', 'ma san pham', 'product_code'], true)
            ) {
                $lineNo++;
                continue;
            }

            if ($productCode === '' && blank($quantityRaw)) {
                $lineNo++;
                continue;
            }

            if ($productCode === '') {
                $errors[] = 'Dòng ' . $lineNo . ': Chưa nhập mã sản phẩm.';
            }

            if (!is_numeric($quantityRaw) || (float) $quantityRaw <= 0) {
                $errors[] = 'Dòng ' . $lineNo . ': Số lượng phải là số lớn hơn 0.';
            }

            if ($productCode !== '' && is_numeric($quantityRaw) && (float) $quantityRaw > 0) {
                $parsedRows->push([
                    'line' => $lineNo,
                    'product_code' => $productCode,
                    'product_code_normalized' => mb_strtoupper($productCode),
                    'quantity' => (float) $quantityRaw,
                ]);
            }

            $lineNo++;
        }

        if ($parsedRows->isEmpty() && empty($errors)) {
            $errors[] = $sourceLabel . ' không có dòng sản phẩm hợp lệ.';
        }

        $products = Product::with('qualityStandard')
            ->where('is_active', true)
            ->whereIn(DB::raw('UPPER(product_code)'), $parsedRows->pluck('product_code_normalized')->unique()->values())
            ->get()
            ->keyBy(fn (Product $product) => mb_strtoupper($product->product_code));

        foreach ($parsedRows as $row) {
            if (!$products->has($row['product_code_normalized'])) {
                $errors[] = 'Dòng ' . $row['line'] . ': Không tìm thấy mã sản phẩm "' . $row['product_code'] . '".';
            }
        }

        if (!empty($errors)) {
            return [
                'errors' => $errors,
                'items' => collect(),
            ];
        }

        $items = $parsedRows
            ->groupBy('product_code_normalized')
            ->map(function ($rows, $normalizedCode) use ($products) {
                $product = $products->get($normalizedCode);

                return [
                    'product_id' => $product->id,
                    'product_code' => $product->product_code,
                    'product_name' => $product->product_name,
                    'product_text' => $this->productOptionDisplayText($product),
                    'quantity' => $rows->sum('quantity'),
                ];
            })
            ->values();

        return [
            'errors' => [],
            'items' => $items,
        ];
    }

    private function productsImportResponse(array|\Illuminate\Http\JsonResponse $result)
    {
        if ($result instanceof \Illuminate\Http\JsonResponse) {
            return $result;
        }

        if (!empty($result['errors'])) {
            return response()->json([
                'message' => 'Danh sách sản phẩm có dữ liệu chưa hợp lệ.',
                'errors' => $result['errors'],
            ], 422);
        }

        $items = $result['items'];

        return response()->json([
            'items' => $items,
            'count' => $items->count(),
        ]);
    }
    public function store(Request $request)
    {
        $rules = [
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'new_customer_code' => $this->newCustomerCodeRules($request),
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
            'request_action' => ['nullable', 'in:draft,submit'],
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
                ->with('error', 'Tài khoản Trung tâm chưa được gắn Trung tâm phân phối.');
        }

        DB::beginTransaction();

        try {
            $customerId = $this->resolveCustomerId($data);
            $requestStatus = $this->requestStatusFromAction($request);

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
                'status' => $requestStatus,
                'submitted_at' => $requestStatus === 'WAIT_DVKH' ? now() : null,
                'submitted_by' => $requestStatus === 'WAIT_DVKH' ? Auth::id() : null,
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
                $certificateRequest->load('details')->toArray(),
                $certificateRequest
            );

            DB::commit();

            if ($certificateRequest->status === 'WAIT_DVKH') {
                app(NotificationService::class)->notifyRequestCreated(
                    $certificateRequest->fresh(['distributionCenter', 'customer'])
                );
            }

            return redirect()
                ->route('certificate-requests.index')
                ->with('success', $certificateRequest->status === 'WAIT_DVKH'
                    ? 'Đã tạo và gửi yêu cầu sang DVKH.'
                    : 'Đã lưu nháp yêu cầu cấp phiếu.');
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
            'qualityCertificate',
        ]);

        $invoiceDuplicates = $this->invoiceDuplicates($certificateRequest);
        $requestWorkflowSteps = $this->requestWorkflowSteps($certificateRequest);

        return view('certificate_requests.show', compact('certificateRequest', 'invoiceDuplicates', 'requestWorkflowSteps'));
    }

    public function edit(CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if ($certificateRequest->status !== 'DRAFT') {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chỉ được sửa yêu cầu đang ở trạng thái Nháp. Yêu cầu đã gửi DVKH không còn được sửa trực tiếp.');
        }

        $certificateRequest->load([
            'details',
            'reissueOfCertificate',
            'reissueCertificates',
        ]);

        $centers = DistributionCenter::where('is_active', true)->orderBy('name')->get();
        $selectedCustomers = $this->selectedCustomersForForm($certificateRequest);
        $selectedProducts = $this->selectedProductsForForm($certificateRequest);
        $urgentReasons = UrgentReason::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('certificate_requests.edit', compact(
            'certificateRequest',
            'centers',
            'selectedCustomers',
            'selectedProducts',
            'urgentReasons'
        ));
    }

    public function update(Request $request, CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if ($certificateRequest->status !== 'DRAFT') {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chỉ được sửa yêu cầu đang ở trạng thái Nháp. Yêu cầu đã gửi DVKH không còn được sửa trực tiếp.');
        }

        $rules = [
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'new_customer_code' => $this->newCustomerCodeRules($request, $certificateRequest),
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
            'request_action' => ['nullable', 'in:draft,submit'],
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
            $requestStatus = $this->requestStatusFromAction($request);

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
                'status' => $requestStatus,
                'submitted_at' => $requestStatus === 'WAIT_DVKH' ? now() : null,
                'submitted_by' => $requestStatus === 'WAIT_DVKH' ? Auth::id() : null,
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
                $certificateRequest->fresh()->load('details')->toArray(),
                $certificateRequest
            );

            DB::commit();

            if ($certificateRequest->fresh()->status === 'WAIT_DVKH') {
                app(NotificationService::class)->notifyRequestCreated(
                    $certificateRequest->fresh(['distributionCenter', 'customer'])
                );
            }

            return redirect()
                ->route('certificate-requests.index')
                ->with('success', $requestStatus === 'WAIT_DVKH'
                    ? 'Đã cập nhật và gửi yêu cầu sang DVKH.'
                    : 'Đã lưu nháp yêu cầu cấp phiếu.');
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

        if ($certificateRequest->status !== 'DRAFT') {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chỉ được xóa yêu cầu đang ở trạng thái Nháp.');
        }

        $oldData = $certificateRequest->load('details')->toArray();

        $certificateRequest->delete();

        ActivityLogger::log(
            'Yêu cầu cấp phiếu',
            'delete',
            'Xóa yêu cầu cấp phiếu: ' . $certificateRequest->request_no,
            $oldData,
            null,
            $certificateRequest
        );

        return redirect()
            ->route('certificate-requests.index')
            ->with('success', 'Xóa yêu cầu cấp phiếu thành công.');
    }

    public function submitDraft(CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if ($certificateRequest->status !== 'DRAFT') {
            return redirect()
                ->route('certificate-requests.show', $certificateRequest)
                ->with('error', 'Chỉ được gửi DVKH với yêu cầu đang ở trạng thái nháp.');
        }

        if (!$certificateRequest->details()->exists()) {
            return redirect()
                ->route('certificate-requests.show', $certificateRequest)
                ->with('error', 'Yêu cầu chưa có sản phẩm, vui lòng cập nhật trước khi gửi DVKH.');
        }

        $oldData = $certificateRequest->toArray();

        $certificateRequest->update([
            'status' => 'WAIT_DVKH',
            'submitted_at' => now(),
            'submitted_by' => Auth::id(),
        ]);

        $this->logDuplicateInvoiceWarning($certificateRequest);

        ActivityLogger::log(
            'Yêu cầu cấp phiếu',
            'submit',
            'Gửi yêu cầu cấp phiếu sang DVKH: ' . $certificateRequest->request_no,
            $oldData,
            $certificateRequest->fresh()->toArray(),
            $certificateRequest
        );

        app(NotificationService::class)->notifyRequestCreated(
            $certificateRequest->fresh(['distributionCenter', 'customer'])
        );

        return redirect()
            ->route('certificate-requests.show', $certificateRequest)
            ->with('success', 'Đã gửi yêu cầu sang DVKH.');
    }

    private function generateRequestNo(): string
    {
        $prefix = 'YC-' . date('Ymd') . '-';

        $lastRequestNo = CertificateRequest::withTrashed()
            ->where('request_no', 'like', $prefix . '%')
            ->orderByDesc('request_no')
            ->value('request_no');

        $nextNumber = $lastRequestNo
            ? ((int) substr($lastRequestNo, strlen($prefix))) + 1
            : 1;

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function requestWorkflowSteps(CertificateRequest $certificateRequest): array
    {
        $certificate = $certificateRequest->qualityCertificate;

        $steps = [
            [
                'title' => 'Trung tâm tạo yêu cầu',
                'status' => 'done',
                'icon' => 'fas fa-file-alt',
                'time' => $certificateRequest->created_at,
                'description' => 'Yêu cầu ' . $certificateRequest->request_no . ' được khởi tạo.',
            ],
            [
                'title' => 'DVKH kiểm tra',
                'status' => 'pending',
                'icon' => 'fas fa-user-check',
                'time' => null,
                'description' => 'Chờ DVKH xác nhận thông tin yêu cầu.',
            ],
            [
                'title' => 'PTN lập phiếu',
                'status' => 'pending',
                'icon' => 'fas fa-vials',
                'time' => $certificate?->created_at,
                'description' => 'Chờ PTN lập phiếu CNCL.',
            ],
            [
                'title' => 'Trưởng PTN ký số',
                'status' => 'pending',
                'icon' => 'fas fa-file-signature',
                'time' => $certificate?->smartca_requested_at,
                'description' => 'Chờ Trưởng PTN gửi yêu cầu ký số.',
            ],
            [
                'title' => 'Phát hành / thu hồi',
                'status' => 'pending',
                'icon' => 'fas fa-paper-plane',
                'time' => $certificate?->signed_at ?: $certificate?->revoked_at,
                'description' => 'Chờ ký số thành công và phát hành phiếu.',
            ],
        ];

        if ($certificateRequest->status === 'DRAFT') {
            $steps[0]['status'] = 'current';
            $steps[0]['description'] = 'Yêu cầu đang được lưu nháp, chưa gửi sang DVKH.';

            return $steps;
        }

        if ($certificateRequest->status === 'WAIT_DVKH') {
            $steps[1]['status'] = 'current';
        } elseif ($certificateRequest->status === 'CANCELLED') {
            $steps[1]['status'] = 'danger';
            $steps[1]['description'] = 'Yêu cầu đã bị trả lại / hủy.';
            $steps[2]['status'] = 'skipped';
            $steps[3]['status'] = 'skipped';
            $steps[4]['status'] = 'skipped';

            return $steps;
        } elseif (in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING', 'COMPLETED'], true) || $certificate) {
            $steps[1]['status'] = 'done';
            $steps[1]['description'] = 'DVKH đã xác nhận và chuyển yêu cầu sang PTN.';
        }

        if ($certificateRequest->status === 'WAIT_PTN') {
            $steps[2]['status'] = 'current';
        } elseif ($certificate || in_array($certificateRequest->status, ['PTN_PROCESSING', 'COMPLETED'], true)) {
            $steps[2]['status'] = 'done';
            $steps[2]['description'] = $certificate
                ? 'PTN đã lập phiếu ' . $certificate->certificate_no . '.'
                : 'PTN đã tiếp nhận xử lý yêu cầu.';
        }

        if (!$certificate) {
            return $steps;
        }

        if ($certificate->status === 'REJECTED') {
            $steps[3]['status'] = 'danger';
            $steps[3]['time'] = $certificate->rejected_at;
            $steps[3]['description'] = 'Trưởng PTN đã trả lại phiếu: ' . ($certificate->rejected_reason ?: '-');
            $steps[4]['status'] = 'skipped';
            $steps[4]['description'] = 'Chưa phát hành vì phiếu đã bị trả lại.';
        } elseif ($certificate->status === 'REVOKED') {
            $steps[3]['status'] = 'done';
            $steps[3]['description'] = 'Phiếu cũ đã được ký số trước khi bị thu hồi.';
            $steps[4]['status'] = 'danger';
            $steps[4]['description'] = 'Phiếu đã hủy / thu hồi. Lý do: ' . ($certificate->revoked_reason ?: '-');
        } elseif ($certificate->signed_at) {
            $steps[3]['status'] = 'done';
            $steps[3]['time'] = $certificate->signed_at;
            $steps[3]['description'] = 'Phiếu đã ký số thành công.';
            $steps[4]['status'] = 'done';
            $steps[4]['time'] = $certificate->signed_at;
            $steps[4]['description'] = 'Phiếu đã phát hành.';
        } elseif ($certificate->smartcaStatusExpired()) {
            $steps[3]['status'] = 'danger';
            $steps[3]['description'] = 'Yêu cầu ký đã quá hạn, cần kiểm tra kết quả cũ hoặc gửi lại yêu cầu ký.';
        } elseif ($certificate->smartca_status === 'PENDING') {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Đang chờ Trưởng PTN xác nhận trên app VNPT SmartCA.';
        } else {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Chờ Trưởng PTN kiểm tra và gửi yêu cầu ký số.';
        }

        return $steps;
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

            if (
                !Auth::user()->hasRole('TrungTam')
                && isset($data['distribution_center_id'])
                && (int) $customer->distribution_center_id !== (int) $data['distribution_center_id']
            ) {
                abort(422, 'Khach hang khong thuoc trung tam phan phoi da chon.');
            }

            return (int) $customer->id;
        }

        $customer = Customer::create([
            'distribution_center_id' => Auth::user()->hasRole('TrungTam')
                ? Auth::user()->distribution_center_id
                : ($data['distribution_center_id'] ?? null),
            'customer_code' => filled($data['new_customer_code'] ?? null)
                ? trim($data['new_customer_code'])
                : $this->generateCustomerCode(),
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
            $customer->toArray(),
            $customer
        );

        return $customer->id;
    }

    private function newCustomerCodeRules(Request $request, ?CertificateRequest $certificateRequest = null): array
    {
        $centerId = Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : ($request->input('distribution_center_id') ?: $certificateRequest?->distribution_center_id);

        return [
            'nullable',
            'string',
            'max:100',
            Rule::unique('customers', 'customer_code')
                ->where(fn ($query) => $query->where('distribution_center_id', $centerId)),
        ];
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

    private function selectedProductsForForm(?CertificateRequest $certificateRequest = null)
    {
        $productIds = collect(old('product_id', []));

        if ($productIds->isEmpty() && $certificateRequest) {
            $productIds = $certificateRequest->details->pluck('product_id');
        }

        $productIds = $productIds
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

    private function selectedCustomersForForm(?CertificateRequest $certificateRequest = null)
    {
        $customerIds = collect([old('customer_id', $certificateRequest->customer_id ?? null)])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($customerIds->isEmpty()) {
            return collect();
        }

        $centerId = Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : old('distribution_center_id', $certificateRequest->distribution_center_id ?? null);

        return Customer::whereIn('id', $customerIds)
            ->when($centerId, function ($query) use ($centerId) {
                $query->where('distribution_center_id', $centerId);
            })
            ->get()
            ->keyBy('id');
    }

    private function customerOptionText(Customer $customer): string
    {
        return collect([
            $customer->customer_code,
            $customer->customer_name,
            $customer->project_name,
            $customer->email,
        ])
            ->filter()
            ->implode(' - ');
    }

    private function productOptionText(Product $product): string
    {
        return collect([
            $product->product_code,
            $product->product_name,
            $product->nominal_size,
            $product->qualityStandard?->code,
        ])
            ->filter()
            ->implode(' - ');
    }

    private function productOptionDisplayText(Product $product): string
    {
        return collect([
            $product->product_code,
            $product->product_name,
        ])
            ->filter()
            ->implode(' - ');
    }

    private function authorizeCenter(CertificateRequest $certificateRequest): void
    {
        if (
            $certificateRequest->status === 'DRAFT'
            && !Auth::user()->hasRole('Admin')
            && !(
                Auth::user()->hasRole('TrungTam')
                && $certificateRequest->distribution_center_id == Auth::user()->distribution_center_id
            )
        ) {
            abort(403, 'Yêu cầu nháp chưa được Trung tâm gửi vào quy trình.');
        }

        if (
            Auth::user()->hasRole('TrungTam')
            && $certificateRequest->distribution_center_id != Auth::user()->distribution_center_id
        ) {
            abort(403, 'Anh không có quyền xem dữ liệu của trung tâm khác.');
        }
    }

    private function applyStatusFilter($query, string $status): void
    {
        if (in_array($status, ['DRAFT', 'WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING', 'COMPLETED', 'CANCELLED'], true)) {
            $query->where('status', $status);

            return;
        }

        $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

        if ($status === 'SIGN_READY') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->whereNull('signed_at')
                    ->whereIn('status', ['DRAFT', 'WAIT_PTN_MANAGER_APPROVAL', 'READY_TO_SIGN'])
                    ->where(function ($q) {
                        $q->whereNull('smartca_status')
                            ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                    });
            });
        }

        if ($status === 'SIGN_PENDING') {
            $query->whereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                $certificate
                    ->whereNull('signed_at')
                    ->where('smartca_status', 'PENDING')
                    ->where('smartca_requested_at', '>', $expiredBefore);
            });
        }

        if ($status === 'SIGN_EXPIRED') {
            $query->whereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                $certificate
                    ->whereNull('signed_at')
                    ->where(function ($q) use ($expiredBefore) {
                        $q->where('smartca_status', 'EXPIRED')
                            ->orWhere(function ($pending) use ($expiredBefore) {
                                $pending->where('smartca_status', 'PENDING')
                                    ->where('smartca_requested_at', '<=', $expiredBefore);
                            });
                    });
            });
        }

        if ($status === 'SIGNED') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->whereNotNull('signed_at')
                    ->where('status', 'ISSUED');
            });
        }
    }

    private function requestTabCounts($baseQuery): array
    {
        return [
            'processing' => tap(clone $baseQuery, fn ($query) => $this->applyStatusGroupFilter($query, 'processing'))->count(),
            'draft' => (clone $baseQuery)->where('status', 'DRAFT')->count(),
            'wait_dvkh' => (clone $baseQuery)->where('status', 'WAIT_DVKH')->count(),
            'wait_ptn' => (clone $baseQuery)->where('status', 'WAIT_PTN')->count(),
            'sign_ready' => tap(clone $baseQuery, fn ($query) => $this->applyStatusFilter($query, 'SIGN_READY'))->count(),
            'sign_pending' => tap(clone $baseQuery, fn ($query) => $this->applyStatusFilter($query, 'SIGN_PENDING'))->count(),
            'sign_expired' => tap(clone $baseQuery, fn ($query) => $this->applyStatusFilter($query, 'SIGN_EXPIRED'))->count(),
            'completed' => tap(clone $baseQuery, fn ($query) => $this->applyStatusGroupFilter($query, 'completed'))->count(),
            'cancelled' => (clone $baseQuery)->where('status', 'CANCELLED')->count(),
            'all' => (clone $baseQuery)->count(),
        ];
    }

    private function applyStatusGroupFilter($query, string $group): void
    {
        if ($group === 'processing') {
            $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

            $query->where(function ($q) use ($expiredBefore) {
                $q->whereIn('status', ['DRAFT', 'WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING'])
                    ->orWhereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                        $certificate
                            ->whereNull('signed_at')
                            ->where(function ($statusQuery) use ($expiredBefore) {
                                $statusQuery
                                    ->whereIn('status', ['DRAFT', 'WAIT_PTN_MANAGER_APPROVAL', 'READY_TO_SIGN'])
                                    ->orWhere('smartca_status', 'PENDING')
                                    ->orWhere('smartca_status', 'EXPIRED')
                                    ->orWhere(function ($pending) use ($expiredBefore) {
                                        $pending->where('smartca_status', 'PENDING')
                                            ->where('smartca_requested_at', '<=', $expiredBefore);
                                    });
                            });
                    });
            });

            return;
        }

        if ($group === 'draft') {
            $query->where('status', 'DRAFT');

            return;
        }

        if ($group === 'wait_dvkh') {
            $query->where('status', 'WAIT_DVKH');

            return;
        }

        if ($group === 'wait_ptn') {
            $query->where('status', 'WAIT_PTN');

            return;
        }

        if ($group === 'sign_ready') {
            $this->applyStatusFilter($query, 'SIGN_READY');

            return;
        }

        if ($group === 'sign_pending') {
            $this->applyStatusFilter($query, 'SIGN_PENDING');

            return;
        }

        if ($group === 'sign_expired') {
            $this->applyStatusFilter($query, 'SIGN_EXPIRED');

            return;
        }

        if ($group === 'completed') {
            $query->where(function ($q) {
                $q->where('status', 'COMPLETED')
                    ->orWhereHas('qualityCertificates', function ($certificate) {
                        $certificate
                            ->whereNotNull('signed_at')
                            ->where('status', 'ISSUED');
                    });
            });

            return;
        }

        if ($group === 'cancelled') {
            $query->where('status', 'CANCELLED');
        }
    }

    private function smartCaPendingTtlMinutes(): int
    {
        return max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
    }

    private function requestStatusFromAction(Request $request): string
    {
        return $request->input('request_action', 'submit') === 'draft'
            ? 'DRAFT'
            : 'WAIT_DVKH';
    }
}

