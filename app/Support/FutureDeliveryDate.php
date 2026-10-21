<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class FutureDeliveryDate
{
    public static function isFuture(mixed $date): bool
    {
        if (!is_string($date) || Validator::make(['date' => $date], ['date' => ['required', 'date']])->fails()) {
            return false;
        }

        return Carbon::parse($date)->toDateString() > today()->toDateString();
    }

}
