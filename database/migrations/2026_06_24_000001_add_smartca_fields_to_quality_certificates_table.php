<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->string('smartca_status')->nullable()->after('print_count');
            $table->string('smartca_transaction_id')->nullable()->after('smartca_status');
            $table->string('smartca_tran_code')->nullable()->after('smartca_transaction_id');
            $table->string('smartca_doc_id')->nullable()->after('smartca_tran_code');
            $table->string('smartca_data_hash', 128)->nullable()->after('smartca_doc_id');
            $table->text('smartca_signature_value')->nullable()->after('smartca_data_hash');
            $table->text('smartca_timestamp_signature')->nullable()->after('smartca_signature_value');
            $table->json('smartca_response')->nullable()->after('smartca_timestamp_signature');
            $table->timestamp('smartca_requested_at')->nullable()->after('smartca_response');
            $table->timestamp('smartca_completed_at')->nullable()->after('smartca_requested_at');

            $table->index('smartca_transaction_id', 'quality_certificates_smartca_transaction_idx');
        });
    }

    public function down(): void
    {
        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropIndex('quality_certificates_smartca_transaction_idx');
            $table->dropColumn([
                'smartca_status',
                'smartca_transaction_id',
                'smartca_tran_code',
                'smartca_doc_id',
                'smartca_data_hash',
                'smartca_signature_value',
                'smartca_timestamp_signature',
                'smartca_response',
                'smartca_requested_at',
                'smartca_completed_at',
            ]);
        });
    }
};
