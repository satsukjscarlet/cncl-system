<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductUnit::query()
            ->withCount([
                'products as products_total_count' => fn ($query) => $query->withTrashed(),
            ]);

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', '%' . $keyword . '%')
                    ->orWhere('name', 'like', '%' . $keyword . '%')
                    ->orWhere('description', 'like', '%' . $keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        [$sort, $direction] = $this->sortInput($request, ['code', 'name', 'is_active', 'created_at']);

        $units = $query
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('product_units.index', compact('units'));
    }

    public function create()
    {
        return view('product_units.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_units,code'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $unit = ProductUnit::create($data);

        ActivityLogger::log(
            'Đơn vị tính',
            'create',
            'Thêm đơn vị tính: ' . $unit->code . ' - ' . $unit->name,
            null,
            $unit->toArray(),
            $unit
        );

        return redirect()
            ->route('product-units.index')
            ->with('success', 'Thêm đơn vị tính thành công.');
    }

    public function edit(ProductUnit $productUnit)
    {
        return view('product_units.edit', [
            'unit' => $productUnit,
        ]);
    }

    public function update(Request $request, ProductUnit $productUnit)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_units,code,' . $productUnit->id],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $productUnit->toArray();
        $data['is_active'] = $request->boolean('is_active');

        $productUnit->update($data);

        Product::where('unit_id', $productUnit->id)->update([
            'unit' => $productUnit->name,
        ]);

        ActivityLogger::log(
            'Đơn vị tính',
            'update',
            'Cập nhật đơn vị tính: ' . $productUnit->code . ' - ' . $productUnit->name,
            $oldData,
            $productUnit->fresh()->toArray(),
            $productUnit
        );

        return redirect()
            ->route('product-units.index')
            ->with('success', 'Cập nhật đơn vị tính thành công.');
    }

    public function destroy(ProductUnit $productUnit)
    {
        $oldData = $productUnit->toArray();

        $productUnit->update(['is_active' => false]);

        ActivityLogger::log(
            'Đơn vị tính',
            'delete',
            'Ngừng sử dụng đơn vị tính: ' . $oldData['code'] . ' - ' . $oldData['name'],
            $oldData,
            $productUnit->fresh()->toArray(),
            $productUnit
        );

        return redirect()
            ->route('product-units.index')
            ->with('success', 'Đã ngừng sử dụng đơn vị tính.');
    }

    public function forceDestroy(ProductUnit $productUnit)
    {
        abort_unless(Auth::user()?->hasRole('Admin'), 403);

        if (Product::withTrashed()->where('unit_id', $productUnit->id)->exists()) {
            return redirect()
                ->route('product-units.index')
                ->with('error', 'Không thể xóa hẳn đơn vị tính đã có sản phẩm liên kết.');
        }

        $oldData = $productUnit->toArray();
        $productUnit->forceDelete();

        ActivityLogger::log(
            'Đơn vị tính',
            'force_delete',
            'Xóa hẳn đơn vị tính: ' . ($oldData['code'] ?? '') . ' - ' . ($oldData['name'] ?? ''),
            $oldData,
            null
        );

        return redirect()
            ->route('product-units.index')
            ->with('success', 'Đã xóa hẳn đơn vị tính chưa phát sinh liên kết.');
    }
}
