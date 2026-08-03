<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->string('pades_prepared_pdf_path')->nullable()->after('pades_status');
            $table->string('pades_state_path')->nullable()->after('pades_prepared_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropColumn([
                'pades_prepared_pdf_path',
                'pades_state_path',
            ]);
        });
    }
};
