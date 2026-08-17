<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $module,
        string $action,
        string $description,
        mixed $oldData = null,
        mixed $newData = null,
        ?Model $subject = null,
        array $extraProperties = []
    ): void {
        $logger = activity($module)->causedBy(Auth::user());

        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger
            ->withProperties(array_merge([
                'action' => $action,
                'object_type' => $subject ? class_basename($subject) : null,
                'object_id' => $subject?->getKey(),
                'object_no' => self::objectNo($subject),
                'old' => $oldData,
                'new' => $newData,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ], $extraProperties))
            ->log($description);
    }

    private static function objectNo(?Model $subject): ?string
    {
        if (!$subject) {
            return null;
        }

        foreach (['certificate_no', 'request_no', 'code', 'username', 'name'] as $attribute) {
            if (filled($subject->{$attribute} ?? null)) {
                return (string) $subject->{$attribute};
            }
        }

        return null;
    }
}
