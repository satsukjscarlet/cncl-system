<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_center_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('name', 500);
            $table->text('address')->nullable();
            $table->string('phone', 100)->nullable();
            $table->string('tax_code', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('representative', 255)->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['distribution_center_id', 'code'], 'sales_units_center_code_unique');
            $table->index(['distribution_center_id', 'is_active'], 'sales_units_center_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_units');
    }
};
