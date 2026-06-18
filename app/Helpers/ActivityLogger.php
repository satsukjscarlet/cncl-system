<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $module,
        string $action,
        string $description,
        mixed $oldData = null,
        mixed $newData = null
    ): void {
        activity($module)
            ->causedBy(Auth::user())
            ->withProperties([
                'action' => $action,
                'old' => $oldData,
                'new' => $newData,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ])
            ->log($description);
    }
}