<?php

namespace App\AdminLte\Menu;

use App\Services\HardCopySupplementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class PendingSupplementBadgeFilter implements FilterInterface
{
    public function transform($item)
    {
        if (($item['url'] ?? null) !== 'hard-copy-supplements') return $item;
        $user = Auth::user();
        if (!$user || $user->hasRole('TrungTam')) return $item;
        $service = app(HardCopySupplementService::class);
        if (!$user->hasRole('Admin') && !$service->dvkh($user) && !$service->ptn($user)) return $item;
        // Deploying code before the additive migration must not break unrelated pages.
        if (!Schema::hasTable('hard_copy_supplements')) return $item;
        $count = $service->pendingQuery($user)->count();
        $item['url'] = 'hard-copy-supplements?work_queue=1';
        if ($count > 0) {
            $item['label'] = $count > 99 ? '99+' : (string) $count;
            $item['label_color'] = 'warning';
        }
        return $item;
    }
}
