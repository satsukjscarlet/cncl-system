<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('customers', 'distribution_center_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('distribution_center_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->nullOnDelete();

                $table->index('distribution_center_id', 'customers_distribution_center_id_idx');
            });
        }

        foreach (DB::table('customers')
            ->whereNull('distribution_center_id')
            ->orderBy('id')
            ->cursor() as $customer) {
            $centerId = DB::table('certificate_requests')
                ->where('customer_id', $customer->id)
                ->whereNotNull('distribution_center_id')
                ->orderByDesc('created_at')
                ->value('distribution_center_id');

            if ($centerId) {
                DB::table('customers')
                    ->where('id', $customer->id)
                    ->update(['distribution_center_id' => $centerId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'distribution_center_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('customers_distribution_center_id_idx');
                $table->dropConstrainedForeignId('distribution_center_id');
            });
        }
    }
};
