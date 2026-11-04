<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'work_department')) {
                $table->string('work_department', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'work_report_scope')) {
                $table->string('work_report_scope', 20)->nullable();
            }
            if (! Schema::hasColumn('users', 'work_report_departments')) {
                $table->json('work_report_departments')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['work_department', 'work_report_scope', 'work_report_departments']);
        });
    }
};
