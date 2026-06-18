<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('login_logs', 'status')) {
                $table->string('status')->default('success')->after('user_agent');
            }

            if (! Schema::hasColumn('login_logs', 'message')) {
                $table->string('message')->nullable()->after('status');
            }

            if (! Schema::hasColumn('login_logs', 'logged_at')) {
                $table->timestamp('logged_at')->nullable()->after('message');
            }
        });

        if (Schema::hasColumn('login_logs', 'is_success')) {
            DB::table('login_logs')->update([
                'status' => DB::raw("CASE WHEN is_success = 1 THEN 'success' ELSE 'failed' END"),
            ]);
        }

        if (Schema::hasColumn('login_logs', 'note')) {
            DB::table('login_logs')->whereNull('message')->update([
                'message' => DB::raw('note'),
            ]);
        }

        if (Schema::hasColumn('login_logs', 'login_at')) {
            DB::table('login_logs')->whereNull('logged_at')->update([
                'logged_at' => DB::raw('login_at'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('login_logs', function (Blueprint $table) {
            if (Schema::hasColumn('login_logs', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('login_logs', 'message')) {
                $table->dropColumn('message');
            }

            if (Schema::hasColumn('login_logs', 'logged_at')) {
                $table->dropColumn('logged_at');
            }
        });
    }
};
