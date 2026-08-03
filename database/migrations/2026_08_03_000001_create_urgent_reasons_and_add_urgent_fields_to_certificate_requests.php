<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgent_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->boolean('is_urgent')->default(false)->after('hard_copy_quantity');
            $table->foreignId('urgent_reason_id')
                ->nullable()
                ->after('is_urgent')
                ->constrained('urgent_reasons')
                ->nullOnDelete();
            $table->string('requester_name')->nullable()->after('urgent_reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('urgent_reason_id');
            $table->dropColumn(['is_urgent', 'requester_name']);
        });

        Schema::dropIfExists('urgent_reasons');
    }
};
