<?php

namespace App\Http\Controllers;

use App\Exports\SalesUnitsExport;
use App\Exports\SalesUnitsTemplateExport;
use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\SalesUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SalesUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesUnit::with('distributionCenter')
            ->withCount([
                'certificateRequests as certificate_requests_total_count' => fn ($query) => $query->withTrashed(),
            ]);

        if (Auth::user()->hasRole('TrungTam')) {
            $query->where('distribution_center_id', Auth::user()->distribution_center_id);
        }

        if ($request->filled('distribution_center_id') && !Auth::user()->hasRole('TrungTam')) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('address', 'like', '%' . $request->keyword . '%')
                    ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                    ->orWhere('tax_code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('bank_account', 'like', '%' . $request->keyword . '%')
                    ->orWhere('representative', 'like', '%' . $request->keyword . '%')
                    ->orWhere('note', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $salesUnits = $query
            ->leftJoin('distribution_centers as sort_centers', 'sort_centers.id', '=', 'sales_units.distribution_center_id')
            ->select('sales_units.*')
            ->orderBy('sort_centers.code')
            ->orderBy('sales_units.name')
            ->paginate(15)
            ->withQueryString();

        $centers = DistributionCenter::where('is_active', true)->orderBy('name')->get();

        return view('sales_units.index', compact('salesUnits', 'centers'));
    }

    public function create()
    {
        abort_if(Auth::user()->hasRole('TrungTam'), 403);

        $centers = DistributionCenter::where('is_active', true)->orderBy('name')->get();

        return view('sales_units.create', compact('centers'));
    }

    public function store(Request $request)
    {
        abort_if(Auth::user()->hasRole('TrungTam'), 403);

        $data = $request->validate($this->rules($request), $this->messages());
        $data['is_active'] = $request->boolean('is_active', true);
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $salesUnit = SalesUnit::create($data);

        ActivityLogger::log(
            'Đơn vị bán hàng',
            'create',
            'Thêm đơn vị bán hàng: ' . $salesUnit->name,
            null,
            $salesUnit->toArray(),
            $salesUnit
        );

        return redirect()
            ->route('sales-units.index')
            ->with('success', 'Đã thêm đơn vị bán hàng.');
    }

    public function edit(SalesUnit $salesUnit)
    {
        abort_if(Auth::user()->hasRole('TrungTam'), 403);

        $centers = DistributionCenter::where('is_active', true)->orderBy('name')->get();

        return view('sales_units.edit', compact('salesUnit', 'centers'));
    }

    public function update(Request $request, SalesUnit $salesUnit)
    {
        abort_if(Auth::user()->hasRole('TrungTam'), 403);

        $data = $request->validate($this->rules($request, $salesUnit), $this->messages());
        $oldData = $salesUnit->toArray();
        $data['is_active'] = $request->boolean('is_active');
        $data['updated_by'] = Auth::id();

        $salesUnit->update($data);

        ActivityLogger::log(
            'Đơn vị bán hàng',
            'update',
            'Cập nhật đơn vị bán hàng: ' . $salesUnit->name,
            $oldData,
            $salesUnit->fresh()->toArray(),
            $salesUnit
        );

        return redirect()
            ->route('sales-units.index')
            ->with('success', 'Đã cập nhật đơn vị bán hàng.');
    }

    public function destroy(SalesUnit $salesUnit)
    {
        abort_if(Auth::user()->hasRole('TrungTam'), 403);

        $oldData = $salesUnit->toArray();
        $salesUnit->update([
            'is_active' => false,
            'updated_by' => Auth::id(),
        ]);

        ActivityLogger::log(
            'Đơn vị bán hàng',
            'deactivate',
            'Ngừng sử dụng đơn vị bán hàng: ' . $salesUnit->name,
            $oldData,
            $salesUnit->fresh()->toArray(),
            $salesUnit
        );

        return redirect()
            ->route('sales-units.index')
            ->with('success', 'Đã ngừng sử dụng đơn vị bán hàng.');
    }

    public function forceDestroy(SalesUnit $salesUnit)
    {
        abort_unless(Auth::user()?->hasRole('Admin'), 403);

        if (CertificateRequest::withTrashed()->where('sales_unit_id', $salesUnit->id)->exists()) {
            return redirect()
                ->route('sales-units.index')
                ->with('error', 'Không thể xóa hẳn đơn vị bán hàng đã được dùng trong yêu cầu cấp phiếu.');
        }

        $oldData = $salesUnit->toArray();
        $salesUnit->forceDelete();

        ActivityLogger::log(
            'Đơn vị bán hàng',
            'force_delete',
            'Xóa hẳn đơn vị bán hàng: ' . ($oldData['name'] ?? ''),
            $oldData,
            null
        );

        return redirect()
            ->route('sales-units.index')
            ->with('success', 'Đã xóa hẳn đơn vị bán hàng chưa phát sinh liên kết.');
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new SalesUnitsExport($this->currentDistributionCenterId()), 'danh_muc_don_vi_ban_hang.xlsx');
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new SalesUnitsTemplateExport(), 'template_don_vi_ban_hang.xlsx');
    }

    public function import(Request $request)
    {
        abort_if(Auth::user()->hasRole('TrungTam'), 403);

        $request->validate([
            'file' => ['nullable', 'file', 'mimes:xlsx,xls,csv'],
            'temp_path' => ['nullable', 'string'],
            'temp_token' => ['nullable', 'string'],
            'confirm_update' => ['nullable', 'boolean'],
            'import_distribution_center_id' => ['nullable', 'exists:distribution_centers,id'],
        ]);

        if (!$request->hasFile('file') && !$request->filled('temp_path')) {
            return redirect()->route('sales-units.index')->with('error', 'Vui lòng chọn file Excel để import.');
        }

        $tempToken = $request->input('temp_token');

        if ($request->filled('temp_path')) {
            $path = $request->input('temp_path');
            $sessionPath = $tempToken ? session('sales_unit_imports.' . $tempToken) : null;

            if (!$sessionPath || !hash_equals($sessionPath, $path)) {
                return redirect()->route('sales-units.index')->with('error', 'File import tạm không hợp lệ hoặc phiên xác nhận đã hết hạn.');
            }
        } else {
            $path = $request->file('file')->store('sales-unit-imports');
            $tempToken = bin2hex(random_bytes(16));
            session(['sales_unit_imports.' . $tempToken => $path]);
        }

        if (!Storage::exists($path)) {
            if ($tempToken) {
                session()->forget('sales_unit_imports.' . $tempToken);
            }

            return redirect()->route('sales-units.index')->with('error', 'File import tạm không còn tồn tại. Vui lòng tải lại file.');
        }

        $result = $this->parseSalesUnitImport($path, $request);

        if ($result['errors']) {
            Storage::delete($path);

            if ($tempToken) {
                session()->forget('sales_unit_imports.' . $tempToken);
            }

            return redirect()
                ->route('sales-units.index')
                ->with('error', 'File import có lỗi, chưa ghi dữ liệu.')
                ->with('sales_unit_import_errors', $result['errors']);
        }

        if ($result['update_count'] > 0 && !$request->boolean('confirm_update')) {
            return redirect()
                ->route('sales-units.index')
                ->with('sales_unit_import_preview', [
                    'temp_path' => $path,
                    'temp_token' => $tempToken,
                    'create_count' => $result['create_count'],
                    'update_count' => $result['update_count'],
                    'duplicates' => array_slice($result['duplicates'], 0, 30),
                    'total_duplicates' => count($result['duplicates']),
                    'import_distribution_center_id' => $request->input('import_distribution_center_id'),
                ]);
        }

        foreach ($result['rows'] as $row) {
            $salesUnit = SalesUnit::firstOrNew([
                'distribution_center_id' => $row['distribution_center_id'],
                'code' => $row['code'],
            ]);

            if (!$salesUnit->exists) {
                $salesUnit->created_by = Auth::id();
            }

            $salesUnit->fill([
                'name' => $row['name'],
                'address' => $row['address'],
                'phone' => $row['phone'],
                'tax_code' => $row['tax_code'],
                'bank_account' => $row['bank_account'],
                'representative' => $row['representative'],
                'note' => $row['note'],
                'is_active' => $row['is_active'],
                'updated_by' => Auth::id(),
            ])->save();
        }

        Storage::delete($path);

        if ($tempToken) {
            session()->forget('sales_unit_imports.' . $tempToken);
        }

        ActivityLogger::log(
            'Đơn vị bán hàng',
            'import',
            'Import Excel đơn vị bán hàng: thêm mới ' . $result['create_count'] . ', cập nhật ' . $result['update_count']
        );

        return redirect()
            ->route('sales-units.index')
            ->with('success', 'Import thành công: thêm mới ' . $result['create_count'] . ', cập nhật ' . $result['update_count'] . ' đơn vị bán hàng.');
    }

    private function rules(Request $request, ?SalesUnit $salesUnit = null): array
    {
        return [
            'distribution_center_id' => ['required', 'exists:distribution_centers,id'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('sales_units', 'code')
                    ->where(fn ($query) => $query->where('distribution_center_id', $request->input('distribution_center_id')))
                    ->ignore($salesUnit?->id),
            ],
            'name' => ['required', 'string', 'max:500'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:100'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'bank_account' => ['nullable', 'string', 'max:1000'],
            'representative' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function messages(): array
    {
        return [
            'distribution_center_id.required' => 'Vui lòng chọn trung tâm phân phối.',
            'code.required' => 'Vui lòng nhập mã Bravo.',
            'code.unique' => 'Mã Bravo đã tồn tại trong trung tâm này.',
            'name.required' => 'Vui lòng nhập tên đơn vị bán hàng.',
        ];
    }

    private function parseSalesUnitImport(string $path, Request $request): array
    {
        $spreadsheet = IOFactory::load(Storage::path($path));
        $sheet = $spreadsheet->getActiveSheet();
        $allRows = $sheet->toArray(null, true, true, true);
        $headerIndex = null;
        $headerRow = [];

        foreach ($allRows as $index => $candidate) {
            $normalized = array_map(fn ($heading) => $this->normalizeHeading($heading), $candidate);

            if (
                in_array('ma_bravo', $normalized, true)
                || in_array('ma_dvbh', $normalized, true)
                || in_array('ma_so', $normalized, true)
            ) {
                $headerIndex = $index;
                $headerRow = $candidate;
                break;
            }
        }

        if ($headerIndex === null) {
            return [
                'rows' => [],
                'errors' => ['Không tìm thấy dòng tiêu đề cột. File cần có cột ma_bravo hoặc ma_dvbh.'],
                'duplicates' => [],
                'create_count' => 0,
                'update_count' => 0,
            ];
        }

        $rows = array_slice($allRows, array_search($headerIndex, array_keys($allRows), true) + 1, null, true);

        $headers = [];
        foreach ($headerRow as $column => $heading) {
            $headers[$column] = $this->normalizeHeading($heading);
        }

        $centers = DistributionCenter::query()
            ->get()
            ->flatMap(function (DistributionCenter $center) {
                return [
                    strtoupper((string) $center->code) => $center->id,
                    $this->normalizeLookupText($center->name) => $center->id,
                    $this->normalizeLookupText($center->code . ' - ' . $center->name) => $center->id,
                ];
            });

        $fixedCenterId = $request->filled('import_distribution_center_id')
            ? (int) $request->input('import_distribution_center_id')
            : null;

        $errors = [];
        $parsedRows = [];
        $duplicates = [];
        $createCount = 0;
        $updateCount = 0;

        foreach ($rows as $index => $rawRow) {
            $line = is_numeric($index) ? (int) $index : 0;
            $row = [];

            foreach ($rawRow as $column => $value) {
                $key = $headers[$column] ?? null;
                if ($key) {
                    $row[$key] = $this->cleanImportValue($value);
                }
            }

            if ($this->isEmptyImportRow($row)) {
                continue;
            }

            $centerId = $fixedCenterId;
            if (!$centerId) {
                $centerText = $row['ma_trung_tam'] ?? $row['ttpsf'] ?? $row['trung_tam'] ?? '';
                $centerId = $centers[strtoupper($centerText)] ?? $centers[$this->normalizeLookupText($centerText)] ?? null;
            }

            $code = $row['ma_bravo'] ?? $row['ma_dvbh'] ?? $row['ma'] ?? null;
            $name = $row['ten_dvbh'] ?? $row['ten_dvbh_in_hdong'] ?? $row['ten'] ?? null;

            if (!$centerId) {
                $errors[] = 'Dòng ' . $line . ': không xác định được trung tâm phân phối.';
            }

            if (blank($code)) {
                $errors[] = 'Dòng ' . $line . ': thiếu mã Bravo.';
            }

            if (blank($name)) {
                $errors[] = 'Dòng ' . $line . ': thiếu tên đơn vị bán hàng.';
            }

            if (!$centerId || blank($code) || blank($name)) {
                continue;
            }

            $existing = SalesUnit::where('distribution_center_id', $centerId)
                ->where('code', $code)
                ->first();

            if ($existing) {
                $updateCount++;
                $duplicates[] = [
                    'line' => $line,
                    'center' => DistributionCenter::find($centerId)?->code,
                    'code' => $code,
                    'name' => $existing->name,
                    'new_name' => $name,
                ];
            } else {
                $createCount++;
            }

            $parsedRows[] = [
                'distribution_center_id' => $centerId,
                'code' => $code,
                'name' => $name,
                'address' => $this->nullIfEmpty($row['dia_chi_cua_hang'] ?? $row['dia_chi'] ?? null),
                'phone' => $this->nullIfEmpty($row['so_dt_lien_lac'] ?? $row['dien_thoai'] ?? null),
                'tax_code' => $this->nullIfEmpty($row['ma_so_thue'] ?? $row['mst'] ?? null),
                'bank_account' => $this->nullIfEmpty($row['so_tai_khoan'] ?? $row['so_tk'] ?? null),
                'representative' => $this->nullIfEmpty($row['dai_dien_chuc_vu'] ?? $row['dai_dien'] ?? null),
                'note' => $this->nullIfEmpty($row['ghi_chu'] ?? null),
                'is_active' => $this->parseBoolean($row['dang_su_dung'] ?? $row['trang_thai'] ?? '1'),
            ];
        }

        return [
            'rows' => $parsedRows,
            'errors' => $errors,
            'duplicates' => $duplicates,
            'create_count' => $createCount,
            'update_count' => $updateCount,
        ];
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

    private function normalizeLookupText($value): string
    {
        return str((string) $value)
            ->trim()
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
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
        $value = $this->cleanImportValue($value);

        return $value === '' ? null : $value;
    }

    private function parseBoolean($value): bool
    {
        $value = mb_strtolower($this->cleanImportValue($value));

        return !in_array($value, ['0', 'no', 'false', 'ngung', 'khong'], true);
    }

    private function cleanImportValue($value): string
    {
        $value = (string) $value;
        $value = preg_replace('/[\x{00A0}\x{2007}\x{202F}]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[ \t\r\n]+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function currentDistributionCenterId(): ?int
    {
        return Auth::user()->hasRole('TrungTam')
            ? Auth::user()->distribution_center_id
            : null;
    }
}
