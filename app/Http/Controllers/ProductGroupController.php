<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\ProductGroup;
use Illuminate\Http\Request;
use App\Exports\ProductGroupsExport;
use App\Imports\ProductGroupsImport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductGroup::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('name', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $groups = $query->latest()->paginate(10)->withQueryString();

        return view('product_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('product_groups.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_groups,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $group = ProductGroup::create($data);

        ActivityLogger::log(
            'Nhóm sản phẩm',
            'create',
            'Thêm nhóm sản phẩm: ' . $group->name,
            null,
            $group->toArray()
        );

        return redirect()
            ->route('product-groups.index')
            ->with('success', 'Thêm nhóm sản phẩm thành công.');
    }

    public function edit(ProductGroup $productGroup)
    {
        return view('product_groups.edit', [
            'group' => $productGroup,
        ]);
    }

    public function update(Request $request, ProductGroup $productGroup)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_groups,code,' . $productGroup->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $productGroup->toArray();

        $data['is_active'] = $request->boolean('is_active');

        $productGroup->update($data);

        ActivityLogger::log(
            'Nhóm sản phẩm',
            'update',
            'Cập nhật nhóm sản phẩm: ' . $productGroup->name,
            $oldData,
            $productGroup->fresh()->toArray()
        );

        return redirect()
            ->route('product-groups.index')
            ->with('success', 'Cập nhật nhóm sản phẩm thành công.');
    }

    public function destroy(ProductGroup $productGroup)
    {
        if ($productGroup->products()->exists()) {
            return redirect()
                ->route('product-groups.index')
                ->with('error', 'Không thể xóa nhóm sản phẩm đã có sản phẩm.');
        }

        $oldData = $productGroup->toArray();

        $productGroup->delete();

        ActivityLogger::log(
            'Nhóm sản phẩm',
            'delete',
            'Xóa nhóm sản phẩm: ' . $oldData['name'],
            $oldData,
            null
        );

        return redirect()
            ->route('product-groups.index')
            ->with('success', 'Xóa nhóm sản phẩm thành công.');
    }
    public function export(): BinaryFileResponse
    {
        ActivityLogger::log('Nhóm sản phẩm', 'export', 'Xuất Excel nhóm sản phẩm');

        return Excel::download(new ProductGroupsExport, 'danh_muc_nhom_san_pham.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(new ProductGroupsImport, $request->file('file'));

        ActivityLogger::log('Nhóm sản phẩm', 'import', 'Import Excel nhóm sản phẩm');

        return redirect()
            ->route('product-groups.index')
            ->with('success', 'Import nhóm sản phẩm thành công.');
    }

    public function template(): BinaryFileResponse
    {
        $file = storage_path('app/templates/template_nhom_san_pham.xlsx');

        return response()->download($file);
    }
}
