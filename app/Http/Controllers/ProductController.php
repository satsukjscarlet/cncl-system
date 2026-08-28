<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Helpers\ActivityLogger;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityStandard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['group', 'qualityStandard']);

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('product_code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('product_name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('nominal_size', 'like', '%' . $request->keyword . '%')
                    ->orWhere('technical_requirements', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('product_group_id')) {
            $query->where('product_group_id', $request->product_group_id);
        }

        if ($request->filled('quality_standard_id')) {
            $query->where('quality_standard_id', $request->quality_standard_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $products = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $groups = ProductGroup::where('is_active', true)->orderBy('name')->get();
        $standards = QualityStandard::where('is_active', true)->orderBy('name')->get();

        return view('products.index', compact('products', 'groups', 'standards'));
    }

    public function create()
    {
        $groups = ProductGroup::where('is_active', true)->orderBy('name')->get();
        $standards = QualityStandard::where('is_active', true)->orderBy('name')->get();

        return view('products.create', compact('groups', 'standards'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_group_id' => ['required', 'exists:product_groups,id'],
            'quality_standard_id' => ['nullable', 'exists:quality_standards,id'],
            'product_code' => ['required', 'string', 'max:255', 'unique:products,product_code'],
            'product_name' => ['required', 'string', 'max:500'],
            'unit' => ['nullable', 'string', 'max:100'],
            'nominal_size' => ['nullable', 'string', 'max:255'],
            'technical_requirements' => ['nullable', 'string'],
            'certificate_type' => ['nullable', 'string', 'max:100'],
            'certificate_template' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['certificate_type'] = $data['certificate_type'] ?: 'CNCL';
        $data['certificate_template'] = $data['certificate_template'] ?: 'default';

        $product = Product::create($data);

        ActivityLogger::log(
            'Sản phẩm',
            'create',
            'Thêm sản phẩm: ' . $product->product_code . ' - ' . $product->product_name,
            null,
            $product->toArray(),
            $product
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Thêm sản phẩm thành công.');
    }

    public function edit(Product $product)
    {
        $groups = ProductGroup::where('is_active', true)->orderBy('name')->get();
        $standards = QualityStandard::where('is_active', true)->orderBy('name')->get();

        return view('products.edit', compact('product', 'groups', 'standards'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'product_group_id' => ['required', 'exists:product_groups,id'],
            'quality_standard_id' => ['nullable', 'exists:quality_standards,id'],
            'product_code' => ['required', 'string', 'max:255', 'unique:products,product_code,' . $product->id],
            'product_name' => ['required', 'string', 'max:500'],
            'unit' => ['nullable', 'string', 'max:100'],
            'nominal_size' => ['nullable', 'string', 'max:255'],
            'technical_requirements' => ['nullable', 'string'],
            'certificate_type' => ['nullable', 'string', 'max:100'],
            'certificate_template' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $product->toArray();
        $data['is_active'] = $request->boolean('is_active');
        $data['certificate_type'] = $data['certificate_type'] ?: ($product->certificate_type ?: 'CNCL');
        $data['certificate_template'] = $data['certificate_template'] ?: ($product->certificate_template ?: 'default');

        $product->update($data);
        $product->refresh();

        ActivityLogger::log(
            'Sản phẩm',
            'update',
            'Cập nhật sản phẩm: ' . $product->product_code . ' - ' . $product->product_name,
            $oldData,
            $product->toArray(),
            $product
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Cập nhật sản phẩm thành công.');
    }

    public function destroy(Product $product)
    {
        $oldData = $product->toArray();

        $product->update(['is_active' => false]);
        $product->refresh();

        ActivityLogger::log(
            'Sản phẩm',
            'delete',
            'Ngừng sử dụng sản phẩm: ' . $oldData['product_code'] . ' - ' . $oldData['product_name'],
            $oldData,
            $product->toArray(),
            $product
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Đã ngừng sử dụng sản phẩm.');
    }

    public function export(): BinaryFileResponse
    {
        ActivityLogger::log(
            'Sản phẩm',
            'export',
            'Xuất Excel danh mục sản phẩm'
        );

        return Excel::download(new ProductsExport(), 'danh_muc_san_pham.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);

        $path = $request->file('file')->store('imports');
        $fullPath = Storage::path($path);

        try {
            $exitCode = Artisan::call('products:import-file', [
                'file' => $fullPath,
                '--memory' => '1024M',
                '--timeout' => 0,
                '--no-progress' => true,
            ]);
            $output = trim(Artisan::output());
        } finally {
            Storage::delete($path);
        }

        if ($exitCode !== 0) {
            return redirect()
                ->route('products.index')
                ->with('error', 'Import danh mục sản phẩm thất bại. ' . $output);
        }

        ActivityLogger::log(
            'Sản phẩm',
            'import',
            'Import Excel danh mục sản phẩm. ' . $output
        );

        return redirect()
            ->route('products.index')
            ->with('success', 'Import danh mục sản phẩm thành công. ' . $output);
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(storage_path('app/templates/template_san_pham.xlsx'));
    }
}
