<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_groups', function (Blueprint $table) {
            $table->foreignId('default_quality_standard_id')->nullable()
                ->constrained('quality_standards')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_quality_standard_id');
        });
    }
};
