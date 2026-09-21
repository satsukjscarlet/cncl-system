<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->string('last_returned_from', 50)->nullable()->after('note');
            $table->string('last_returned_to', 50)->nullable()->after('last_returned_from');
            $table->text('last_return_reason')->nullable()->after('last_returned_to');
            $table->timestamp('last_returned_at')->nullable()->after('last_return_reason');
            $table->foreignId('last_returned_by')->nullable()->after('last_returned_at')->constrained('users')->nullOnDelete();
            $table->index(['status', 'last_returned_to'], 'cert_requests_status_returned_to_idx');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropIndex('cert_requests_status_returned_to_idx');
            $table->dropConstrainedForeignId('last_returned_by');
            $table->dropColumn([
                'last_returned_from',
                'last_returned_to',
                'last_return_reason',
                'last_returned_at',
            ]);
        });
    }
};
