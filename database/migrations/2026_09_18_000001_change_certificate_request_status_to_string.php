<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE certificate_requests MODIFY status VARCHAR(30) NOT NULL DEFAULT 'DRAFT'");
        }
    }

    public function down(): void
    {
        // Keep status as string. Current workflow statuses are broader than the original enum.
    }
};
