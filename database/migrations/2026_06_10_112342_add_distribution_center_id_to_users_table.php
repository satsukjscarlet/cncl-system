<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->foreignId('distribution_center_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->nullOnDelete();

            $table->boolean('is_active')
                ->default(true)
                ->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropForeign([
                'distribution_center_id'
            ]);

            $table->dropColumn([
                'distribution_center_id',
                'is_active'
            ]);
        });
    }
};