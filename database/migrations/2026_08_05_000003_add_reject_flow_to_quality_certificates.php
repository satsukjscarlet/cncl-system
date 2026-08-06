<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->timestamp('rejected_at')->nullable()->after('revoked_reason');
            $table->foreignId('rejected_by')
                ->nullable()
                ->after('rejected_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('rejected_to', 20)->nullable()->after('rejected_by');
            $table->text('rejected_reason')->nullable()->after('rejected_to');
        });
    }

    public function down(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['rejected_at', 'rejected_to', 'rejected_reason']);
        });
    }
};
