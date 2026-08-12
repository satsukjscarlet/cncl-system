<?php

namespace App\Http\Controllers;

use App\Services\WorkQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WorkQueueController extends Controller
{
    public function feed(Request $request, WorkQueueService $workQueueService)
    {
        $user = $request->user();
        $cacheKey = 'work_queue:user:' . $user->id;

        $workQueue = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($workQueueService, $user) {
            return $workQueueService->forUser($user);
        });

        $total = $workQueue['total'];
        $items = collect($workQueue['items'])
            ->filter(fn ($item) => $item['count'] > 0)
            ->values();

        return response()->json([
            'label' => $total > 0 ? ($total > 99 ? '99+' : $total) : '',
            'label_color' => $total > 0 ? 'danger' : 'secondary',
            'icon_color' => $total > 0 ? 'primary' : 'muted',
            'dropdown' => view('work_queue.dropdown', compact('items', 'total'))->render(),
        ]);
    }
}
