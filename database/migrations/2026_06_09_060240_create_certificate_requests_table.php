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
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_no')->unique();

            $table->foreignId('distribution_center_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('delivery_date')->nullable();

            $table->string('invoice_no')->nullable();

            $table->boolean('require_hard_copy')->default(false);
            $table->integer('hard_copy_quantity')->default(0);

            $table->text('note')->nullable();

            $table->enum('status', [
                'DRAFT',
                'WAIT_DVKH',
                'WAIT_PTN',
                'PTN_PROCESSING',
                'SIGNED',
                'COMPLETED',
                'CANCELLED',
            ])->default('DRAFT');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_requests');
    }
};
