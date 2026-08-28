<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Exports\CustomersTemplateExport;
use App\Helpers\ActivityLogger;
use App\Models\Customer;
use App\Models\DistributionCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('distributionCenter');

        $this->scopeCustomersForCurrentUser($query);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('customer_code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('customer_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('customer_address', 'like', '%' . $request->keyword . '%')
                    ->orWhere('tax_code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('contact_person', 'like', '%' . $request->keyword . '%')
                    ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%')
                    ->orWhere('project_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('project_address', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $customers = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('customers.index', compact('customers', 'centers'));
    }

    public function create()
    {
        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('customers.create', compact('centers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->customerRules($request));
        $data['is_active'] = $request->boolean('is_active');
        $data['distribution_center_id'] = $this->resolveDistributionCenterId($data);

        if (Auth::user()->hasRole('TrungTam') && !$data['distribution_center_id']) {
            return back()
                ->withInput()
                ->with('error', 'Tài khoản Trung tâm chưa được gán Trung tâm phân phối.');
        }

        $customer = Customer::create($data);

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'create',
            'Thêm khách hàng/công trình: ' . $customer->customer_name,
            null,
            $customer->toArray()
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Thêm khách hàng - công trình thành công.');
    }

    public function edit(Customer $customer)
    {
        $this->authorizeCustomerCenter($customer);

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('customers.edit', compact('customer', 'centers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomerCenter($customer);

        $data = $request->validate($this->customerRules($request, $customer));
        $oldData = $customer->toArray();

        $data['is_active'] = $request->boolean('is_active');
        $data['distribution_center_id'] = $this->resolveDistributionCenterId($data, $customer);

        if (Auth::user()->hasRole('TrungTam') && !$data['distribution_center_id']) {
            return back()
                ->withInput()
                ->with('error', 'Tài khoản Trung tâm chưa được gán Trung tâm phân phối.');
        }

        $customer->update($data);

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'update',
            'Cập nhật khách hàng/công trình: ' . $customer->customer_name,
            $oldData,
            $customer->fresh()->toArray()
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Cập nhật khách hàng - công trình thành công.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizeCustomerCenter($customer);

        $oldData = $customer->toArray();
        $customer->update(['is_active' => false]);
        $customer->refresh();

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'deactivate',
            'Ngừng sử dụng khách hàng/công trình: ' . $oldData['customer_name'],
            $oldData,
            $customer->toArray(),
            $customer
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Đã ngừng sử dụng khách hàng - công trình.');
    }

    public function export(): BinaryFileResponse
    {
        ActivityLogger::log(
            'Khách hàng - Công trình',
            'export',
            'Xuất Excel danh mục khách hàng - công trình'
        );

        return Excel::download(
            new CustomersExport($this->currentDistributionCenterId()),
            'danh_muc_khach_hang_cong_trinh.xlsx'
        );
    }

    public function import(Request $request)
    {
        if (Auth::user()->hasRole('TrungTam') && !$this->currentDistributionCenterId()) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'Tài khoản Trung tâm chưa được gán Trung tâm phân phối.');
        }

        $request->validate([
            'file' => ['nullable', 'file', 'mimes:xlsx,xls,csv'],
            'temp_path' => ['nullable', 'string'],
            'confirm_update' => ['nullable', 'boolean'],
            'import_distribution_center_id' => ['nullable', 'exists:distribution_centers,id'],
        ]);

        if (!$request->hasFile('file') && !$request->filled('temp_path')) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'Vui lòng chọn file Excel để import.');
        }

        $path = $request->filled('temp_path')
            ? $request->input('temp_path')
            : $request->file('file')->store('customer-imports');

        if (!Storage::exists($path)) {
            return redirect()
                ->route('customers.index')
                ->with('error', 'File import tạm không còn tồn tại. Vui lòng tải lại file.');
        }

        $result = $this->parseCustomerImport($path, $request);

        if (!empty($result['errors'])) {
            if (!$request->filled('temp_path')) {
                Storage::delete($path);
            }

            return redirect()
                ->route('customers.index')
                ->with('error', 'File import có lỗi, chưa ghi dữ liệu.')
                ->with('customer_import_errors', $result['errors']);
        }

        if ($result['update_count'] > 0 && !$request->boolean('confirm_update')) {
            return redirect()
                ->route('customers.index')
                ->with('customer_import_preview', [
                    'temp_path' => $path,
                    'create_count' => $result['create_count'],
                    'update_count' => $result['update_count'],
                    'duplicates' => array_slice($result['duplicates'], 0, 30),
                    'total_duplicates' => count($result['duplicates']),
                    'import_distribution_center_id' => $request->input('import_distribution_center_id'),
                ]);
        }

        foreach ($result['rows'] as $row) {
            Customer::updateOrCreate(
                [
                    'distribution_center_id' => $row['distribution_center_id'],
                    'customer_code' => $row['customer_code'],
                ],
                [
                    'customer_name' => $row['customer_name'],
                    'customer_address' => $row['customer_address'],
                    'tax_code' => $row['tax_code'],
                    'contact_person' => $row['contact_person'],
                    'phone' => $row['phone'],
                    'email' => $row['email'],
                    'project_name' => $row['project_name'],
                    'project_address' => $row['project_address'],
                    'is_active' => $row['is_active'],
                ]
            );
        }

        Storage::delete($path);

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'import',
            'Import Excel danh mục khách hàng - công trình: thêm mới ' . $result['create_count'] . ', cập nhật ' . $result['update_count']
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Import thành công: thêm mới ' . $result['create_count'] . ', cập nhật ' . $result['update_count'] . ' khách hàng.');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(
            new CustomersTemplateExport(),
            'template_khach_hang_cong_trinh.xlsx'
        );
    }

    private function customerRules(Request $request, ?Customer $customer = null): array
    {
        $centerId = $this->resolveDistributionCenterId($request->all(), $customer);

        return [
            'distribution_center_id' => ['nullable', 'exists:distribution_centers,id'],
            'customer_code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('customers', 'customer_code')
                    ->ignore($customer?->id)
                    ->where(fn ($query) => $query->where('distribution_center_id', $centerId)),
            ],
            'customer_name' => ['required', 'string', 'max:500'],
            'customer_address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:500'],
            'project_address' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ];
    }

    private function parseCustomerImport(string $path, Request $request): array
    {
        $spreadsheet = IOFactory::load(Storage::path($path));
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $spreadsheet->disconnectWorksheets();

        $headerRow = array_shift($rows) ?: [];
        $headers = [];

        foreach ($headerRow as $column => $heading) {
            $key = $this->normalizeHeading($heading);

            if ($key) {
                $headers[$column] = $key;
            }
        }

        $errors = [];
        $parsedRows = [];
        $duplicates = [];
        $seenKeys = [];
        $fixedCenterId = $this->importDistributionCenterId($request);
        $centersByCode = DistributionCenter::query()
            ->where('is_active', true)
            ->get()
            ->keyBy(fn ($center) => strtoupper(trim($center->code)));

        foreach ($rows as $rowNumber => $excelRow) {
            $row = $this->mapCustomerImportRow($excelRow, $headers);

            if ($this->isEmptyImportRow($row)) {
                continue;
            }

            $line = $rowNumber + 1;
            $centerId = $fixedCenterId;

            if (!$centerId) {
                $centerCode = strtoupper(trim((string) ($row['ma_trung_tam'] ?? $row['trung_tam'] ?? '')));

                if (!$centerCode) {
                    $errors[] = "Dòng {$line}: Thiếu mã trung tâm. Admin cần nhập cột ma_trung_tam hoặc chọn một trung tâm khi import.";
                    continue;
                }

                $center = $centersByCode->get($centerCode);

                if (!$center) {
                    $errors[] = "Dòng {$line}: Không tìm thấy trung tâm có mã {$centerCode}.";
                    continue;
                }

                $centerId = $center->id;
            }

            $customerCode = trim((string) ($row['ma_khach_hang'] ?? ''));
            $customerName = trim((string) ($row['ten_khach_hang'] ?? ''));

            if (!$customerCode) {
                $errors[] = "Dòng {$line}: Thiếu mã khách hàng.";
                continue;
            }

            if (!$customerName) {
                $errors[] = "Dòng {$line}: Thiếu tên khách hàng.";
                continue;
            }

            $email = $this->nullIfEmpty($row['email'] ?? null);

            if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Dòng {$line}: Email {$email} không đúng định dạng.";
                continue;
            }

            $key = $centerId . '|' . mb_strtoupper($customerCode);

            if (isset($seenKeys[$key])) {
                $errors[] = "Dòng {$line}: Trùng mã khách hàng {$customerCode} trong cùng file import với dòng {$seenKeys[$key]}.";
                continue;
            }

            $seenKeys[$key] = $line;
            $existing = Customer::where('distribution_center_id', $centerId)
                ->where('customer_code', $customerCode)
                ->first();

            if ($existing) {
                $duplicates[] = [
                    'line' => $line,
                    'customer_code' => $customerCode,
                    'customer_name' => $existing->customer_name,
                    'new_customer_name' => $customerName,
                    'center' => DistributionCenter::find($centerId)?->code,
                ];
            }

            $parsedRows[] = [
                'distribution_center_id' => $centerId,
                'customer_code' => $customerCode,
                'customer_name' => $customerName,
                'customer_address' => $this->nullIfEmpty($row['dia_chi_khach_hang'] ?? null),
                'tax_code' => $this->nullIfEmpty($row['ma_so_thue'] ?? null),
                'contact_person' => $this->nullIfEmpty($row['nguoi_lien_he'] ?? null),
                'phone' => $this->nullIfEmpty($row['dien_thoai'] ?? null),
                'email' => $email,
                'project_name' => $this->nullIfEmpty($row['ten_cong_trinh'] ?? null),
                'project_address' => $this->nullIfEmpty($row['dia_diem_cong_trinh'] ?? null),
                'is_active' => $this->parseBoolean($row['dang_su_dung'] ?? true),
                'exists' => (bool) $existing,
            ];
        }

        return [
            'rows' => $parsedRows,
            'errors' => $errors,
            'duplicates' => $duplicates,
            'create_count' => collect($parsedRows)->where('exists', false)->count(),
            'update_count' => collect($parsedRows)->where('exists', true)->count(),
        ];
    }

    private function mapCustomerImportRow(array $row, array $headers): array
    {
        $mapped = [];

        foreach ($headers as $column => $key) {
            $mapped[$key] = is_string($row[$column] ?? null)
                ? trim($row[$column])
                : ($row[$column] ?? null);
        }

        return $mapped;
    }

    private function importDistributionCenterId(Request $request): ?int
    {
        if (Auth::user()->hasRole('TrungTam')) {
            return Auth::user()->distribution_center_id;
        }

        return $request->filled('import_distribution_center_id')
            ? (int) $request->input('import_distribution_center_id')
            : null;
    }

    private function normalizeHeading($heading): string
    {
        return str((string) $heading)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();
    }

    private function isEmptyImportRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->isEmpty();
    }

    private function nullIfEmpty($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseBoolean($value): bool
    {
        $value = mb_strtolower(trim((string) $value));

        return !in_array($value, ['0', 'no', 'false', 'ngung', 'ngừng', 'khong', 'không'], true);
    }

    private function currentDistributionCenterId(): ?int
    {
        return Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : null;
    }

    private function scopeCustomersForCurrentUser($query): void
    {
        $centerId = $this->currentDistributionCenterId();

        if ($centerId) {
            $query->where('distribution_center_id', $centerId);
        }
    }

    private function resolveDistributionCenterId(array $data, ?Customer $customer = null): ?int
    {
        if (Auth::user()->hasRole('TrungTam')) {
            return Auth::user()->distribution_center_id;
        }

        return $data['distribution_center_id'] ?? $customer?->distribution_center_id;
    }

    private function authorizeCustomerCenter(Customer $customer): void
    {
        $centerId = $this->currentDistributionCenterId();

        if ($centerId && (int) $customer->distribution_center_id !== (int) $centerId) {
            abort(403, 'Anh không có quyền thao tác khách hàng của trung tâm khác.');
        }
    }
}
