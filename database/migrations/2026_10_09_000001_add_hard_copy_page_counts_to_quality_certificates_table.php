<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->unsignedSmallInteger('hard_copy_single_page_count')->nullable()->after('print_count');
            $table->unsignedSmallInteger('hard_copy_batch_page_count')->nullable()->after('hard_copy_single_page_count');
        });
    }

    public function down(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropColumn([
                'hard_copy_single_page_count',
                'hard_copy_batch_page_count',
            ]);
        });
    }
};
