<?php

namespace App\Http\Controllers;

use App\Exports\SlaConfigsExport;
use App\Helpers\ActivityLogger;
use App\Imports\SlaConfigsImport;
use App\Models\SlaConfig;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SlaConfigController extends Controller
{
    public function index(Request $request)
    {
        $query = SlaConfig::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('description', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('process_step')) {
            $query->where('process_step', $request->process_step);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $slaConfigs = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $processSteps = SlaConfig::processStepOptions();

        return view('sla_configs.index', compact('slaConfigs', 'processSteps'));
    }

    public function create()
    {
        $processSteps = SlaConfig::processStepOptions();

        return view('sla_configs.create', compact('processSteps'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:sla_configs,code'],
            'name' => ['required', 'string', 'max:255'],
            'process_step' => ['required', 'string', 'max:100'],
            'warning_minutes' => ['required', 'integer', 'min:0'],
            'limit_minutes' => ['required', 'integer', 'min:1', 'gte:warning_minutes'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $sla = SlaConfig::create($data);

        ActivityLogger::log(
            'Cấu hình SLA',
            'create',
            'Thêm cấu hình SLA: ' . $sla->code,
            null,
            $sla->toArray()
        );

        return redirect()
            ->route('sla-configs.index')
            ->with('success', 'Thêm cấu hình SLA thành công.');
    }

    public function edit(SlaConfig $slaConfig)
    {
        $processSteps = SlaConfig::processStepOptions();

        return view('sla_configs.edit', compact('slaConfig', 'processSteps'));
    }

    public function update(Request $request, SlaConfig $slaConfig)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:sla_configs,code,' . $slaConfig->id],
            'name' => ['required', 'string', 'max:255'],
            'process_step' => ['required', 'string', 'max:100'],
            'warning_minutes' => ['required', 'integer', 'min:0'],
            'limit_minutes' => ['required', 'integer', 'min:1', 'gte:warning_minutes'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $slaConfig->toArray();

        $data['is_active'] = $request->boolean('is_active');

        $slaConfig->update($data);

        ActivityLogger::log(
            'Cấu hình SLA',
            'update',
            'Cập nhật cấu hình SLA: ' . $slaConfig->code,
            $oldData,
            $slaConfig->fresh()->toArray()
        );

        return redirect()
            ->route('sla-configs.index')
            ->with('success', 'Cập nhật cấu hình SLA thành công.');
    }

    public function destroy(SlaConfig $slaConfig)
    {
        $oldData = $slaConfig->toArray();

        $slaConfig->delete();

        ActivityLogger::log(
            'Cấu hình SLA',
            'delete',
            'Xóa cấu hình SLA: ' . $oldData['code'],
            $oldData,
            null
        );

        return redirect()
            ->route('sla-configs.index')
            ->with('success', 'Xóa cấu hình SLA thành công.');
    }

    public function export(): BinaryFileResponse
    {
        ActivityLogger::log(
            'Cấu hình SLA',
            'export',
            'Xuất Excel cấu hình SLA'
        );

        return Excel::download(
            new SlaConfigsExport(),
            'cau_hinh_sla.xlsx'
        );
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        Excel::import(
            new SlaConfigsImport(),
            $request->file('file')
        );

        ActivityLogger::log(
            'Cấu hình SLA',
            'import',
            'Import Excel cấu hình SLA'
        );

        return redirect()
            ->route('sla-configs.index')
            ->with('success', 'Import cấu hình SLA thành công.');
    }

    public function template(): BinaryFileResponse
    {
        return response()->download(
            storage_path('app/templates/template_sla.xlsx')
        );
    }
}