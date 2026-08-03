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
            $table->string('request_type', 30)->default('NORMAL')->after('request_no');
            $table->foreignId('reissue_of_certificate_id')
                ->nullable()
                ->after('request_type')
                ->constrained('quality_certificates')
                ->nullOnDelete();
            $table->text('reissue_reason')->nullable()->after('reissue_of_certificate_id');
        });

        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->string('status', 30)->default('DRAFT')->after('certificate_no');
            $table->foreignId('replaces_certificate_id')
                ->nullable()
                ->after('certificate_request_id')
                ->constrained('quality_certificates')
                ->nullOnDelete();
            $table->foreignId('replaced_by_certificate_id')
                ->nullable()
                ->after('replaces_certificate_id')
                ->constrained('quality_certificates')
                ->nullOnDelete();
            $table->timestamp('revoked_at')->nullable()->after('print_count');
            $table->foreignId('revoked_by')
                ->nullable()
                ->after('revoked_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('revoked_reason')->nullable()->after('revoked_by');
        });

        DB::table('quality_certificates')
            ->whereNotNull('signed_at')
            ->update(['status' => 'ISSUED']);
    }

    public function down(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revoked_by');
            $table->dropColumn(['revoked_at', 'revoked_reason']);
            $table->dropConstrainedForeignId('replaced_by_certificate_id');
            $table->dropConstrainedForeignId('replaces_certificate_id');
            $table->dropColumn('status');
        });

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reissue_of_certificate_id');
            $table->dropColumn(['request_type', 'reissue_reason']);
        });
    }
};
