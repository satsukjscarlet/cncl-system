<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\DistributionCenter;
use Illuminate\Http\Request;

class DistributionCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = DistributionCenter::query();

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->keyword . '%')
                    ->orWhere('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%')
                    ->orWhere('phone', 'like', '%' . $request->keyword . '%')
                    ->orWhere('contact_person', 'like', '%' . $request->keyword . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $centers = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('distribution_centers.index', compact('centers'));
    }

    public function create()
    {
        return view('distribution_centers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:distribution_centers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $center = DistributionCenter::create($data);

        ActivityLogger::log(
            'Trung tâm phân phối',
            'create',
            'Thêm mới trung tâm: ' . $center->name,
            null,
            $center->toArray()
        );

        return redirect()
            ->route('distribution-centers.index')
            ->with('success', 'Thêm trung tâm phân phối thành công.');
    }

    public function edit(DistributionCenter $distributionCenter)
    {
        return view('distribution_centers.edit', [
            'center' => $distributionCenter,
        ]);
    }

    public function update(Request $request, DistributionCenter $distributionCenter)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:distribution_centers,code,' . $distributionCenter->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable'],
        ]);

        $oldData = $distributionCenter->toArray();

        $data['is_active'] = $request->boolean('is_active');

        $distributionCenter->update($data);

        ActivityLogger::log(
            'Trung tâm phân phối',
            'update',
            'Cập nhật trung tâm: ' . $distributionCenter->name,
            $oldData,
            $distributionCenter->fresh()->toArray()
        );

        return redirect()
            ->route('distribution-centers.index')
            ->with('success', 'Cập nhật trung tâm phân phối thành công.');
    }

    public function destroy(DistributionCenter $distributionCenter)
    {
        if ($distributionCenter->users()->exists() || $distributionCenter->certificateRequests()->exists()) {
            return redirect()
                ->route('distribution-centers.index')
                ->with('error', 'Không thể xóa trung tâm đã được gán người dùng hoặc đã phát sinh yêu cầu cấp phiếu.');
        }

        $oldData = $distributionCenter->toArray();

        $distributionCenter->delete();

        ActivityLogger::log(
            'Trung tâm phân phối',
            'delete',
            'Xóa trung tâm: ' . $oldData['name'],
            $oldData,
            null
        );

        return redirect()
            ->route('distribution-centers.index')
            ->with('success', 'Xóa trung tâm phân phối thành công.');
    }
}
