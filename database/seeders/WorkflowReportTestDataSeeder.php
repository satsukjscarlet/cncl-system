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

                    if ($scenario['certificate_status']) {
                        $this->createScenarioCertificate($request, $details, $ptnUserId, $scenario, $createdAt->copy()->addHours(2));
                    }
                }

                $this->createSignedHardCopyTestCertificate($center, $centerUserId, $ptnUserId, $products);
            }
            });
        });

        $this->command?->info('Đã tạo dữ liệu test workflow/báo cáo: 5 trung tâm x 15 đề nghị, mỗi đề nghị 20-100 sản phẩm. Có thêm 5 phiếu đã ký số, mỗi phiếu nhiều sản phẩm để test in ký tươi/in lại.');
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
            ->orWhere('certificate_no', 'like', self::PREFIX . '-%')
            ->pluck('id');

        if ($certificateIds->isNotEmpty()) {
            DB::table('print_logs')->whereIn('quality_certificate_id', $certificateIds)->delete();
            DB::table('certificate_request_reissue_certificates')
                ->whereIn('quality_certificate_id', $certificateIds)
                ->orWhereIn('certificate_request_id', $requestIds)
                ->delete();
            DB::table('quality_certificate_details')->whereIn('quality_certificate_id', $certificateIds)->delete();
            DB::table('quality_certificates')->whereIn('id', $certificateIds)->delete();
        }

        if ($requestIds->isNotEmpty()) {
            DB::table('user_notifications')
                ->where('title', 'like', '%' . self::PREFIX . '%')
                ->orWhere('message', 'like', '%' . self::PREFIX . '%')
                ->orWhere('url', 'like', '%' . self::PREFIX . '%')
                ->orWhere('data', 'like', '%' . self::PREFIX . '%')
                ->delete();

            DB::table('activity_log')
                ->where('description', 'like', '%' . self::PREFIX . '%')
                ->orWhere('properties', 'like', '%' . self::PREFIX . '%')
                ->delete();
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
        $submittedAt = $scenario['submitted_minutes_ago'] !== null
            ? now()->subMinutes($scenario['submitted_minutes_ago'])
            : null;
        $sentToPtnAt = $scenario['sent_to_ptn_minutes_ago'] !== null
            ? now()->subMinutes($scenario['sent_to_ptn_minutes_ago'])
            : null;
        $lastReturnedAt = $scenario['last_returned_minutes_ago'] !== null
            ? now()->subMinutes($scenario['last_returned_minutes_ago'])
            : null;
        $updatedAt = $lastReturnedAt
            ?? $sentToPtnAt
            ?? $submittedAt
            ?? $createdAt;

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
            'customer_commitment_confirmed' => $scenario['request_status'] !== 'DRAFT',
            'note' => self::PREFIX . ' - ' . $scenario['label'] . '. Dữ liệu test SLA/báo cáo.',
            'last_returned_from' => $scenario['last_returned_from'],
            'last_returned_to' => $scenario['last_returned_to'],
            'last_return_reason' => $scenario['last_return_reason'],
            'last_returned_at' => $lastReturnedAt,
            'last_returned_by' => $scenario['last_returned_to'] ? User::where('username', 'admin')->value('id') : null,
            'status' => $scenario['request_status'],
            'submitted_at' => $submittedAt,
            'submitted_by' => $submittedAt ? $creatorId : null,
            'sent_to_ptn_at' => $sentToPtnAt,
            'created_by' => $creatorId,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }

    private function createRequestDetails(CertificateRequest $request, Collection $products, int $index, $createdAt): Collection
    {
        $count = mt_rand(self::MIN_PRODUCTS_PER_REQUEST, self::MAX_PRODUCTS_PER_REQUEST);
        $selected = $products->shuffle()->take(min($count, $products->count()))->values();
        $now = now();

        $details = $selected->map(fn (Product $product) => [
            'product' => $product,
            'quantity' => mt_rand(1, 500),
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

    private function createScenarioCertificate(CertificateRequest $request, Collection $products, ?int $ptnUserId, array $scenario, $createdAt): void
    {
        $smartCaRequestedAt = $scenario['smartca_requested_minutes_ago'] !== null
            ? now()->subMinutes($scenario['smartca_requested_minutes_ago'])
            : null;

        $certificateId = DB::table('quality_certificates')->insertGetId([
            'certificate_no' => self::PREFIX . '-CNCL-' . str_replace(self::PREFIX . '-YC-', '', $request->request_no),
            'status' => $scenario['certificate_status'],
            'certificate_request_id' => $request->id,
            'created_by' => $ptnUserId ?: $request->created_by,
            'signed_at' => null,
            'signed_by' => null,
            'pdf_path' => null,
            'print_count' => 0,
            'rejected_at' => $scenario['certificate_status'] === 'REJECTED' ? now()->subMinutes(30) : null,
            'rejected_by' => $scenario['certificate_status'] === 'REJECTED' ? User::where('username', 'truongptn')->value('id') : null,
            'rejected_to' => $scenario['certificate_status'] === 'REJECTED' ? 'PTN' : null,
            'rejected_reason' => $scenario['certificate_status'] === 'REJECTED' ? 'Test trưởng PTN trả lại PTN xử lý lại.' : null,
            'smartca_status' => $scenario['smartca_status'],
            'smartca_transaction_id' => $scenario['smartca_status'] ? self::PREFIX . '-TRAN-' . $request->id : null,
            'smartca_tran_code' => $scenario['smartca_status'] ? self::PREFIX . '-CODE-' . $request->id : null,
            'smartca_doc_id' => $scenario['smartca_status'] ? self::PREFIX . '-DOC-' . $request->id : null,
            'smartca_data_hash' => $scenario['smartca_status'] ? hash('sha256', $request->request_no) : null,
            'smartca_response' => $scenario['smartca_status'] ? json_encode([
                'test_data' => true,
                'scenario' => $scenario['label'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'smartca_requested_at' => $smartCaRequestedAt,
            'created_at' => $createdAt,
            'updated_at' => $smartCaRequestedAt ?? $createdAt,
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

    private function createSignedHardCopyTestCertificate(DistributionCenter $center, ?int $centerUserId, ?int $ptnUserId, Collection $products): void
    {
        $sequence = ['NP' => 1, 'TP' => 2, 'HP' => 3, 'HD' => 4, 'TH' => 5][$center->code] ?? 1;
        $createdAt = now()->subDays(20 - $sequence)->subHours($sequence);
        $signedAt = $createdAt->copy()->addHours(8);
        $customer = $this->createCustomer($center, 100 + $sequence, $createdAt);
        $requestNo = self::PREFIX . '-SIGNED-YC-' . $center->code . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

        $request = CertificateRequest::create([
            'request_no' => $requestNo,
            'request_type' => 'NORMAL',
            'distribution_center_id' => $center->id,
            'customer_id' => $customer->id,
            'delivery_date' => $createdAt->copy()->addDays(2)->toDateString(),
            'invoice_no' => self::PREFIX . '-SIGNED-HD-' . $center->code . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'require_hard_copy' => true,
            'hard_copy_quantity' => 2,
            'is_urgent' => false,
            'urgent_reason_id' => null,
            'requester_name' => 'Người tạo test phiếu đã ký ' . $center->code,
            'customer_commitment_confirmed' => true,
            'note' => self::PREFIX . ' - Phiếu đã ký số để test in ký tươi/in lại, có nhiều dòng sản phẩm.',
            'status' => 'COMPLETED',
            'submitted_at' => $createdAt->copy()->addMinutes(30),
            'submitted_by' => $centerUserId,
            'sent_to_ptn_at' => $createdAt->copy()->addHours(2),
            'created_by' => $centerUserId,
            'created_at' => $createdAt,
            'updated_at' => $signedAt,
        ]);

        $count = [45, 60, 75, 90, 100][$sequence - 1] ?? 60;
        $selectedProducts = $products->shuffle()->take(min($count, $products->count()))->values();
        $requestDetailRows = [];
        $certificateDetailRows = [];

        foreach ($selectedProducts as $index => $product) {
            /** @var Product $product */
            $quantity = mt_rand(1, 500);
            $qualityStandard = ($index + 1) % 7 === 0
                ? 'DIN 8077:2008&DIN8078:2008'
                : ($product->qualityStandard->code ?? $product->qualityStandard->name ?? 'TCVN-TEST');

            $requestDetailRows[] = [
                'certificate_request_id' => $request->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'created_at' => $createdAt,
                'updated_at' => $signedAt,
            ];

            $certificateDetailRows[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'nominal_size' => $product->nominal_size,
                'technical_requirements' => $product->technical_requirements,
                'quality_standard' => $qualityStandard,
                'created_at' => $createdAt,
                'updated_at' => $signedAt,
            ];
        }

        DB::table('certificate_request_details')->insert($requestDetailRows);

        $certificateId = DB::table('quality_certificates')->insertGetId([
            'certificate_no' => self::PREFIX . '-SIGNED-CNCL-' . $center->code . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'status' => 'ISSUED',
            'certificate_request_id' => $request->id,
            'created_by' => $ptnUserId ?: $centerUserId,
            'signed_at' => $signedAt,
            'signed_by' => 'VNPT SmartCA Test',
            'pdf_path' => null,
            'print_count' => 0,
            'smartca_status' => 'SIGNED',
            'smartca_transaction_id' => self::PREFIX . '-SIGNED-TRAN-' . $center->code . '-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'smartca_tran_code' => self::PREFIX . '-SIGNED-CODE-' . $center->code,
            'smartca_doc_id' => self::PREFIX . '-SIGNED-DOC-' . $center->code,
            'smartca_data_hash' => hash('sha256', $requestNo),
            'smartca_certificate_serial' => config('services.smartca.serial_number'),
            'smartca_signature_value' => 'TEST_SIGNATURE_VALUE',
            'smartca_timestamp_signature' => $signedAt->format('YmdHis'),
            'smartca_requested_at' => $signedAt->copy()->subMinutes(5),
            'smartca_completed_at' => $signedAt,
            'pades_status' => 'SIGNED_PDF',
            'pades_error' => null,
            'created_at' => $createdAt->copy()->addHours(3),
            'updated_at' => $signedAt,
        ]);

        DB::table('quality_certificate_details')->insert(collect($certificateDetailRows)
            ->map(fn (array $row) => array_merge($row, [
                'quality_certificate_id' => $certificateId,
            ]))
            ->all());
    }

    private function scenario(int $index): array
    {
        $base = [
            'request_status' => 'DRAFT',
            'certificate_status' => null,
            'smartca_status' => null,
            'smartca_requested_minutes_ago' => null,
            'age_days' => 1,
            'submitted_minutes_ago' => null,
            'sent_to_ptn_minutes_ago' => null,
            'last_returned_from' => null,
            'last_returned_to' => null,
            'last_return_reason' => null,
            'last_returned_minutes_ago' => null,
            'label' => 'Nháp test',
        ];

        return array_merge($base, match ($index) {
            1 => [
                'request_status' => 'DRAFT',
                'age_days' => 6,
                'label' => 'Nháp cũ chưa gửi DVKH - không tính SLA',
            ],
            2 => [
                'request_status' => 'DRAFT',
                'age_days' => 4,
                'last_returned_from' => 'DVKH',
                'last_returned_to' => 'TRUNG_TAM',
                'last_return_reason' => 'Test DVKH trả lại trung tâm bổ sung thông tin.',
                'last_returned_minutes_ago' => 180,
                'label' => 'DVKH trả lại trung tâm phân phối',
            ],
            3 => [
                'request_status' => 'WAIT_DVKH',
                'age_days' => 5,
                'submitted_minutes_ago' => 20,
                'label' => 'Mới gửi DVKH từ bản nháp cũ - SLA phải bình thường',
            ],
            4 => [
                'request_status' => 'WAIT_DVKH',
                'age_days' => 2,
                'submitted_minutes_ago' => 1500,
                'label' => 'Chờ DVKH gần quá hạn SLA',
            ],
            5 => [
                'request_status' => 'WAIT_DVKH',
                'age_days' => 5,
                'submitted_minutes_ago' => 3300,
                'label' => 'Chờ DVKH quá hạn SLA',
            ],
            6 => [
                'request_status' => 'WAIT_DVKH',
                'age_days' => 6,
                'submitted_minutes_ago' => 4200,
                'last_returned_from' => 'PTN',
                'last_returned_to' => 'DVKH',
                'last_return_reason' => 'Test PTN trả lại DVKH kiểm tra lại dữ liệu.',
                'last_returned_minutes_ago' => 45,
                'label' => 'PTN trả lại DVKH - SLA tính lại từ lúc trả',
            ],
            7 => [
                'request_status' => 'WAIT_PTN',
                'age_days' => 5,
                'submitted_minutes_ago' => 4200,
                'sent_to_ptn_minutes_ago' => 30,
                'label' => 'Mới chuyển PTN từ hồ sơ cũ - SLA PTN bình thường',
            ],
            8 => [
                'request_status' => 'WAIT_PTN',
                'age_days' => 3,
                'submitted_minutes_ago' => 3000,
                'sent_to_ptn_minutes_ago' => 1500,
                'label' => 'Chờ PTN gần quá hạn SLA',
            ],
            9 => [
                'request_status' => 'WAIT_PTN',
                'age_days' => 5,
                'submitted_minutes_ago' => 4200,
                'sent_to_ptn_minutes_ago' => 3300,
                'label' => 'Chờ PTN quá hạn SLA',
            ],
            10 => [
                'request_status' => 'PTN_PROCESSING',
                'certificate_status' => 'WAIT_PTN_MANAGER_APPROVAL',
                'age_days' => 2,
                'submitted_minutes_ago' => 2600,
                'sent_to_ptn_minutes_ago' => 1200,
                'label' => 'PTN đã lập phiếu - chờ Trưởng PTN duyệt',
            ],
            11 => [
                'request_status' => 'PTN_PROCESSING',
                'certificate_status' => 'READY_TO_SIGN',
                'age_days' => 2,
                'submitted_minutes_ago' => 2600,
                'sent_to_ptn_minutes_ago' => 1000,
                'label' => 'Trưởng PTN đã duyệt - chờ gửi ký số',
            ],
            12 => [
                'request_status' => 'PTN_PROCESSING',
                'certificate_status' => 'READY_TO_SIGN',
                'smartca_status' => 'PENDING',
                'smartca_requested_minutes_ago' => 3,
                'age_days' => 2,
                'submitted_minutes_ago' => 2600,
                'sent_to_ptn_minutes_ago' => 1000,
                'label' => 'SmartCA pending còn hạn để test kiểm tra kết quả ký',
            ],
            13 => [
                'request_status' => 'PTN_PROCESSING',
                'certificate_status' => 'READY_TO_SIGN',
                'smartca_status' => 'PENDING',
                'smartca_requested_minutes_ago' => 15,
                'age_days' => 2,
                'submitted_minutes_ago' => 2600,
                'sent_to_ptn_minutes_ago' => 1000,
                'label' => 'SmartCA pending quá 5 phút để test gửi lại yêu cầu ký',
            ],
            14 => [
                'request_status' => 'PTN_PROCESSING',
                'certificate_status' => 'REJECTED',
                'age_days' => 4,
                'submitted_minutes_ago' => 5000,
                'sent_to_ptn_minutes_ago' => 3000,
                'last_returned_from' => 'TRUONG_PTN',
                'last_returned_to' => 'PTN',
                'last_return_reason' => 'Test Trưởng PTN trả lại PTN xử lý lại.',
                'last_returned_minutes_ago' => 35,
                'label' => 'Trưởng PTN trả lại PTN - SLA PTN tính lại',
            ],
            default => [
                'request_status' => 'PTN_PROCESSING',
                'certificate_status' => 'WAIT_PTN_MANAGER_APPROVAL',
                'age_days' => 1,
                'submitted_minutes_ago' => 1800,
                'sent_to_ptn_minutes_ago' => 900,
                'label' => 'Yêu cầu gấp đã lập phiếu, chờ Trưởng PTN duyệt',
            ],
        });
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
