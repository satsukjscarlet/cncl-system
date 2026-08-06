<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('distribution_center_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('customer_code', 100)->nullable()->unique();
            $table->string('customer_name', 500);

            $table->text('customer_address')->nullable();

            $table->string('tax_code', 100)->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('email', 255)->nullable();

            $table->string('project_name', 500)->nullable();
            $table->text('project_address')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index('customer_code');
            $table->index('distribution_center_id');
            $table->index('customer_name');
            $table->index('project_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
