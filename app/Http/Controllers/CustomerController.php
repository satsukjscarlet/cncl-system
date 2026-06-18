<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Helpers\ActivityLogger;
use App\Imports\CustomersImport;
use App\Models\Customer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

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
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_code' => ['nullable', 'string', 'max:100', 'unique:customers,customer_code'],
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
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'customer_code' => ['nullable', 'string', 'max:100', 'unique:customers,customer_code,' . $customer->id],
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
            new CustomersExport(),
            'danh_muc_khach_hang_cong_trinh.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(
            new CustomersImport(),
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
}