<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            $table->string('print_template', 20)->default('single')->after('print_no');
        });
    }

    public function down(): void
    {
        Schema::table('print_logs', function (Blueprint $table) {
            $table->dropColumn('print_template');
        });
    }
};
