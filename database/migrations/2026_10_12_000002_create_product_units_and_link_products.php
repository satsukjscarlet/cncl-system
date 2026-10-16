<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('unit_id')
                ->nullable()
                ->after('quality_standard_id')
                ->constrained('product_units')
                ->nullOnDelete();
        });

        $now = now();
        $units = DB::table('products')
            ->whereNotNull('unit')
            ->where('unit', '<>', '')
            ->select('unit')
            ->distinct()
            ->orderBy('unit')
            ->pluck('unit');

        foreach ($units as $unitName) {
            $unitName = trim((string) $unitName);

            if ($unitName === '') {
                continue;
            }

            $code = $this->uniqueUnitCode($unitName);

            DB::table('product_units')->insertOrIgnore([
                'code' => $code,
                'name' => $unitName,
                'description' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $unitId = DB::table('product_units')
                ->where('name', $unitName)
                ->orWhere('code', $code)
                ->value('id');

            if ($unitId) {
                DB::table('products')
                    ->where('unit', $unitName)
                    ->update(['unit_id' => $unitId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });

        Schema::dropIfExists('product_units');
    }

    private function uniqueUnitCode(string $name): string
    {
        $base = strtoupper(Str::slug($name, '_'));
        $base = $base !== '' ? substr($base, 0, 40) : 'DVT';
        $code = $base;
        $index = 2;

        while (DB::table('product_units')->where('code', $code)->exists()) {
            $code = substr($base, 0, 36) . '_' . $index;
            $index++;
        }

        return $code;
    }
};
