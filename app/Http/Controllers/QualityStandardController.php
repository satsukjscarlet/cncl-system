<?php

namespace App\Http\Controllers;

use App\Exports\QualityStandardsExport;
use App\Helpers\ActivityLogger;
use App\Imports\QualityStandardsImport;
use App\Models\QualityStandard;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QualityStandardController extends Controller
{
    public function index(Request $request)
    {
        $query = QualityStandard::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('description', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $standards = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quality_standards.index', compact('standards'));
    }

    public function create()
    {
        return view('quality_standards.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:quality_standards,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $standard = QualityStandard::create($data);

        ActivityLogger::log(
            'Tiêu chuẩn chất lượng',
            'create',
            'Thêm tiêu chuẩn: ' . $standard->code,
            null,
            $standard->toArray()
        );

        return redirect()
            ->route('quality-standards.index')
            ->with('success', 'Thêm tiêu chuẩn chất lượng thành công.');
    }

    public function edit(QualityStandard $qualityStandard)
    {
        return view('quality_standards.edit', [
            'standard' => $qualityStandard,
        ]);
    }

    public function update(Request $request, QualityStandard $qualityStandard)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:quality_standards,code,' . $qualityStandard->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $qualityStandard->toArray();

        $data['is_active'] = $request->boolean('is_active');

        $qualityStandard->update($data);

        ActivityLogger::log(
            'Tiêu chuẩn chất lượng',
            'update',
            'Cập nhật tiêu chuẩn: ' . $qualityStandard->code,
            $oldData,
            $qualityStandard->fresh()->toArray()
        );

        return redirect()
            ->route('quality-standards.index')
            ->with('success', 'Cập nhật tiêu chuẩn chất lượng thành công.');
    }

    public function destroy(QualityStandard $qualityStandard)
    {
        if ($qualityStandard->products()->exists()) {
            return redirect()
                ->route('quality-standards.index')
                ->with('error', 'Không thể xóa tiêu chuẩn đã được sử dụng trong danh mục sản phẩm.');
        }

        $oldData = $qualityStandard->toArray();

        $qualityStandard->delete();

        ActivityLogger::log(
            'Tiêu chuẩn chất lượng',
            'delete',
            'Xóa tiêu chuẩn: ' . $oldData['code'],
            $oldData,
            null
        );

        return redirect()
            ->route('quality-standards.index')
            ->with('success', 'Xóa tiêu chuẩn chất lượng thành công.');
    }

    public function export(): BinaryFileResponse
    {
        ActivityLogger::log(
            'Tiêu chuẩn chất lượng',
            'export',
            'Xuất Excel danh mục tiêu chuẩn chất lượng'
        );

        return Excel::download(
            new QualityStandardsExport(),
            'danh_muc_tieu_chuan_chat_luong.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(
            new QualityStandardsImport(),
            $request->file('file')
        );

        ActivityLogger::log(
            'Tiêu chuẩn chất lượng',
            'import',
            'Import Excel danh mục tiêu chuẩn chất lượng'
        );

        return redirect()
            ->route('quality-standards.index')
            ->with('success', 'Import tiêu chuẩn chất lượng thành công.');
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(
            storage_path('app/templates/template_tieu_chuan_chat_luong.xlsx')
        );
    }
}