<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'sales_unit_id')) {
                $table->foreignId('sales_unit_id')->nullable()->constrained('sales_units')->restrictOnDelete();
            }
        });
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'sales_unit_id')) {
                $table->foreignId('sales_unit_id')->nullable()->constrained('sales_units')->restrictOnDelete();
            }
        });
        Schema::table('certificate_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('certificate_requests', 'request_source')) {
                $table->string('request_source', 20)->nullable();
            }
            if (! Schema::hasColumn('certificate_requests', 'sales_unit_submitted_at')) {
                $table->dateTime('sales_unit_submitted_at')->nullable();
            }
            if (! Schema::hasColumn('certificate_requests', 'sales_unit_submitted_by')) {
                $table->unsignedBigInteger('sales_unit_submitted_by')->nullable();
            }
            if (! Schema::hasColumn('certificate_requests', 'center_reviewed_at')) {
                $table->dateTime('center_reviewed_at')->nullable();
            }
            if (! Schema::hasColumn('certificate_requests', 'center_reviewed_by')) {
                $table->unsignedBigInteger('center_reviewed_by')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', fn (Blueprint $table) => $table->dropColumn(['request_source', 'sales_unit_submitted_at', 'sales_unit_submitted_by', 'center_reviewed_at', 'center_reviewed_by']));
        Schema::table('customers', fn (Blueprint $table) => $table->dropConstrainedForeignId('sales_unit_id'));
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('sales_unit_id'));
    }
};
