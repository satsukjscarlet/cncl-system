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
use Maatwebsite\Excel\Facades\Excel;

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
            'reissueCertificates',
            'qualityCertificate',
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
            $this->applyStatusFilter($query, $request->status);
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
                    'text' => $this->productOptionText($product),
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
                    'product_text' => $this->productOptionText($product),
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
            'new_customer_code' => ['nullable', 'string', 'max:100', 'unique:customers,customer_code'],
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
                ->with('error', 'TÃ i khoáº£n Trung tÃ¢m chÆ°a Ä‘Æ°á»£c gÃ¡n Trung tÃ¢m phÃ¢n phá»‘i.');
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
                'YÃªu cáº§u cáº¥p phiáº¿u',
                'create',
                'Táº¡o yÃªu cáº§u cáº¥p phiáº¿u: ' . $certificateRequest->request_no,
                null,
                $certificateRequest->load('details')->toArray(),
                $certificateRequest
            );

            DB::commit();

            app(NotificationService::class)->notifyRequestCreated(
                $certificateRequest->fresh(['distributionCenter', 'customer'])
            );

            return redirect()
                ->route('certificate-requests.index')
                ->with('success', 'Táº¡o yÃªu cáº§u cáº¥p phiáº¿u thÃ nh cÃ´ng.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'CÃ³ lá»—i khi táº¡o yÃªu cáº§u: ' . $e->getMessage());
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

        if (!in_array($certificateRequest->status, ['DRAFT', 'WAIT_DVKH'])) {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chá»‰ Ä‘Æ°á»£c sá»­a yÃªu cáº§u á»Ÿ tráº¡ng thÃ¡i NhÃ¡p hoáº·c Chá» DVKH.');
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

        if (!in_array($certificateRequest->status, ['DRAFT', 'WAIT_DVKH'])) {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chá»‰ Ä‘Æ°á»£c sá»­a yÃªu cáº§u á»Ÿ tráº¡ng thÃ¡i NhÃ¡p hoáº·c Chá» DVKH.');
        }

        $rules = [
            'customer_mode' => ['required', 'in:existing,new'],
            'customer_id' => ['required_if:customer_mode,existing', 'nullable', 'exists:customers,id'],
            'new_customer_code' => ['nullable', 'string', 'max:100', 'unique:customers,customer_code'],
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
                'YÃªu cáº§u cáº¥p phiáº¿u',
                'update',
                'Cáº­p nháº­t yÃªu cáº§u cáº¥p phiáº¿u: ' . $certificateRequest->request_no,
                $oldData,
                $certificateRequest->fresh()->load('details')->toArray(),
                $certificateRequest
            );

            DB::commit();

            return redirect()
                ->route('certificate-requests.index')
                ->with('success', 'Cáº­p nháº­t yÃªu cáº§u cáº¥p phiáº¿u thÃ nh cÃ´ng.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'CÃ³ lá»—i khi cáº­p nháº­t yÃªu cáº§u: ' . $e->getMessage());
        }
    }

    public function destroy(CertificateRequest $certificateRequest)
    {
        $this->authorizeCenter($certificateRequest);

        if (!in_array($certificateRequest->status, ['DRAFT', 'WAIT_DVKH'])) {
            return redirect()
                ->route('certificate-requests.index')
                ->with('error', 'Chá»‰ Ä‘Æ°á»£c xÃ³a yÃªu cáº§u á»Ÿ tráº¡ng thÃ¡i NhÃ¡p hoáº·c Chá» DVKH.');
        }

        $oldData = $certificateRequest->load('details')->toArray();

        $certificateRequest->delete();

        ActivityLogger::log(
            'YÃªu cáº§u cáº¥p phiáº¿u',
            'delete',
            'XÃ³a yÃªu cáº§u cáº¥p phiáº¿u: ' . $certificateRequest->request_no,
            $oldData,
            null,
            $certificateRequest
        );

        return redirect()
            ->route('certificate-requests.index')
            ->with('success', 'XÃ³a yÃªu cáº§u cáº¥p phiáº¿u thÃ nh cÃ´ng.');
    }

    private function generateRequestNo(): string
    {
        $prefix = 'YC-' . date('Ymd') . '-';

        $count = CertificateRequest::whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function requestWorkflowSteps(CertificateRequest $certificateRequest): array
    {
        $certificate = $certificateRequest->qualityCertificate;

        $steps = [
            [
                'title' => 'Trung tÃ¢m táº¡o yÃªu cáº§u',
                'status' => 'done',
                'icon' => 'fas fa-file-alt',
                'time' => $certificateRequest->created_at,
                'description' => 'YÃªu cáº§u ' . $certificateRequest->request_no . ' Ä‘Æ°á»£c khá»Ÿi táº¡o.',
            ],
            [
                'title' => 'DVKH kiá»ƒm tra',
                'status' => 'pending',
                'icon' => 'fas fa-user-check',
                'time' => null,
                'description' => 'Chá» DVKH xÃ¡c nháº­n thÃ´ng tin yÃªu cáº§u.',
            ],
            [
                'title' => 'PTN láº­p phiáº¿u',
                'status' => 'pending',
                'icon' => 'fas fa-vials',
                'time' => $certificate?->created_at,
                'description' => 'Chá» PTN láº­p phiáº¿u CNCL.',
            ],
            [
                'title' => 'TrÆ°á»Ÿng PTN kÃ½ sá»‘',
                'status' => 'pending',
                'icon' => 'fas fa-file-signature',
                'time' => $certificate?->smartca_requested_at,
                'description' => 'Chá» TrÆ°á»Ÿng PTN gá»­i yÃªu cáº§u kÃ½ sá»‘.',
            ],
            [
                'title' => 'PhÃ¡t hÃ nh / thu há»“i',
                'status' => 'pending',
                'icon' => 'fas fa-paper-plane',
                'time' => $certificate?->signed_at ?: $certificate?->revoked_at,
                'description' => 'Chá» kÃ½ sá»‘ thÃ nh cÃ´ng vÃ  phÃ¡t hÃ nh phiáº¿u.',
            ],
        ];

        if ($certificateRequest->status === 'WAIT_DVKH') {
            $steps[1]['status'] = 'current';
        } elseif ($certificateRequest->status === 'CANCELLED') {
            $steps[1]['status'] = 'danger';
            $steps[1]['description'] = 'YÃªu cáº§u Ä‘Ã£ bá»‹ tráº£ láº¡i / há»§y.';
            $steps[2]['status'] = 'skipped';
            $steps[3]['status'] = 'skipped';
            $steps[4]['status'] = 'skipped';

            return $steps;
        } else {
            $steps[1]['status'] = 'done';
            $steps[1]['description'] = 'DVKH Ä‘Ã£ xÃ¡c nháº­n vÃ  chuyá»ƒn yÃªu cáº§u sang PTN.';
        }

        if ($certificateRequest->status === 'WAIT_PTN') {
            $steps[2]['status'] = 'current';
        } elseif ($certificate || in_array($certificateRequest->status, ['PTN_PROCESSING', 'COMPLETED'], true)) {
            $steps[2]['status'] = 'done';
            $steps[2]['description'] = $certificate
                ? 'PTN Ä‘Ã£ láº­p phiáº¿u ' . $certificate->certificate_no . '.'
                : 'PTN Ä‘Ã£ tiáº¿p nháº­n xá»­ lÃ½ yÃªu cáº§u.';
        }

        if (!$certificate) {
            return $steps;
        }

        if ($certificate->status === 'REJECTED') {
            $steps[3]['status'] = 'danger';
            $steps[3]['time'] = $certificate->rejected_at;
            $steps[3]['description'] = 'TrÆ°á»Ÿng PTN Ä‘Ã£ tráº£ láº¡i phiáº¿u: ' . ($certificate->rejected_reason ?: '-');
            $steps[4]['status'] = 'skipped';
            $steps[4]['description'] = 'ChÆ°a phÃ¡t hÃ nh vÃ¬ phiáº¿u Ä‘Ã£ bá»‹ tráº£ láº¡i.';
        } elseif ($certificate->status === 'REVOKED') {
            $steps[3]['status'] = 'done';
            $steps[3]['description'] = 'Phiáº¿u cÅ© Ä‘Ã£ Ä‘Æ°á»£c kÃ½ sá»‘ trÆ°á»›c khi bá»‹ thu há»“i.';
            $steps[4]['status'] = 'danger';
            $steps[4]['description'] = 'Phiáº¿u Ä‘Ã£ há»§y / thu há»“i. LÃ½ do: ' . ($certificate->revoked_reason ?: '-');
        } elseif ($certificate->signed_at) {
            $steps[3]['status'] = 'done';
            $steps[3]['time'] = $certificate->signed_at;
            $steps[3]['description'] = 'Phiáº¿u Ä‘Ã£ kÃ½ sá»‘ thÃ nh cÃ´ng.';
            $steps[4]['status'] = 'done';
            $steps[4]['time'] = $certificate->signed_at;
            $steps[4]['description'] = 'Phiáº¿u Ä‘Ã£ phÃ¡t hÃ nh.';
        } elseif ($certificate->smartcaStatusExpired()) {
            $steps[3]['status'] = 'danger';
            $steps[3]['description'] = 'YÃªu cáº§u kÃ½ Ä‘Ã£ quÃ¡ háº¡n, cáº§n kiá»ƒm tra káº¿t quáº£ cÅ© hoáº·c gá»­i láº¡i yÃªu cáº§u kÃ½.';
        } elseif ($certificate->smartca_status === 'PENDING') {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Äang chá» TrÆ°á»Ÿng PTN xÃ¡c nháº­n trÃªn app VNPT SmartCA.';
        } else {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Chá» TrÆ°á»Ÿng PTN kiá»ƒm tra vÃ  gá»­i yÃªu cáº§u kÃ½ sá»‘.';
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
                abort(403, 'Anh khÃ´ng cÃ³ quyá»n chá»n khÃ¡ch hÃ ng cá»§a trung tÃ¢m khÃ¡c.');
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
            'KhÃ¡ch hÃ ng - CÃ´ng trÃ¬nh',
            'create_from_request',
            'Táº¡o khÃ¡ch hÃ ng tá»« phiáº¿u Ä‘á» nghá»‹: ' . $customer->customer_name,
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
            'YÃªu cáº§u cáº¥p phiáº¿u',
            'duplicate_invoice_warning',
            'Cáº£nh bÃ¡o sá»‘ hÃ³a Ä‘Æ¡n trÃ¹ng khi lÆ°u yÃªu cáº§u ' . $certificateRequest->request_no . ': ' . $certificateRequest->invoice_no . ' (' . $duplicateCount . ' báº£n ghi trÃ¹ng)'
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

    private function authorizeCenter(CertificateRequest $certificateRequest): void
    {
        if (
            Auth::user()->hasRole('TrungTam')
            && $certificateRequest->distribution_center_id != Auth::user()->distribution_center_id
        ) {
            abort(403, 'Anh khÃ´ng cÃ³ quyá»n xem dá»¯ liá»‡u cá»§a trung tÃ¢m khÃ¡c.');
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
                    ->where('status', 'DRAFT')
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

    private function smartCaPendingTtlMinutes(): int
    {
        return max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
    }
}

