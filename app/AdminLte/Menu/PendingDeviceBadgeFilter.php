<?php

namespace App\AdminLte\Menu;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class PendingDeviceBadgeFilter implements FilterInterface
{
    public function transform($item)
    {
        if (($item['url'] ?? null) !== 'user-devices') {
            return $item;
        }

        $user = Auth::user();

        if (!$user || !$user->can('device.manage')) {
            return $item;
        }

        $pendingCount = UserDevice::where('status', UserDevice::STATUS_PENDING)->count();

        if ($pendingCount > 0) {
            $item['label'] = $pendingCount > 99 ? '99+' : (string) $pendingCount;
            $item['label_color'] = 'warning';
        }

        return $item;
    }
}
