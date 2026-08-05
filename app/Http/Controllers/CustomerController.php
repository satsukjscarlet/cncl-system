<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Helpers\ActivityLogger;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Models\DistributionCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
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

        return view('customers.index', compact('customers'));
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
        $data = $request->validate([
            'customer_code' => ['nullable', 'string', 'max:100', 'unique:customers,customer_code'],
            'distribution_center_id' => ['nullable', 'exists:distribution_centers,id'],
            'customer_name' => ['required', 'string', 'max:500'],
            'customer_address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:500'],
            'project_address' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

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

        $data = $request->validate([
            'customer_code' => ['nullable', 'string', 'max:100', 'unique:customers,customer_code,' . $customer->id],
            'distribution_center_id' => ['nullable', 'exists:distribution_centers,id'],
            'customer_name' => ['required', 'string', 'max:500'],
            'customer_address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'project_name' => ['nullable', 'string', 'max:500'],
            'project_address' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

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

        $customer->delete();

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'delete',
            'Xóa khách hàng/công trình: ' . $oldData['customer_name'],
            $oldData,
            null
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Xóa khách hàng - công trình thành công.');
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
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(
            new CustomersImport($this->currentDistributionCenterId()),
            $request->file('file')
        );

        ActivityLogger::log(
            'Khách hàng - Công trình',
            'import',
            'Import Excel danh mục khách hàng - công trình'
        );

        return redirect()
            ->route('customers.index')
            ->with('success', 'Import khách hàng - công trình thành công.');
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(
            storage_path('app/templates/template_khach_hang_cong_trinh.xlsx')
        );
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
