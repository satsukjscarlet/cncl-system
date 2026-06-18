<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'cert_requests_status_created_idx');
            $table->index(['distribution_center_id', 'status'], 'cert_requests_center_status_idx');
            $table->index(['customer_id', 'created_at'], 'cert_requests_customer_created_idx');
            $table->index('invoice_no', 'cert_requests_invoice_idx');
            $table->index('delivery_date', 'cert_requests_delivery_idx');
        });

        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->index(['signed_at', 'created_at'], 'quality_certs_signed_created_idx');
            $table->index(['created_by', 'created_at'], 'quality_certs_creator_created_idx');
        });

        Schema::table('login_logs', function (Blueprint $table) {
            $table->index(['status', 'logged_at'], 'login_logs_status_logged_idx');
            $table->index(['user_id', 'logged_at'], 'login_logs_user_logged_idx');
            $table->index('ip_address', 'login_logs_ip_idx');
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->index(['log_name', 'created_at'], 'activity_log_name_created_idx');
            $table->index(['causer_id', 'created_at'], 'activity_log_causer_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex('activity_log_causer_created_idx');
            $table->dropIndex('activity_log_name_created_idx');
        });

        Schema::table('login_logs', function (Blueprint $table) {
            $table->dropIndex('login_logs_ip_idx');
            $table->dropIndex('login_logs_user_logged_idx');
            $table->dropIndex('login_logs_status_logged_idx');
        });

        Schema::table('quality_certificates', function (Blueprint $table) {
            $table->dropIndex('quality_certs_creator_created_idx');
            $table->dropIndex('quality_certs_signed_created_idx');
        });

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropIndex('cert_requests_delivery_idx');
            $table->dropIndex('cert_requests_invoice_idx');
            $table->dropIndex('cert_requests_customer_created_idx');
            $table->dropIndex('cert_requests_center_status_idx');
            $table->dropIndex('cert_requests_status_created_idx');
        });
    }
};
