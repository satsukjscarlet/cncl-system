<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionSecurityService
{
    public function revokeUserSessions(User $user, ?string $exceptSessionId = null): int
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        $connection = config('session.connection');
        $table = config('session.table', 'sessions');
        if (!Schema::connection($connection)->hasTable($table)) {
            return 0;
        }

        $query = DB::connection($connection)->table($table)
            ->where('user_id', $user->id);

        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }
}
