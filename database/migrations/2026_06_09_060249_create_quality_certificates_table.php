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
        Schema::create('quality_certificates', function (Blueprint $table) {

            $table->id();

            $table->string('certificate_no')->unique();

            $table->foreignId('certificate_request_id')
                ->constrained();

            $table->foreignId('created_by');

            $table->timestamp('signed_at')->nullable();

            $table->string('signed_by')->nullable();

            $table->string('pdf_path')->nullable();

            $table->integer('print_count')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_certificates');
    }
};
