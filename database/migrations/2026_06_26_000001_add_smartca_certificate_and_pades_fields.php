<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->longText('smartca_certificate_data')->nullable()->after('smartca_data_hash');
            $table->json('smartca_chain_data')->nullable()->after('smartca_certificate_data');
            $table->string('smartca_certificate_serial')->nullable()->after('smartca_chain_data');
            $table->string('pades_status')->nullable()->after('smartca_completed_at');
            $table->text('pades_error')->nullable()->after('pades_status');
        });
    }

    public function down(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropColumn([
                'smartca_certificate_data',
                'smartca_chain_data',
                'smartca_certificate_serial',
                'pades_status',
                'pades_error',
            ]);
        });
    }
};
