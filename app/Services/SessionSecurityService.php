<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SessionSecurityService
{
    public function revokeUserSessions(User $user, ?string $exceptSessionId = null): int
    {
        if (!Schema::hasTable(config('session.table', 'sessions'))) {
            return 0;
        }

        $query = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id);

        if ($exceptSessionId) {
            $query->where('id', '!=', $exceptSessionId);
        }

        return $query->delete();
    }
}
