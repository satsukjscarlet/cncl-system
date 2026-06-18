<?php

namespace Database\Seeders;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\LoginLog;
use App\Models\PrintLog;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityCertificate;
use App\Models\QualityStandard;
use App\Models\SlaConfig;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AcceptanceTestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            DistributionCenterSeeder::class,
            QualityStandardSeeder::class,
            ProductGroupSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
            SlaConfigSeeder::class,
            SystemSettingSeeder::class,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $centers = $this->seedCenters();
        $users = $this->seedUsers($centers);
        $standards = $this->seedStandards();
        $groups = $this->seedProductGroups();
        $products = $this->seedProducts($groups, $standards);
        $customers = $this->seedCustomers();

        $requests = $this->seedCertificateRequests($centers, $users, $customers, $products);
        $certificates = $this->seedQualityCertificates($requests, $users, $products);

        $this->seedPrintLogs($certificates, $users);
        $this->seedLoginLogs($users);
        $this->seedActivityLogs($users, $requests);
        $this->seedAcceptanceSettings();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedCenters(): array
    {
        $data = [
            'AC_HP' => [
                'code' => 'AC_HP',
                'name' => 'Acceptance - Trung tam Hai Phong',
                'email' => 'ac.hp@ntp.local',
                'phone' => '0225 399 0001',
                'contact_person' => 'Nguyen Van Hai',
                'address' => 'Khu cong nghiep Dinh Vu, Hai Phong',
                'is_active' => true,
            ],
            'AC_HN' => [
                'code' => 'AC_HN',
                'name' => 'Acceptance - Trung tam Ha Noi',
                'email' => 'ac.hn@ntp.local',
                'phone' => '024 399 0002',
                'contact_person' => 'Tran Thi Ha',
                'address' => 'Khu cong nghiep Thang Long, Ha Noi',
                'is_active' => true,
            ],
            'AC_LOCK' => [
                'code' => 'AC_LOCK',
                'name' => 'Acceptance - Trung tam ngung hoat dong',
                'email' => 'ac.lock@ntp.local',
                'phone' => '028 399 0003',
                'contact_person' => 'Le Van Khoa',
                'address' => 'Du lieu kiem thu trang thai inactive',
                'is_active' => false,
            ],
        ];

        $centers = [];
        foreach ($data as $key => $item) {
            $centers[$key] = DistributionCenter::updateOrCreate(['code' => $item['code']], $item);
        }

        return $centers;
    }

    private function seedUsers(array $centers): array
    {
        $data = [
            'admin_accept' => [
                'name' => 'Acceptance Admin',
                'email' => 'accept.admin@ntp.local',
                'role' => 'Admin',
                'center' => null,
                'is_active' => true,
            ],
            'leader_accept' => [
                'name' => 'Acceptance Lanh dao',
                'email' => 'accept.leader@ntp.local',
                'role' => 'LanhDao',
                'center' => null,
                'is_active' => true,
            ],
            'center_accept' => [
                'name' => 'Acceptance Trung tam Hai Phong',
                'email' => 'accept.center@ntp.local',
                'role' => 'TrungTam',
                'center' => 'AC_HP',
                'is_active' => true,
            ],
            'dvkh_accept' => [
                'name' => 'Acceptance DVKH',
                'email' => 'accept.dvkh@ntp.local',
                'role' => 'DVKH',
                'center' => null,
                'is_active' => true,
            ],
            'ptn_accept' => [
                'name' => 'Acceptance PTN',
                'email' => 'accept.ptn@ntp.local',
                'role' => 'PTN',
                'center' => null,
                'is_active' => true,
            ],
            'viewer_accept' => [
                'name' => 'Acceptance Viewer',
                'email' => 'accept.viewer@ntp.local',
                'role' => 'Viewer',
                'center' => null,
                'is_active' => true,
            ],
            'inactive_accept' => [
                'name' => 'Acceptance Tai khoan bi khoa',
                'email' => 'accept.inactive@ntp.local',
                'role' => 'Viewer',
                'center' => null,
                'is_active' => false,
            ],
        ];

        $users = [];
        foreach ($data as $username => $item) {
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'password' => Hash::make('123123123'),
                    'distribution_center_id' => $item['center'] ? $centers[$item['center']]->id : null,
                    'is_active' => $item['is_active'],
                ]
            );

            Role::firstOrCreate(['name' => $item['role'], 'guard_name' => 'web']);
            $user->syncRoles([$item['role']]);
            $users[$username] = $user;
        }

        return $users;
    }

    private function seedStandards(): array
    {
        $data = [
            'AC_ISO_4427' => [
                'code' => 'AC-ISO-4427',
                'name' => 'Acceptance ISO 4427 - ong HDPE',
                'description' => 'Tieu chuan kiem thu cho ong HDPE cap nuoc.',
                'is_active' => true,
            ],
            'AC_TCVN_PVC' => [
                'code' => 'AC-TCVN-PVC',
                'name' => 'Acceptance TCVN PVC-U',
                'description' => 'Tieu chuan kiem thu cho ong PVC-U.',
                'is_active' => true,
            ],
            'AC_OLD' => [
                'code' => 'AC-OLD-STANDARD',
                'name' => 'Acceptance tieu chuan ngung su dung',
                'description' => 'Du lieu kiem thu trang thai inactive.',
                'is_active' => false,
            ],
        ];

        $standards = [];
        foreach ($data as $key => $item) {
            $standards[$key] = QualityStandard::updateOrCreate(['code' => $item['code']], $item);
        }

        return $standards;
    }

    private function seedProductGroups(): array
    {
        $data = [
            'HDPE' => [
                'code' => 'AC-HDPE',
                'name' => 'Acceptance nhom ong HDPE',
                'description' => 'Nhom san pham HDPE dung cho nghiem thu import/export.',
                'is_active' => true,
            ],
            'PVC' => [
                'code' => 'AC-PVC',
                'name' => 'Acceptance nhom ong PVC-U',
                'description' => 'Nhom san pham PVC dung cho nghiem thu danh muc.',
                'is_active' => true,
            ],
            'INACTIVE' => [
                'code' => 'AC-GROUP-OFF',
                'name' => 'Acceptance nhom ngung su dung',
                'description' => 'Du lieu kiem thu loc trang thai inactive.',
                'is_active' => false,
            ],
        ];

        $groups = [];
        foreach ($data as $key => $item) {
            $groups[$key] = ProductGroup::updateOrCreate(['code' => $item['code']], $item);
        }

        return $groups;
    }

    private function seedProducts(array $groups, array $standards): array
    {
        $data = [
            'HDPE_DN90' => [
                'product_group_id' => $groups['HDPE']->id,
                'quality_standard_id' => $standards['AC_ISO_4427']->id,
                'product_code' => 'AC-HDPE-DN90-PN8',
                'product_name' => 'Acceptance ong HDPE DN90 PN8',
                'unit' => 'm',
                'nominal_size' => 'DN90',
                'technical_requirements' => 'PN8; PE100; ap dung cho luu mau nghiem thu.',
                'certificate_type' => 'CNCL',
                'certificate_template' => 'template_hdpe',
                'note' => 'San pham co day du thong tin ky thuat.',
                'is_active' => true,
            ],
            'HDPE_DN25' => [
                'product_group_id' => $groups['HDPE']->id,
                'quality_standard_id' => $standards['AC_ISO_4427']->id,
                'product_code' => 'AC-HDPE-DN25-PN16',
                'product_name' => 'Acceptance ong HDPE DN25 PN16',
                'unit' => 'm',
                'nominal_size' => 'DN25',
                'technical_requirements' => 'PN16; PE100; san pham thu nghiem goi y tim kiem.',
                'certificate_type' => 'CNCL',
                'certificate_template' => 'template_hdpe',
                'note' => null,
                'is_active' => true,
            ],
            'PVC_DN110' => [
                'product_group_id' => $groups['PVC']->id,
                'quality_standard_id' => $standards['AC_TCVN_PVC']->id,
                'product_code' => 'AC-PVC-DN110',
                'product_name' => 'Acceptance ong PVC-U DN110',
                'unit' => 'm',
                'nominal_size' => 'DN110',
                'technical_requirements' => 'PVC-U; kich thuoc danh nghia DN110.',
                'certificate_type' => 'CNCL',
                'certificate_template' => 'template_pvc',
                'note' => null,
                'is_active' => true,
            ],
            'INACTIVE' => [
                'product_group_id' => $groups['INACTIVE']->id,
                'quality_standard_id' => $standards['AC_OLD']->id,
                'product_code' => 'AC-PRODUCT-OFF',
                'product_name' => 'Acceptance san pham ngung su dung',
                'unit' => 'm',
                'nominal_size' => 'DN00',
                'technical_requirements' => 'Du lieu inactive.',
                'certificate_type' => 'CNCL',
                'certificate_template' => 'template_inactive',
                'note' => 'Khong dung trong tao yeu cau moi.',
                'is_active' => false,
            ],
        ];

        $products = [];
        foreach ($data as $key => $item) {
            $products[$key] = Product::updateOrCreate(['product_code' => $item['product_code']], $item);
        }

        return $products;
    }

    private function seedCustomers(): array
    {
        $data = [
            'WATER_HP' => [
                'customer_code' => 'AC-KH-HP',
                'customer_name' => 'Acceptance Cong ty Cap nuoc Hai Phong',
                'customer_address' => 'So 1 Duong Kiem Thu, Hai Phong',
                'tax_code' => 'AC010000001',
                'contact_person' => 'Pham Van Test',
                'phone' => '0901000001',
                'email' => 'khachhang.hp@example.test',
                'project_name' => 'Acceptance du an cap nuoc Hai Phong',
                'project_address' => 'Quan Hong Bang, Hai Phong',
                'is_active' => true,
            ],
            'NO_EMAIL' => [
                'customer_code' => 'AC-KH-NOEMAIL',
                'customer_name' => 'Acceptance Khach hang chua co email',
                'customer_address' => 'Dia chi kiem thu email rong',
                'tax_code' => 'AC010000002',
                'contact_person' => 'Khach Hang Test',
                'phone' => '0901000002',
                'email' => null,
                'project_name' => 'Acceptance du an can cap nhat email',
                'project_address' => 'Dia diem kiem thu gui mail',
                'is_active' => true,
            ],
            'INACTIVE' => [
                'customer_code' => 'AC-KH-OFF',
                'customer_name' => 'Acceptance khach hang ngung su dung',
                'customer_address' => 'Dia chi inactive',
                'tax_code' => 'AC010000003',
                'contact_person' => 'Inactive Test',
                'phone' => '0901000003',
                'email' => 'inactive@example.test',
                'project_name' => 'Acceptance du an inactive',
                'project_address' => 'Dia diem inactive',
                'is_active' => false,
            ],
        ];

        $customers = [];
        foreach ($data as $key => $item) {
            $customers[$key] = Customer::updateOrCreate(['customer_code' => $item['customer_code']], $item);
        }

        return $customers;
    }

    private function seedCertificateRequests(array $centers, array $users, array $customers, array $products): array
    {
        $cases = [
            [
                'key' => 'DRAFT',
                'request_no' => 'AC-YC-DRAFT-0001',
                'status' => 'DRAFT',
                'center' => 'AC_HP',
                'customer' => 'WATER_HP',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-DRAFT-001',
                'delivery_date' => now()->subDays(1)->toDateString(),
                'require_hard_copy' => false,
                'hard_copy_quantity' => 0,
                'note' => 'Nghiem thu: Trung tam tao nhap va duoc sua/xoa.',
                'created_at' => now()->subHours(3),
                'details' => [
                    ['product' => 'HDPE_DN90', 'quantity' => 120],
                ],
            ],
            [
                'key' => 'WAIT_DVKH',
                'request_no' => 'AC-YC-WAIT-DVKH-0001',
                'status' => 'WAIT_DVKH',
                'center' => 'AC_HP',
                'customer' => 'WATER_HP',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-DVKH-001',
                'delivery_date' => now()->toDateString(),
                'require_hard_copy' => true,
                'hard_copy_quantity' => 2,
                'note' => 'Nghiem thu: DVKH thay nut xac nhan va tra lai.',
                'created_at' => now()->subHours(2),
                'details' => [
                    ['product' => 'HDPE_DN90', 'quantity' => 200],
                    ['product' => 'HDPE_DN25', 'quantity' => 500],
                ],
            ],
            [
                'key' => 'WAIT_DVKH_NO_EMAIL',
                'request_no' => 'AC-YC-NOEMAIL-0001',
                'status' => 'WAIT_DVKH',
                'center' => 'AC_HN',
                'customer' => 'NO_EMAIL',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-NOEMAIL-001',
                'delivery_date' => now()->subDays(2)->toDateString(),
                'require_hard_copy' => false,
                'hard_copy_quantity' => 0,
                'note' => 'Nghiem thu: khach hang chua co email de test canh bao gui mail.',
                'created_at' => now()->subHours(5),
                'details' => [
                    ['product' => 'PVC_DN110', 'quantity' => 80],
                ],
            ],
            [
                'key' => 'WAIT_PTN',
                'request_no' => 'AC-YC-WAIT-PTN-0001',
                'status' => 'WAIT_PTN',
                'center' => 'AC_HP',
                'customer' => 'WATER_HP',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-PTN-001',
                'delivery_date' => now()->subDays(3)->toDateString(),
                'require_hard_copy' => true,
                'hard_copy_quantity' => 1,
                'note' => 'Nghiem thu: PTN tiep nhan yeu cau.',
                'created_at' => now()->subHours(7),
                'details' => [
                    ['product' => 'HDPE_DN25', 'quantity' => 300],
                ],
            ],
            [
                'key' => 'PTN_PROCESSING',
                'request_no' => 'AC-YC-PTN-PROCESS-0001',
                'status' => 'PTN_PROCESSING',
                'center' => 'AC_HN',
                'customer' => 'WATER_HP',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-PROCESS-001',
                'delivery_date' => now()->subDays(4)->toDateString(),
                'require_hard_copy' => false,
                'hard_copy_quantity' => 0,
                'note' => 'Nghiem thu: PTN lap phieu CNCL.',
                'created_at' => now()->subHours(12),
                'details' => [
                    ['product' => 'PVC_DN110', 'quantity' => 50],
                ],
            ],
            [
                'key' => 'SIGNED',
                'request_no' => 'AC-YC-SIGNED-0001',
                'status' => 'SIGNED',
                'center' => 'AC_HP',
                'customer' => 'WATER_HP',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-SIGNED-001',
                'delivery_date' => now()->subDays(5)->toDateString(),
                'require_hard_copy' => true,
                'hard_copy_quantity' => 3,
                'note' => 'Nghiem thu: phieu da ky, co lich su in ky tuoi.',
                'created_at' => now()->subDays(1),
                'details' => [
                    ['product' => 'HDPE_DN90', 'quantity' => 180],
                ],
            ],
            [
                'key' => 'COMPLETED',
                'request_no' => 'AC-YC-COMPLETED-0001',
                'status' => 'COMPLETED',
                'center' => 'AC_HN',
                'customer' => 'WATER_HP',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-DONE-001',
                'delivery_date' => now()->subDays(8)->toDateString(),
                'require_hard_copy' => false,
                'hard_copy_quantity' => 0,
                'note' => 'Nghiem thu: hoan tat quy trinh va bao cao.',
                'created_at' => now()->subDays(2),
                'details' => [
                    ['product' => 'HDPE_DN25', 'quantity' => 1000],
                    ['product' => 'PVC_DN110', 'quantity' => 150],
                ],
            ],
            [
                'key' => 'CANCELLED',
                'request_no' => 'AC-YC-CANCELLED-0001',
                'status' => 'CANCELLED',
                'center' => 'AC_HP',
                'customer' => 'NO_EMAIL',
                'creator' => 'center_accept',
                'invoice_no' => 'AC-HD-CANCEL-001',
                'delivery_date' => now()->subDays(6)->toDateString(),
                'require_hard_copy' => false,
                'hard_copy_quantity' => 0,
                'note' => '[DVKH tra lai]: Thieu email khach hang va thong tin cong trinh.',
                'created_at' => now()->subDays(3),
                'details' => [
                    ['product' => 'PVC_DN110', 'quantity' => 60],
                ],
            ],
        ];

        $requests = [];

        foreach ($cases as $case) {
            $request = CertificateRequest::updateOrCreate(
                ['request_no' => $case['request_no']],
                [
                    'distribution_center_id' => $centers[$case['center']]->id,
                    'customer_id' => $customers[$case['customer']]->id,
                    'delivery_date' => $case['delivery_date'],
                    'invoice_no' => $case['invoice_no'],
                    'require_hard_copy' => $case['require_hard_copy'],
                    'hard_copy_quantity' => $case['hard_copy_quantity'],
                    'note' => $case['note'],
                    'status' => $case['status'],
                    'created_by' => $users[$case['creator']]->id,
                ]
            );

            $request->details()->delete();
            foreach ($case['details'] as $detail) {
                $request->details()->create([
                    'product_id' => $products[$detail['product']]->id,
                    'quantity' => $detail['quantity'],
                ]);
            }

            $request->forceFill([
                'created_at' => Carbon::parse($case['created_at']),
                'updated_at' => Carbon::parse($case['created_at'])->addMinutes(15),
            ])->save();

            $requests[$case['key']] = $request->fresh(['details.product']);
        }

        return $requests;
    }

    private function seedQualityCertificates(array $requests, array $users, array $products): array
    {
        $cases = [
            'UNSIGNED' => [
                'certificate_no' => 'AC-CNCL-UNSIGNED-0001',
                'request' => 'PTN_PROCESSING',
                'created_by' => 'ptn_accept',
                'signed_at' => null,
                'signed_by' => null,
                'print_count' => 0,
            ],
            'SIGNED' => [
                'certificate_no' => 'AC-CNCL-SIGNED-0001',
                'request' => 'SIGNED',
                'created_by' => 'ptn_accept',
                'signed_at' => now()->subHours(10),
                'signed_by' => 'Acceptance PTN',
                'print_count' => 2,
            ],
            'COMPLETED' => [
                'certificate_no' => 'AC-CNCL-COMPLETED-0001',
                'request' => 'COMPLETED',
                'created_by' => 'ptn_accept',
                'signed_at' => now()->subDay(),
                'signed_by' => 'Acceptance PTN',
                'print_count' => 0,
            ],
        ];

        $certificates = [];
        foreach ($cases as $key => $case) {
            $certificate = QualityCertificate::updateOrCreate(
                ['certificate_no' => $case['certificate_no']],
                [
                    'certificate_request_id' => $requests[$case['request']]->id,
                    'created_by' => $users[$case['created_by']]->id,
                    'signed_at' => $case['signed_at'],
                    'signed_by' => $case['signed_by'],
                    'pdf_path' => 'acceptance/' . strtolower($case['certificate_no']) . '.pdf',
                    'print_count' => $case['print_count'],
                ]
            );

            $certificate->details()->delete();

            foreach ($requests[$case['request']]->details as $detail) {
                $product = $detail->product ?: $products['HDPE_DN90'];
                $certificate->details()->create([
                    'product_id' => $product->id,
                    'quantity' => $detail->quantity,
                    'nominal_size' => $product->nominal_size,
                    'technical_requirements' => $product->technical_requirements,
                    'quality_standard' => $product->qualityStandard?->code,
                ]);
            }

            $certificates[$key] = $certificate->fresh(['details']);
        }

        return $certificates;
    }

    private function seedPrintLogs(array $certificates, array $users): void
    {
        if (!isset($certificates['SIGNED'])) {
            return;
        }

        PrintLog::where('quality_certificate_id', $certificates['SIGNED']->id)->delete();

        foreach ([1, 2] as $printNo) {
            PrintLog::create([
                'quality_certificate_id' => $certificates['SIGNED']->id,
                'user_id' => $users['ptn_accept']->id,
                'reason' => $printNo === 1
                    ? 'Acceptance in ban ky tuoi lan dau'
                    : 'Acceptance in lai do khach hang yeu cau',
                'print_no' => $printNo,
            ])->forceFill([
                'created_at' => now()->subHours(8 - $printNo),
                'updated_at' => now()->subHours(8 - $printNo),
            ])->save();
        }
    }

    private function seedLoginLogs(array $users): void
    {
        $logs = [
            ['user' => 'admin_accept', 'username' => 'admin_accept', 'status' => 'success', 'message' => 'Acceptance dang nhap thanh cong', 'ip' => '10.10.10.1'],
            ['user' => 'center_accept', 'username' => 'center_accept', 'status' => 'success', 'message' => 'Acceptance trung tam dang nhap', 'ip' => '10.10.10.2'],
            ['user' => 'dvkh_accept', 'username' => 'dvkh_accept', 'status' => 'logout', 'message' => 'Acceptance DVKH dang xuat', 'ip' => '10.10.10.3'],
            ['user' => null, 'username' => 'wrong_password', 'status' => 'failed', 'message' => 'Acceptance sai mat khau', 'ip' => '10.10.10.4'],
            ['user' => 'inactive_accept', 'username' => 'inactive_accept', 'status' => 'failed', 'message' => 'Acceptance tai khoan bi khoa', 'ip' => '10.10.10.5'],
        ];

        LoginLog::whereIn('username', collect($logs)->pluck('username')->all())->delete();

        foreach ($logs as $index => $log) {
            LoginLog::create([
                'user_id' => $log['user'] ? $users[$log['user']]->id : null,
                'username' => $log['username'],
                'ip_address' => $log['ip'],
                'user_agent' => 'AcceptanceBrowser/1.0',
                'status' => $log['status'],
                'message' => $log['message'],
                'logged_at' => now()->subMinutes(60 - ($index * 5)),
            ]);
        }
    }

    private function seedActivityLogs(array $users, array $requests): void
    {
        DB::table(config('activitylog.table_name', 'activity_log'))
            ->where('description', 'like', 'Acceptance:%')
            ->delete();

        $now = now();
        $rows = [
            [
                'log_name' => 'Yeu cau cap phieu',
                'description' => 'Acceptance: Trung tam tao yeu cau AC-YC-WAIT-DVKH-0001',
                'event' => 'create',
                'causer' => $users['center_accept'],
                'subject' => $requests['WAIT_DVKH'],
                'properties' => ['action' => 'create', 'criteria' => 'crud_history'],
            ],
            [
                'log_name' => 'DVKH kiem tra yeu cau',
                'description' => 'Acceptance: DVKH xac nhan yeu cau chuyen PTN',
                'event' => 'approve',
                'causer' => $users['dvkh_accept'],
                'subject' => $requests['WAIT_PTN'],
                'properties' => ['action' => 'approve', 'criteria' => 'workflow'],
            ],
            [
                'log_name' => 'DVKH kiem tra yeu cau',
                'description' => 'Acceptance: DVKH tra lai yeu cau thieu email',
                'event' => 'reject',
                'causer' => $users['dvkh_accept'],
                'subject' => $requests['CANCELLED'],
                'properties' => ['action' => 'reject', 'criteria' => 'workflow'],
            ],
            [
                'log_name' => 'Phieu CNCL',
                'description' => 'Acceptance: PTN ky phat hanh phieu AC-CNCL-SIGNED-0001',
                'event' => 'sign',
                'causer' => $users['ptn_accept'],
                'subject' => $requests['SIGNED'],
                'properties' => ['action' => 'sign', 'criteria' => 'certificate'],
            ],
        ];

        foreach ($rows as $index => $row) {
            DB::table(config('activitylog.table_name', 'activity_log'))->insert([
                'log_name' => $row['log_name'],
                'description' => $row['description'],
                'subject_type' => get_class($row['subject']),
                'subject_id' => $row['subject']->id,
                'event' => $row['event'],
                'causer_type' => get_class($row['causer']),
                'causer_id' => $row['causer']->id,
                'properties' => json_encode($row['properties'], JSON_UNESCAPED_UNICODE),
                'batch_uuid' => null,
                'created_at' => $now->copy()->subMinutes(40 - ($index * 5)),
                'updated_at' => $now->copy()->subMinutes(40 - ($index * 5)),
            ]);
        }
    }

    private function seedAcceptanceSettings(): void
    {
        $settings = [
            [
                'key' => 'acceptance_test_dataset_version',
                'value' => '2026-06-11',
                'type' => 'string',
                'description' => 'Danh dau bo du lieu nghiem thu tong hop.',
            ],
            [
                'key' => 'auto_send_email_after_sign',
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Tu dong gui email sau khi ky/phat hanh phieu CNCL.',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }

        SlaConfig::updateOrCreate(
            ['code' => 'AC-SLA-FAST-DVKH'],
            [
                'name' => 'Acceptance SLA DVKH canh bao nhanh',
                'process_step' => 'DVKH',
                'warning_minutes' => 30,
                'limit_minutes' => 60,
                'description' => 'Dung de nghiem thu loc/canh bao SLA trong bao cao.',
                'is_active' => true,
            ]
        );
    }
}
