<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_requests', 'sent_to_ptn_at')) {
                $table->timestamp('sent_to_ptn_at')->nullable()->after('submitted_by');
            }
        });

        DB::table('certificate_requests')
            ->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING', 'COMPLETED'])
            ->whereNull('sent_to_ptn_at')
            ->update(['sent_to_ptn_at' => DB::raw('COALESCE(submitted_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_requests', 'sent_to_ptn_at')) {
                $table->dropColumn('sent_to_ptn_at');
            }
        });
    }
};
