<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_configs', function (Blueprint $table) {
            $table->id();

            $table->string('code', 100)->unique();
            $table->string('name', 255);

            $table->string('process_step', 100);

            $table->integer('warning_minutes')->default(0);
            $table->integer('limit_minutes')->default(0);

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('process_step');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_configs');
    }
};