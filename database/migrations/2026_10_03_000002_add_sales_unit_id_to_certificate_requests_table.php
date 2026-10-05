<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->foreignId('sales_unit_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('sales_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_unit_id');
        });
    }
};
