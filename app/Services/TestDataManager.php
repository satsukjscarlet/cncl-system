<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\Product;
use Database\Seeders\WorkflowReportTestDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestDataManager
{
    public const PREFIX = 'TEST-SLA';

    public function seed(): array
    {
        Artisan::call('db:seed', [
            '--class' => WorkflowReportTestDataSeeder::class,
            '--force' => true,
        ]);

        return [
            'output' => trim(Artisan::output()),
            ...$this->counts(),
        ];
    }

    public function clear(): array
    {
        $counts = $this->counts();

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () {
                $requestIds = CertificateRequest::withTrashed()
                    ->where('request_no', 'like', self::PREFIX . '-%')
                    ->pluck('id');

                $certificateIds = DB::table('quality_certificates')
                    ->whereIn('certificate_request_id', $requestIds)
                    ->orWhere('certificate_no', 'like', self::PREFIX . '-%')
                    ->pluck('id');

                $productIds = Product::withTrashed()
                    ->where('product_code', 'like', self::PREFIX . '-SP-%')
                    ->pluck('id');

                $this->deleteNotifications();
                $this->deleteActivityLogs($requestIds, $certificateIds);

                DB::table('certificate_request_reissue_certificates')
                    ->whereIn('certificate_request_id', $requestIds)
                    ->orWhereIn('quality_certificate_id', $certificateIds)
                    ->delete();

                DB::table('print_logs')
                    ->whereIn('quality_certificate_id', $certificateIds)
                    ->delete();

                DB::table('quality_certificates')
                    ->whereIn('id', $certificateIds)
                    ->update([
                        'replaces_certificate_id' => null,
                        'replaced_by_certificate_id' => null,
                    ]);

                DB::table('certificate_requests')
                    ->whereIn('id', $requestIds)
                    ->update(['reissue_of_certificate_id' => null]);

                DB::table('quality_certificate_details')
                    ->whereIn('quality_certificate_id', $certificateIds)
                    ->delete();

                DB::table('quality_certificates')
                    ->whereIn('id', $certificateIds)
                    ->delete();

                DB::table('certificate_request_details')
                    ->whereIn('certificate_request_id', $requestIds)
                    ->delete();

                DB::table('certificate_requests')
                    ->whereIn('id', $requestIds)
                    ->delete();

                DB::table('customers')
                    ->where('customer_code', 'like', self::PREFIX . '-%')
                    ->delete();

                if ($productIds->isNotEmpty()) {
                    DB::table('products')->whereIn('id', $productIds)->delete();
                }

                DB::table('product_groups')
                    ->where('code', self::PREFIX . '-GROUP')
                    ->delete();

                DB::table('quality_standards')
                    ->where('code', self::PREFIX . '-STD')
                    ->delete();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return $counts;
    }

    public function counts(): array
    {
        $requestIds = CertificateRequest::withTrashed()
            ->where('request_no', 'like', self::PREFIX . '-%')
            ->pluck('id');

        $certificateIds = DB::table('quality_certificates')
            ->whereIn('certificate_request_id', $requestIds)
            ->orWhere('certificate_no', 'like', self::PREFIX . '-%')
            ->pluck('id');

        return [
            'requests' => $requestIds->count(),
            'certificates' => $certificateIds->count(),
            'customers' => DB::table('customers')->where('customer_code', 'like', self::PREFIX . '-%')->count(),
            'products' => Product::withTrashed()->where('product_code', 'like', self::PREFIX . '-SP-%')->count(),
        ];
    }

    private function deleteNotifications(): void
    {
        if (!Schema::hasTable('user_notifications')) {
            return;
        }

        DB::table('user_notifications')
            ->where('title', 'like', '%' . self::PREFIX . '%')
            ->orWhere('message', 'like', '%' . self::PREFIX . '%')
            ->orWhere('url', 'like', '%' . self::PREFIX . '%')
            ->orWhere('data', 'like', '%' . self::PREFIX . '%')
            ->delete();
    }

    private function deleteActivityLogs($requestIds, $certificateIds): void
    {
        if (!Schema::hasTable('activity_log')) {
            return;
        }

        DB::table('activity_log')
            ->where('description', 'like', '%' . self::PREFIX . '%')
            ->orWhere('properties', 'like', '%' . self::PREFIX . '%')
            ->orWhere(function ($query) use ($requestIds) {
                $query->where('subject_type', CertificateRequest::class)
                    ->whereIn('subject_id', $requestIds);
            })
            ->orWhere(function ($query) use ($certificateIds) {
                $query->where('subject_type', \App\Models\QualityCertificate::class)
                    ->whereIn('subject_id', $certificateIds);
            })
            ->delete();
    }
}

