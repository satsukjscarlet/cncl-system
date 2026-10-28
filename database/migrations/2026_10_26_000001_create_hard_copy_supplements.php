<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hard_copy_supplements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_certificate_id')->constrained()->restrictOnDelete();
            // Nullable unique slot prevents concurrent open requests for the same certificate.
            $table->foreignId('active_certificate_id')->nullable()->unique()->constrained('quality_certificates')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('reason');
            $table->text('note')->nullable();
            $table->string('status', 20)->index();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at')->nullable()->index();
            $table->string('print_template', 10)->nullable();
            $table->unsignedInteger('page_count')->nullable();
            $table->text('completion_note')->nullable();
            $table->timestamps();
            $table->index(['quality_certificate_id', 'status']);
        });
        Schema::create('hard_copy_supplement_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hard_copy_supplement_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->json('data');
            $table->timestamps();
        });
        Schema::table('print_logs', function (Blueprint $table) {
            $table->foreignId('hard_copy_supplement_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', fn (Blueprint $table) => $table->dropConstrainedForeignId('hard_copy_supplement_id'));
        Schema::dropIfExists('hard_copy_supplement_events');
        Schema::dropIfExists('hard_copy_supplements');
    }
};
