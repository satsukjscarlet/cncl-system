<?php

namespace Database\Seeders;

use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityStandard;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowReportTestDataSeeder extends Seeder
{
    private const PREFIX = 'TEST-SLA';
    private const REQUESTS_PER_CENTER = 15;
    private const MIN_PRODUCTS_PER_REQUEST = 20;
    private const MAX_PRODUCTS_PER_REQUEST = 100;
    private const MIN_PRODUCT_POOL = 120;

    public function run(): void
    {
        mt_srand(20260811);

        $this->call([
            PermissionSeeder::class,
            DistributionCenterSeeder::class,
            UserSeeder::class,
            QualityStandardSeeder::class,
            ProductGroupSeeder::class,
            UrgentReasonSeeder::class,
            SlaConfigSeeder::class,
        ]);

        Model::unguarded(function () {
            DB::transaction(function () {
            $this->clearOldTestData();

            $products = $this->productPool();
            $ptnUserId = User::where('username', 'ptn')->value('id') ?: User::whereHas('roles', fn ($q) => $q->where('name', 'PTN'))->value('id');

            foreach ($this->centers() as $center) {
                $centerUserId = User::role('TrungTam')
                    ->where('distribution_center_id', $center->id)
                    ->value('id') ?: User::where('username', 'admin')->value('id');

                for ($index = 1; $index <= self::REQUESTS_PER_CENTER; $index++) {
                    $scenario = $this->scenario($index);
                    $createdAt = now()->subDays($scenario['age_days'])->subHours($index);
                    $customer = $this->createCustomer($center, $index, $createdAt);
                    $request = $this->createRequest($center, $customer->id, $centerUserId, $index, $scenario, $createdAt);
                    $details = $this->createRequestDetails($request, $products, $index, $createdAt);

                    if ($scenario['create_certificate']) {
                        $this->createDraftCertificate($request, $details, $ptnUserId, $createdAt->copy()->addHours(2));
                    }
                }
            }
            });
        });

        $this->command?->info('Đã tạo dữ liệu test workflow/báo cáo: 5 trung tâm x 15 đề nghị, mỗi đề nghị 20-100 sản phẩm. Phần lớn dừng ở bước Chờ Trưởng PTN ký.');
    }

    private function centers(): Collection
    {
        return DistributionCenter::whereIn('code', ['NP', 'TP', 'HP', 'HD', 'TH'])
            ->get()
            ->sortBy(fn (DistributionCenter $center) => array_search($center->code, ['NP', 'TP', 'HP', 'HD', 'TH'], true))
            ->values();
    }

    private function clearOldTestData(): void
    {
        $requestIds = CertificateRequest::withTrashed()
            ->where('request_no', 'like', self::PREFIX . '-%')
            ->pluck('id');

        if ($requestIds->isEmpty()) {
            DB::table('customers')->where('customer_code', 'like', self::PREFIX . '-%')->delete();

            return;
        }

        $certificateIds = DB::table('quality_certificates')
            ->whereIn('certificate_request_id', $requestIds)
            ->pluck('id');

        if ($certificateIds->isNotEmpty()) {
            DB::table('quality_certificate_details')->whereIn('quality_certificate_id', $certificateIds)->delete();
            DB::table('quality_certificates')->whereIn('id', $certificateIds)->delete();
        }

        DB::table('certificate_request_details')->whereIn('certificate_request_id', $requestIds)->delete();
        DB::table('certificate_requests')->whereIn('id', $requestIds)->delete();
        DB::table('customers')->where('customer_code', 'like', self::PREFIX . '-%')->delete();
    }

    private function productPool(): Collection
    {
        $existingCount = Product::where('is_active', true)->count();

        if ($existingCount < self::MIN_PRODUCT_POOL) {
            $this->createFallbackProducts(self::MIN_PRODUCT_POOL - $existingCount);
        }

        return Product::with('qualityStandard')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    private function createFallbackProducts(int $needed): void
    {
        $group = ProductGroup::firstOrCreate(
            ['code' => self::PREFIX . '-GROUP'],
            [
                'name' => 'Nhóm sản phẩm test SLA',
                'description' => 'Nhóm sản phẩm tự sinh để test workflow và báo cáo.',
                'is_active' => true,
            ]
        );

        $standard = QualityStandard::firstOrCreate(
            ['code' => self::PREFIX . '-STD'],
            [
                'name' => 'Tiêu chuẩn test SLA',
                'description' => 'Tiêu chuẩn tự sinh để test workflow và báo cáo.',
                'is_active' => true,
            ]
        );

        $start = Product::withTrashed()
            ->where('product_code', 'like', self::PREFIX . '-SP-%')
            ->count() + 1;

        for ($i = $start; $i < $start + $needed; $i++) {
            Product::updateOrCreate(
                ['product_code' => self::PREFIX . '-SP-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'product_group_id' => $group->id,
                    'quality_standard_id' => $standard->id,
                    'product_name' => 'Sản phẩm test SLA ' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'unit' => 'm',
                    'nominal_size' => 'DN' . ([21, 27, 34, 42, 60, 75, 90, 110, 160, 200][$i % 10]),
                    'technical_requirements' => 'Đạt yêu cầu kỹ thuật test theo tiêu chuẩn công bố.',
                    'certificate_type' => 'CNCL',
                    'certificate_template' => 'default',
                    'note' => 'Dữ liệu tự sinh phục vụ test.',
                    'is_active' => true,
                ]
            );
        }
    }

    private function createCustomer(DistributionCenter $center, int $index, $createdAt)
    {
        return \App\Models\Customer::create([
            'distribution_center_id' => $center->id,
            'customer_code' => self::PREFIX . '-KH-' . $center->code . '-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            'customer_name' => 'Khách hàng test ' . $center->code . ' ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'customer_address' => 'Địa chỉ khách hàng test ' . $center->name,
            'tax_code' => 'TEST' . $center->code . str_pad((string) $index, 6, '0', STR_PAD_LEFT),
            'contact_person' => 'Người liên hệ ' . $index,
            'phone' => '090' . str_pad((string) $index, 7, '0', STR_PAD_LEFT),
            'email' => 'test-' . strtolower($center->code) . '-' . $index . '@example.com',
            'project_name' => 'Công trình test SLA ' . $center->code . ' ' . $index,
            'project_address' => 'Địa điểm công trình test ' . $center->name,
            'is_active' => true,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createRequest(DistributionCenter $center, int $customerId, ?int $creatorId, int $index, array $scenario, $createdAt): CertificateRequest
    {
        return CertificateRequest::create([
            'request_no' => self::PREFIX . '-YC-' . $center->code . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
            'request_type' => 'NORMAL',
            'distribution_center_id' => $center->id,
            'customer_id' => $customerId,
            'delivery_date' => $createdAt->copy()->addDay()->toDateString(),
            'invoice_no' => $this->invoiceNo($center->code, $index),
            'require_hard_copy' => $index % 5 === 0,
            'hard_copy_quantity' => $index % 5 === 0 ? mt_rand(1, 3) : 0,
            'is_urgent' => in_array($index, [3, 9, 14], true),
            'urgent_reason_id' => in_array($index, [3, 9, 14], true)
                ? DB::table('urgent_reasons')->where('is_active', true)->inRandomOrder()->value('id')
                : null,
            'requester_name' => 'Người tạo test ' . $center->code . ' ' . $index,
            'note' => self::PREFIX . ' - ' . $scenario['label'] . '. Dữ liệu test SLA/báo cáo.',
            'status' => $scenario['request_status'],
            'created_by' => $creatorId,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createRequestDetails(CertificateRequest $request, Collection $products, int $index, $createdAt): Collection
    {
        $count = mt_rand(self::MIN_PRODUCTS_PER_REQUEST, self::MAX_PRODUCTS_PER_REQUEST);
        $selected = $products->shuffle()->take(min($count, $products->count()))->values();
        $now = now();

        $details = $selected->map(fn (Product $product) => [
            'product' => $product,
            'quantity' => mt_rand(1, 500) + ($index / 100),
        ]);

        $rows = $details->map(fn (array $detail) => [
            'certificate_request_id' => $request->id,
            'product_id' => $detail['product']->id,
            'quantity' => $detail['quantity'],
            'created_at' => $createdAt,
            'updated_at' => $now,
        ])->all();

        DB::table('certificate_request_details')->insert($rows);

        return $details;
    }

    private function createDraftCertificate(CertificateRequest $request, Collection $products, ?int $ptnUserId, $createdAt): void
    {
        $certificateId = DB::table('quality_certificates')->insertGetId([
            'certificate_no' => self::PREFIX . '-CNCL-' . str_replace(self::PREFIX . '-YC-', '', $request->request_no),
            'status' => 'DRAFT',
            'certificate_request_id' => $request->id,
            'created_by' => $ptnUserId ?: $request->created_by,
            'signed_at' => null,
            'signed_by' => null,
            'pdf_path' => null,
            'print_count' => 0,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $rows = $products->map(function (array $detail) use ($certificateId, $createdAt) {
            /** @var Product $product */
            $product = $detail['product'];

            return [
            'quality_certificate_id' => $certificateId,
            'product_id' => $product->id,
            'quantity' => $detail['quantity'],
            'nominal_size' => $product->nominal_size,
            'technical_requirements' => $product->technical_requirements,
            'quality_standard' => $product->qualityStandard->code ?? $product->qualityStandard->name ?? 'TCVN-TEST',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
            ];
        })->all();

        DB::table('quality_certificate_details')->insert($rows);
    }

    private function scenario(int $index): array
    {
        return match (true) {
            in_array($index, [1, 2], true) => [
                'request_status' => 'WAIT_DVKH',
                'create_certificate' => false,
                'age_days' => $index === 1 ? 5 : 1,
                'label' => 'Chờ DVKH để test SLA DVKH',
            ],
            in_array($index, [3, 4, 5, 6], true) => [
                'request_status' => 'WAIT_PTN',
                'create_certificate' => false,
                'age_days' => match ($index) {
                    3 => 4,
                    4 => 2,
                    5 => 3,
                    default => 1,
                },
                'label' => 'Cho PTN lap phieu de test SLA PTN',
            ],
            default => [
                'request_status' => 'PTN_PROCESSING',
                'create_certificate' => true,
                'age_days' => (($index - 7) % 8) + 1,
                'label' => 'Đã lập phiếu, chờ Trưởng PTN ký',
            ],
        };
    }

    private function invoiceNo(string $centerCode, int $index): string
    {
        return match ($index) {
            1, 2 => self::PREFIX . '-HD-' . $centerCode . '-DUP-001',
            7, 8 => self::PREFIX . '-HD-' . $centerCode . '-DUP-002',
            default => self::PREFIX . '-HD-' . $centerCode . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT),
        };
    }
}
