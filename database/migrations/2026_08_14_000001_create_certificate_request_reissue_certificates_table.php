<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_request_reissue_certificates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('certificate_request_id');

            $table->unsignedBigInteger('quality_certificate_id');

            $table->timestamps();

            $table->unique(
                ['certificate_request_id', 'quality_certificate_id'],
                'cr_reissue_cert_unique'
            );

            $table->foreign('certificate_request_id', 'cr_reissue_request_fk')
                ->references('id')
                ->on('certificate_requests')
                ->cascadeOnDelete();

            $table->foreign('quality_certificate_id', 'cr_reissue_certificate_fk')
                ->references('id')
                ->on('quality_certificates')
                ->cascadeOnDelete();
        });

        DB::table('certificate_requests')
            ->where('request_type', 'REISSUE')
            ->whereNotNull('reissue_of_certificate_id')
            ->orderBy('id')
            ->select(['id', 'reissue_of_certificate_id'])
            ->chunkById(200, function ($requests) {
                foreach ($requests as $request) {
                    DB::table('certificate_request_reissue_certificates')->updateOrInsert(
                        [
                            'certificate_request_id' => $request->id,
                            'quality_certificate_id' => $request->reissue_of_certificate_id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_request_reissue_certificates');
    }
};
