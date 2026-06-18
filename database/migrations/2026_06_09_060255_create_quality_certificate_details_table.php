<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quality_certificate_details', function (Blueprint $table) {

            $table->id();

            $table->foreignId('quality_certificate_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained();

            $table->decimal('quantity', 18, 2);

            $table->string('nominal_size')->nullable();

            $table->text('technical_requirements')->nullable();

            $table->string('quality_standard')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_certificate_details');
    }
};
