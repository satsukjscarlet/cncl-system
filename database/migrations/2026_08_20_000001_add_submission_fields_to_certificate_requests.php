<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('certificate_requests', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('certificate_requests', 'submitted_by')) {
                $table->foreignId('submitted_by')
                    ->nullable()
                    ->after('submitted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (Schema::hasColumn('certificate_requests', 'submitted_by')) {
                $table->dropConstrainedForeignId('submitted_by');
            }

            if (Schema::hasColumn('certificate_requests', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });
    }
};
