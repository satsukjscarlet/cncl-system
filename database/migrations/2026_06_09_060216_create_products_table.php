<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_group_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('quality_standard_id')
                ->nullable()
                ->constrained('quality_standards')
                ->nullOnDelete();

            $table->string('product_code', 255)->unique();
            $table->string('product_name', 500);

            $table->string('unit', 100)->nullable();
            $table->string('nominal_size', 255)->nullable();

            $table->longText('technical_requirements')->nullable();

            $table->string('certificate_type', 100)->nullable();
            $table->string('certificate_template', 255)->nullable();

            $table->longText('note')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('product_code');
            $table->index('product_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};