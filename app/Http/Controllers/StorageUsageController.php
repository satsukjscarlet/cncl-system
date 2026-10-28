<?php

namespace App\Http\Controllers;

use App\Services\StorageUsageService;
use Illuminate\Support\Facades\Cache;

class StorageUsageController extends Controller
{
    public function index()
    {
        return response()->view('reports.storage_usage')->header('Cache-Control', 'no-store');
    }

    public function scan(StorageUsageService $service)
    {
        $lock = Cache::lock('storage-usage-scan', 120);
        if (!$lock->get()) {
            return response()->json(['message' => 'Đang có lượt quét khác. Vui lòng thử lại sau.'], 409);
        }
        try {
            $data = $service->scan();
            try {
                $data['history'] = app(\App\Services\StorageUsageHistoryService::class)->record($data);
            } catch (\Throwable $e) {
                report($e);
                $data['history'] = null;
                $data['warnings'][] = 'Không lưu được lịch sử dung lượng. Thống kê hiện tại vẫn hiển thị; vui lòng kiểm tra quyền ghi thư mục system-metrics.';
            }
            return response()->json($data)->header('Cache-Control', 'no-store');
        } finally {
            $lock->release();
        }
    }
}
