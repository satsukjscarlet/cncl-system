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
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->string('invoice_no_normalized')->nullable()->after('invoice_no');
            $table->index('invoice_no_normalized', 'certificate_requests_invoice_normalized_idx');
        });

        DB::table('certificate_requests')
            ->whereNotNull('invoice_no')
            ->orderBy('id')
            ->chunkById(200, function ($requests) {
                foreach ($requests as $request) {
                    DB::table('certificate_requests')
                        ->where('id', $request->id)
                        ->update([
                            'invoice_no_normalized' => $this->normalizeInvoiceNo($request->invoice_no),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropIndex('certificate_requests_invoice_normalized_idx');
            $table->dropColumn('invoice_no_normalized');
        });
    }

    private function normalizeInvoiceNo(?string $invoiceNo): ?string
    {
        if ($invoiceNo === null || trim($invoiceNo) === '') {
            return null;
        }

        return preg_replace('/\s+/', '', Str::upper(trim($invoiceNo)));
    }
};
