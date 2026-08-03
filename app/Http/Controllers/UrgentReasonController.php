<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\UrgentReason;
use Illuminate\Http\Request;

class UrgentReasonController extends Controller
{
    public function index(Request $request)
    {
        $query = UrgentReason::query();

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

        $urgentReasons = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('urgent_reasons.index', compact('urgentReasons'));
    }

    public function create()
    {
        return view('urgent_reasons.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:urgent_reasons,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $urgentReason = UrgentReason::create($data);

        ActivityLogger::log(
            'Danh mục lý do gấp',
            'create',
            'Thêm lý do gấp: ' . $urgentReason->code,
            null,
            $urgentReason->toArray()
        );

        return redirect()
            ->route('urgent-reasons.index')
            ->with('success', 'Thêm lý do gấp thành công.');
    }

    public function edit(UrgentReason $urgentReason)
    {
        return view('urgent_reasons.edit', compact('urgentReason'));
    }

    public function update(Request $request, UrgentReason $urgentReason)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:urgent_reasons,code,' . $urgentReason->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $urgentReason->toArray();
        $data['is_active'] = $request->boolean('is_active');

        $urgentReason->update($data);

        ActivityLogger::log(
            'Danh mục lý do gấp',
            'update',
            'Cập nhật lý do gấp: ' . $urgentReason->code,
            $oldData,
            $urgentReason->fresh()->toArray()
        );

        return redirect()
            ->route('urgent-reasons.index')
            ->with('success', 'Cập nhật lý do gấp thành công.');
    }

    public function destroy(UrgentReason $urgentReason)
    {
        if ($urgentReason->certificateRequests()->exists()) {
            return redirect()
                ->route('urgent-reasons.index')
                ->with('error', 'Không thể xóa lý do gấp đã được sử dụng trong yêu cầu cấp phiếu.');
        }

        $oldData = $urgentReason->toArray();
        $urgentReason->delete();

        ActivityLogger::log(
            'Danh mục lý do gấp',
            'delete',
            'Xóa lý do gấp: ' . $oldData['code'],
            $oldData,
            null
        );

        return redirect()
            ->route('urgent-reasons.index')
            ->with('success', 'Xóa lý do gấp thành công.');
    }
}
