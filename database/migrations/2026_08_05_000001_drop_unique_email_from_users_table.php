<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if ($this->hasIndex('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_email_unique');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (!$this->hasIndex('users', 'users_email_unique')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unique('email', 'users_email_unique');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
