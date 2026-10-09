<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50);
            $table->unsignedSmallInteger('year');
            $table->foreignId('distribution_center_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(
                ['document_type', 'year', 'distribution_center_id'],
                'document_sequences_type_year_center_unique'
            );
        });

        $this->backfillRequestSequences();
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }

    private function backfillRequestSequences(): void
    {
        if (!Schema::hasTable('certificate_requests')) {
            return;
        }

        $sequences = [];

        DB::table('certificate_requests')
            ->select('distribution_center_id', 'request_no', 'created_at')
            ->whereNotNull('distribution_center_id')
            ->orderBy('id')
            ->cursor()
            ->each(function ($request) use (&$sequences): void {
                $year = null;
                $sequence = 0;
                $requestNo = (string) ($request->request_no ?? '');

                if (preg_match('/^YC-(\d{4})\d{4}-(\d{4,6})\/[A-Z0-9_-]+$/i', $requestNo, $matches)) {
                    $year = (int) $matches[1];
                    $sequence = (int) $matches[2];
                }

                if (!$year && $request->created_at) {
                    $year = (int) substr((string) $request->created_at, 0, 4);
                }

                if (!$year) {
                    return;
                }

                $key = $request->distribution_center_id . ':' . $year;
                $sequences[$key] ??= [
                    'distribution_center_id' => (int) $request->distribution_center_id,
                    'year' => $year,
                    'count' => 0,
                    'max_sequence' => 0,
                ];

                $sequences[$key]['count']++;
                $sequences[$key]['max_sequence'] = max($sequences[$key]['max_sequence'], $sequence);
            });

        foreach ($sequences as $sequence) {
            DB::table('document_sequences')->insert([
                'document_type' => 'request',
                'year' => $sequence['year'],
                'distribution_center_id' => $sequence['distribution_center_id'],
                'last_number' => max($sequence['count'], $sequence['max_sequence']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
