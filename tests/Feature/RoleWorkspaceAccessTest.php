<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\PrintLog;
use App\Models\QualityCertificate;
use App\Models\QualityStandard;
use App\Models\SalesUnit;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserNotification;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleWorkspaceAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            DistributionCenterSeeder::class,
            UserSeeder::class,
        ]);
    }

    public function test_seeded_test_accounts_can_login_and_open_dashboard(): void
    {
        foreach ($this->testUsernames() as $username) {
            $user = User::where('username', $username)->firstOrFail();
            $deviceUid = 'test-device-' . $username;

            if ($user->hasRole('TrungTam')) {
                UserDevice::create([
                    'user_id' => $user->id,
                    'device_uid' => $deviceUid,
                    'device_name' => 'Máy test ' . $username,
                    'status' => UserDevice::STATUS_APPROVED,
                    'requested_at' => now(),
                    'approved_at' => now(),
                ]);
            }

            $response = $this
                ->withCookie('cncl_device_uid', $deviceUid)
                ->post('/login', [
                    'username' => $username,
                    'password' => '123123123',
                ]);

            $response->assertRedirect(route('dashboard', absolute: false));
            $this->assertAuthenticatedAs($user);

            $this->get('/dashboard')->assertOk();
            $this->post('/logout')->assertRedirect('/');
        }
    }

    public function test_distribution_center_account_must_wait_for_device_approval(): void
    {
        $user = User::where('username', 'trungtam_np')->firstOrFail();

        $this->post('/login', [
            'username' => 'trungtam_np',
            'password' => '123123123',
        ])
            ->assertSessionHasErrors('username')
            ->assertRedirect('/');

        $this->assertGuest();

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'status' => UserDevice::STATUS_PENDING,
        ]);
    }

    public function test_new_login_device_notifies_device_admins(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();

        $this->post('/login', [
            'username' => 'trungtam_np',
            'password' => '123123123',
        ])
            ->assertSessionHasErrors('username')
            ->assertRedirect('/');

        $device = UserDevice::where('user_id', $centerUser->id)
            ->where('status', UserDevice::STATUS_PENDING)
            ->firstOrFail();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => 'login_device_pending',
        ]);

        $this->actingAs($admin)
            ->get(route('user-devices.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee((string) $device->id)
            ->assertSee('Thiết bị đăng nhập mới đang chờ duyệt');

        $notification = UserNotification::where('user_id', $admin->id)
            ->where('type', 'login_device_pending')
            ->firstOrFail();

        $this->assertSame($device->id, $notification->data['device_id']);
    }

    public function test_device_control_can_be_disabled_for_distribution_center_login(): void
    {
        SystemSetting::create([
            'key' => 'login_device_control_enabled',
            'value' => '0',
            'type' => 'boolean',
            'description' => 'Test disable device control',
        ]);

        $user = User::where('username', 'trungtam_np')->firstOrFail();

        $this->post('/login', [
            'username' => 'trungtam_np',
            'password' => '123123123',
        ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('user_devices', [
            'user_id' => $user->id,
        ]);
    }

    public function test_blocked_approved_device_is_logged_out_on_next_request(): void
    {
        $user = User::where('username', 'trungtam_np')->firstOrFail();
        $deviceUid = 'approved-then-blocked-device';

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_uid' => $deviceUid,
            'device_name' => 'Máy sẽ bị khóa',
            'status' => UserDevice::STATUS_APPROVED,
            'requested_at' => now(),
            'approved_at' => now(),
            'last_used_at' => now()->subMinutes(10),
        ]);

        $this
            ->withCookie('cncl_device_uid', $deviceUid)
            ->post('/login', [
                'username' => 'trungtam_np',
                'password' => '123123123',
            ])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);

        $device->update([
            'status' => UserDevice::STATUS_BLOCKED,
            'blocked_at' => now(),
        ]);

        $this
            ->withCookie('cncl_device_uid', $deviceUid)
            ->get('/dashboard')
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_deactivating_user_revokes_existing_sessions(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();

        $this->createSessionRow('session-a', $centerUser);
        $this->createSessionRow('session-b', $centerUser);

        $this->actingAs($admin)
            ->post(route('users.toggle-active', $centerUser))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('sessions', [
            'id' => 'session-a',
            'user_id' => $centerUser->id,
        ]);
        $this->assertDatabaseMissing('sessions', [
            'id' => 'session-b',
            'user_id' => $centerUser->id,
        ]);
    }

    public function test_reset_password_revokes_existing_sessions(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();

        $this->createSessionRow('session-old', $centerUser);

        $this->actingAs($admin)
            ->post(route('users.reset-password', $centerUser), [
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('users.edit', $centerUser));

        $this->assertDatabaseMissing('sessions', [
            'id' => 'session-old',
            'user_id' => $centerUser->id,
        ]);
    }

    public function test_inactive_authenticated_user_is_logged_out_on_next_request(): void
    {
        $user = User::where('username', 'dvkh')->firstOrFail();
        $user->update(['is_active' => false]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login', absolute: false))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_role_route_access_matrix_matches_workspace_permissions(): void
    {
        SystemSetting::create([
            'key' => 'login_device_control_enabled',
            'value' => '0',
            'type' => 'boolean',
            'description' => 'Test route permissions without device gate',
        ]);

        $matrix = [
            'admin' => [
                'allow' => ['/dashboard', '/users', '/user-devices', '/role-permissions', '/reports/summary', '/reports/hard-copy', '/activity-logs'],
                'deny' => [],
            ],
            'lanhdao' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/reports/summary', '/reports/hard-copy', '/activity-logs'],
                'deny' => ['/users', '/role-permissions', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'trungtam_np' => [
                'allow' => ['/dashboard', '/customers', '/certificate-requests', '/quality-certificates'],
                'deny' => ['/users', '/user-devices', '/role-permissions', '/reports/summary', '/reports/hard-copy', '/activity-logs', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'dvkh' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/dvkh/requests', '/user-devices'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/reports/hard-copy', '/activity-logs', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'ptn' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/ptn/requests', '/ptn/requests/direct-create', '/reports/summary', '/reports/hard-copy'],
                'deny' => ['/users', '/role-permissions', '/activity-logs', '/dvkh/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'truongptn' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign', '/print-logs', '/reports/summary', '/reports/hard-copy'],
                'deny' => ['/users', '/role-permissions', '/activity-logs', '/dvkh/requests', '/ptn/requests'],
            ],
            'viewer' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/reports/hard-copy', '/activity-logs', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
        ];

        foreach ($matrix as $username => $rules) {
            $user = User::where('username', $username)->firstOrFail();

            foreach ($rules['allow'] as $path) {
                $this->actingAs($user)
                    ->get($path)
                    ->assertOk();
            }

            foreach ($rules['deny'] as $path) {
                $this->actingAs($user)
                    ->get($path)
                    ->assertForbidden();
            }
        }
    }

    public function test_distribution_center_user_cannot_open_other_center_request_or_certificate(): void
    {
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $tpUser = User::where('username', 'trungtam_tp')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();
        $npCenter = DistributionCenter::where('code', 'NP')->firstOrFail();
        $tpCenter = DistributionCenter::where('code', 'TP')->firstOrFail();

        $npRequest = $this->createRequestForCenter($npCenter, $npUser, 'YC-NP-001');
        $tpRequest = $this->createRequestForCenter($tpCenter, $tpUser, 'YC-TP-001');
        $tpCertificate = QualityCertificate::create([
            'certificate_no' => 'CNCL-TP-001',
            'certificate_request_id' => $tpRequest->id,
            'status' => 'DRAFT',
            'created_by' => $admin->id,
            'signed_at' => null,
            'signed_by' => null,
            'pdf_path' => null,
            'print_count' => 0,
        ]);

        $this->actingAs($npUser)
            ->get(route('certificate-requests.show', $npRequest))
            ->assertOk();

        $this->actingAs($npUser)
            ->get(route('certificate-requests.show', $tpRequest))
            ->assertForbidden();

        $this->actingAs($npUser)
            ->get(route('quality-certificates.show', $tpCertificate))
            ->assertForbidden();
    }

    public function test_internal_processing_roles_cannot_view_center_drafts_before_submission(): void
    {
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $ptn = User::where('username', 'ptn')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();
        $npCenter = DistributionCenter::where('code', 'NP')->firstOrFail();

        $draftRequest = $this->createRequestForCenter($npCenter, $npUser, 'YC-DRAFT-PRIVATE', 'DRAFT');

        foreach ([$dvkh, $ptn] as $user) {
            $this->actingAs($user)
                ->get(route('certificate-requests.index', ['status' => 'DRAFT']))
                ->assertOk()
                ->assertDontSee($draftRequest->request_no);

            $this->actingAs($user)
                ->get(route('certificate-requests.show', $draftRequest))
                ->assertForbidden();
        }

        $this->actingAs($npUser)
            ->get(route('certificate-requests.show', $draftRequest))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('certificate-requests.index', ['status' => 'DRAFT']))
            ->assertOk()
            ->assertSee($draftRequest->request_no);
    }

    public function test_summary_report_shows_monthly_certificate_counts_by_distribution_center(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $tpUser = User::where('username', 'trungtam_tp')->firstOrFail();
        $npCenter = DistributionCenter::where('code', 'NP')->firstOrFail();
        $tpCenter = DistributionCenter::where('code', 'TP')->firstOrFail();

        $npRequest = $this->createRequestForCenter($npCenter, $npUser, 'YC-REPORT-NP', 'COMPLETED');
        $tpRequest = $this->createRequestForCenter($tpCenter, $tpUser, 'YC-REPORT-TP', 'COMPLETED');

        $this->createIssuedCertificateForRequest($npRequest, 'CNCL-REPORT-NP-1', '2026-01-15 08:00:00');
        $this->createIssuedCertificateForRequest($npRequest, 'CNCL-REPORT-NP-2', '2026-01-20 08:00:00');
        $this->createIssuedCertificateForRequest($tpRequest, 'CNCL-REPORT-TP-1', '2026-02-10 08:00:00');

        $this->actingAs($admin)
            ->get(route('reports.summary', ['report_year' => 2026]))
            ->assertOk()
            ->assertSee('Thống kê số lượng phiếu đã phát hành theo trung tâm - 2026')
            ->assertSee('NP - ' . $npCenter->name)
            ->assertSee('TP - ' . $tpCenter->name)
            ->assertSee('Tổng năm: 3');

        $this->actingAs($admin)
            ->get(route('reports.summary', [
                'report_year' => 2026,
                'distribution_center_id' => $npCenter->id,
            ]))
            ->assertOk()
            ->assertSee('NP - ' . $npCenter->name)
            ->assertDontSee('<strong>TP</strong> - ' . $tpCenter->name, false)
            ->assertSee('Tổng năm: 2');
    }

    public function test_summary_report_can_filter_by_certificate_status(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $npCenter = DistributionCenter::where('code', 'NP')->firstOrFail();

        $readyRequest = $this->createRequestForCenter($npCenter, $npUser, 'YC-REPORT-READY', 'PTN_PROCESSING');
        $revokedRequest = $this->createRequestForCenter($npCenter, $npUser, 'YC-REPORT-REVOKED', 'COMPLETED');

        $this->createCertificateForRequest($readyRequest, 'CNCL-REPORT-READY', 'READY_TO_SIGN');
        $this->createCertificateForRequest($revokedRequest, 'CNCL-REPORT-REVOKED', 'REVOKED');

        $this->actingAs($admin)
            ->get(route('reports.summary', ['certificate_status' => 'READY_TO_SIGN']))
            ->assertOk()
            ->assertSee('Trạng thái phiếu CNCL')
            ->assertSee('CNCL-REPORT-READY')
            ->assertSee('Chờ gửi ký số')
            ->assertDontSee('CNCL-REPORT-REVOKED');

        $this->actingAs($admin)
            ->get(route('reports.summary', ['certificate_status' => 'REVOKED']))
            ->assertOk()
            ->assertSee('CNCL-REPORT-REVOKED')
            ->assertSee('Đã hủy / thu hồi')
            ->assertDontSee('CNCL-REPORT-READY');
    }

    public function test_request_and_certificate_lists_can_filter_and_search_by_sales_unit(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $tpUser = User::where('username', 'trungtam_tp')->firstOrFail();
        $npCenter = DistributionCenter::where('code', 'NP')->firstOrFail();
        $tpCenter = DistributionCenter::where('code', 'TP')->firstOrFail();

        $npSalesUnit = SalesUnit::create([
            'distribution_center_id' => $npCenter->id,
            'code' => 'NP-DVBH-SEARCH',
            'name' => 'Đơn vị bán hàng Nam Phương Search',
            'is_active' => true,
        ]);
        $tpSalesUnit = SalesUnit::create([
            'distribution_center_id' => $tpCenter->id,
            'code' => 'TP-DVBH-HIDE',
            'name' => 'Đơn vị bán hàng Tam Phước Hide',
            'is_active' => true,
        ]);

        $npRequest = $this->createRequestForCenter($npCenter, $npUser, 'YC-SALES-UNIT-NP', 'WAIT_DVKH');
        $tpRequest = $this->createRequestForCenter($tpCenter, $tpUser, 'YC-SALES-UNIT-TP', 'WAIT_DVKH');
        $npRequest->update([
            'sales_unit_id' => $npSalesUnit->id,
            'require_hard_copy' => true,
            'hard_copy_quantity' => 2,
        ]);
        $tpRequest->update(['sales_unit_id' => $tpSalesUnit->id]);

        $this->createCertificateForRequest($npRequest, 'CNCL-SALES-UNIT-NP', 'READY_TO_SIGN');
        $this->createCertificateForRequest($tpRequest, 'CNCL-SALES-UNIT-TP', 'READY_TO_SIGN');

        $this->actingAs($admin)
            ->get(route('certificate-requests.index', [
                'status_group' => 'all',
                'sales_unit_id' => $npSalesUnit->id,
            ]))
            ->assertOk()
            ->assertSee('NP-DVBH-SEARCH')
            ->assertSee('Đơn vị bán hàng Nam Phương Search')
            ->assertDontSee('YC-SALES-UNIT-TP');

        $this->actingAs($admin)
            ->get(route('quality-certificates.index', [
                'keyword' => 'Nam Phương Search',
            ]))
            ->assertOk()
            ->assertSee('CNCL-SALES-UNIT-NP')
            ->assertSee('NP-DVBH-SEARCH')
            ->assertDontSee('CNCL-SALES-UNIT-TP');

        $this->actingAs($admin)
            ->get(route('quality-certificates.index', [
                'hard_copy' => '1',
            ]))
            ->assertOk()
            ->assertSee('CNCL-SALES-UNIT-NP')
            ->assertSee('2 bản')
            ->assertDontSee('CNCL-SALES-UNIT-TP');

        $this->actingAs($admin)
            ->get(route('quality-certificates.index', [
                'sort' => 'hard_copy',
                'direction' => 'desc',
            ]))
            ->assertOk()
            ->assertSeeInOrder(['CNCL-SALES-UNIT-NP', 'CNCL-SALES-UNIT-TP']);
    }

    public function test_product_options_prioritize_exact_and_normalized_product_code_matches(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        [$exactMatch, $normalizedMatch] = $this->createProductSearchFixtures();

        $response = $this->actingAs($admin)
            ->getJson(route('certificate-requests.product-options', ['q' => 'T110']))
            ->assertOk();

        $results = $response->json('results');

        $this->assertSame($exactMatch->id, $results[0]['id']);
        $this->assertSame($normalizedMatch->id, $results[1]['id']);
    }

    public function test_product_index_prioritizes_exact_and_normalized_product_code_matches(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        [$exactMatch, $normalizedMatch] = $this->createProductSearchFixtures();

        $this->actingAs($admin)
            ->get(route('products.index', ['keyword' => 'T110']))
            ->assertOk()
            ->assertSeeInOrder([
                $exactMatch->product_code,
                $normalizedMatch->product_code,
                'A-T110-LONG',
            ]);
    }

    public function test_product_options_do_not_keep_previous_code_when_new_code_search_matches_dn_text(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        [$n27Product, $t27Product, $l27Product] = $this->createN27ProductSearchFixtures();

        $response = $this->actingAs($admin)
            ->getJson(route('certificate-requests.product-options', ['q' => 'N27']))
            ->assertOk();

        $ids = collect($response->json('results'))->pluck('id')->all();

        $this->assertContains($n27Product->id, $ids);
        $this->assertNotContains($t27Product->id, $ids);
        $this->assertNotContains($l27Product->id, $ids);
    }

    public function test_report_permission_allows_truong_ptn_to_open_summary_report(): void
    {
        $truongPtn = User::where('username', 'truongptn')->firstOrFail();
        $truongPtn->givePermissionTo(['report.view', 'report.export']);

        $this->actingAs($truongPtn)
            ->get(route('reports.summary', ['report_year' => 2026]))
            ->assertOk()
            ->assertSee('Báo cáo tổng hợp');
    }

    public function test_hard_copy_report_filters_by_date_center_sales_unit_print_and_keyword(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $tpUser = User::where('username', 'trungtam_tp')->firstOrFail();
        $npCenter = DistributionCenter::where('code', 'NP')->firstOrFail();
        $tpCenter = DistributionCenter::where('code', 'TP')->firstOrFail();

        $npSalesA = $this->createSalesUnitForCenter($npCenter, 'NP-HC-A', 'Hard Copy Sales NP A');
        $npSalesB = $this->createSalesUnitForCenter($npCenter, 'NP-HC-B', 'Hard Copy Sales NP B');
        $tpSales = $this->createSalesUnitForCenter($tpCenter, 'TP-HC-A', 'Hard Copy Sales TP A');

        $singleOnly = $this->createHardCopyReportCertificate(
            $npCenter,
            $npUser,
            'YC-HC-SINGLE',
            'CNCL-HC-SINGLE',
            $npSalesA,
            '2026-10-01 08:00:00',
            3,
            4,
            2
        );
        $batchOnly = $this->createHardCopyReportCertificate(
            $npCenter,
            $npUser,
            'YC-HC-BATCH',
            'CNCL-HC-BATCH',
            $npSalesB,
            '2026-10-02 08:00:00',
            2,
            5,
            3
        );
        $printedBoth = $this->createHardCopyReportCertificate(
            $npCenter,
            $npUser,
            'YC-HC-BOTH',
            'CNCL-HC-BOTH',
            $npSalesA,
            '2026-10-03 08:00:00',
            1,
            6,
            4
        );
        $emergencyOnly = $this->createHardCopyReportCertificate(
            $npCenter,
            $npUser,
            'YC-HC-EMERGENCY',
            'CNCL-HC-EMERGENCY',
            $npSalesA,
            '2026-10-04 08:00:00',
            1,
            2,
            1
        );
        $tpCertificate = $this->createHardCopyReportCertificate(
            $tpCenter,
            $tpUser,
            'YC-HC-TP',
            'CNCL-HC-TP',
            $tpSales,
            '2026-10-05 08:00:00',
            1,
            2,
            1
        );

        $this->createPrintLog($singleOnly, $admin, 'single', 'normal');
        $this->createPrintLog($batchOnly, $admin, 'batch', 'normal');
        $this->createPrintLog($printedBoth, $admin, 'single', 'normal');
        $this->createPrintLog($printedBoth, $admin, 'batch', 'normal');
        $this->createPrintLog($emergencyOnly, $admin, 'single', 'emergency');
        $this->createPrintLog($tpCertificate, $admin, 'single', 'normal');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-SINGLE')
            ->assertSee('CNCL-HC-BATCH')
            ->assertSee('CNCL-HC-BOTH')
            ->assertSee('CNCL-HC-EMERGENCY')
            ->assertSee('CNCL-HC-TP')
            ->assertSee('Định nghĩa số liệu')
            ->assertSee('Thống kê ký tươi theo Đơn vị bán hàng')
            ->assertSee('Thống kê ký tươi theo tháng ký số')
            ->assertSee('Lượt bấm in ghi nhận');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-02',
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-SINGLE')
            ->assertSee('CNCL-HC-BATCH')
            ->assertDontSee('CNCL-HC-BOTH');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'distribution_center_id' => $tpCenter->id,
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-TP')
            ->assertDontSee('CNCL-HC-SINGLE');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'sales_unit_id' => $npSalesB->id,
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-BATCH')
            ->assertSee('NP-HC-B')
            ->assertDontSee('CNCL-HC-SINGLE');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'print_template' => 'single',
                'distribution_center_id' => $npCenter->id,
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-SINGLE')
            ->assertSee('CNCL-HC-BOTH')
            ->assertDontSee('CNCL-HC-BATCH')
            ->assertDontSee('CNCL-HC-EMERGENCY');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'print_status' => 'not_printed',
                'distribution_center_id' => $npCenter->id,
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-EMERGENCY')
            ->assertDontSee('CNCL-HC-SINGLE');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'print_status' => 'printed_batch',
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-BATCH')
            ->assertDontSee('CNCL-HC-BOTH')
            ->assertDontSee('CNCL-HC-SINGLE');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'print_status' => 'printed_both',
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-BOTH')
            ->assertDontSee('CNCL-HC-BATCH');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy', [
                'keyword' => 'NP-HC-B',
            ]))
            ->assertOk()
            ->assertSee('CNCL-HC-BATCH')
            ->assertDontSee('CNCL-HC-SINGLE');

        $this->actingAs($admin)
            ->get(route('reports.hard-copy.export', [
                'distribution_center_id' => $npCenter->id,
            ]))
            ->assertOk()
            ->assertDownload('bao_cao_ky_tuoi.xlsx');
    }

    private function createRequestForCenter(
        DistributionCenter $center,
        User $creator,
        string $requestNo,
        string $status = 'WAIT_DVKH'
    ): CertificateRequest {
        $customer = Customer::create([
            'distribution_center_id' => $center->id,
            'customer_code' => 'KH-' . $center->code . '-' . $requestNo,
            'customer_name' => 'Khach hang ' . $center->code,
            'project_name' => 'Cong trinh ' . $center->code,
            'is_active' => true,
        ]);

        return CertificateRequest::create([
            'request_no' => $requestNo,
            'distribution_center_id' => $center->id,
            'customer_id' => $customer->id,
            'delivery_date' => now()->toDateString(),
            'invoice_no' => 'INV-' . $center->code,
            'require_hard_copy' => false,
            'hard_copy_quantity' => 0,
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }

    private function createIssuedCertificateForRequest(
        CertificateRequest $request,
        string $certificateNo,
        string $signedAt
    ): QualityCertificate {
        return QualityCertificate::create([
            'certificate_no' => $certificateNo,
            'certificate_request_id' => $request->id,
            'status' => 'ISSUED',
            'created_by' => $request->created_by,
            'signed_at' => $signedAt,
            'signed_by' => 'Truong PTN',
            'pdf_path' => 'quality-certificates/report-test-' . $request->id . '.pdf',
            'print_count' => 0,
        ]);
    }

    private function createCertificateForRequest(
        CertificateRequest $request,
        string $certificateNo,
        string $status
    ): QualityCertificate {
        return QualityCertificate::create([
            'certificate_no' => $certificateNo,
            'certificate_request_id' => $request->id,
            'status' => $status,
            'created_by' => $request->created_by,
            'print_count' => 0,
        ]);
    }

    private function createSalesUnitForCenter(
        DistributionCenter $center,
        string $code,
        string $name
    ): SalesUnit {
        return SalesUnit::create([
            'distribution_center_id' => $center->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function createHardCopyReportCertificate(
        DistributionCenter $center,
        User $creator,
        string $requestNo,
        string $certificateNo,
        SalesUnit $salesUnit,
        string $signedAt,
        int $hardCopyQuantity,
        int $singlePageCount,
        int $batchPageCount
    ): QualityCertificate {
        $request = $this->createRequestForCenter($center, $creator, $requestNo, 'COMPLETED');
        $request->update([
            'sales_unit_id' => $salesUnit->id,
            'delivery_date' => '2026-10-01',
            'invoice_no' => 'INV-' . $certificateNo,
            'require_hard_copy' => true,
            'hard_copy_quantity' => $hardCopyQuantity,
        ]);

        $certificate = $this->createIssuedCertificateForRequest($request, $certificateNo, $signedAt);
        $certificate->update([
            'hard_copy_single_page_count' => $singlePageCount,
            'hard_copy_batch_page_count' => $batchPageCount,
        ]);

        return $certificate;
    }

    private function createPrintLog(
        QualityCertificate $certificate,
        User $user,
        string $template,
        string $mode
    ): PrintLog {
        return PrintLog::create([
            'quality_certificate_id' => $certificate->id,
            'user_id' => $user->id,
            'reason' => 'Test print log',
            'print_no' => 1,
            'print_template' => $template,
            'print_mode' => $mode,
        ]);
    }

    private function createProductSearchFixtures(): array
    {
        $group = ProductGroup::firstOrCreate(
            ['code' => 'TEST-SEARCH'],
            [
                'name' => 'Nhóm test tìm kiếm',
                'is_active' => true,
            ]
        );
        $standard = QualityStandard::firstOrCreate(
            ['code' => 'ISO-SEARCH'],
            [
                'name' => 'Tiêu chuẩn test tìm kiếm',
                'is_active' => true,
            ]
        );

        Product::firstOrCreate(
            ['product_code' => 'A-T110-LONG'],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard->id,
                'product_name' => 'Sản phẩm có chứa T110 nhưng không đúng mã',
                'unit' => 'cái',
                'nominal_size' => 'DN110',
                'is_active' => true,
            ]
        );
        $normalizedMatch = Product::firstOrCreate(
            ['product_code' => 'T-110'],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard->id,
                'product_name' => 'Sản phẩm mã có dấu gạch',
                'unit' => 'cái',
                'nominal_size' => 'DN110',
                'is_active' => true,
            ]
        );
        $exactMatch = Product::firstOrCreate(
            ['product_code' => 'T110'],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard->id,
                'product_name' => 'Sản phẩm mã chính xác',
                'unit' => 'cái',
                'nominal_size' => 'DN110',
                'is_active' => true,
            ]
        );

        return [$exactMatch, $normalizedMatch];
    }

    private function createN27ProductSearchFixtures(): array
    {
        $group = ProductGroup::firstOrCreate(
            ['code' => 'TEST-N27'],
            [
                'name' => 'Nhóm test N27',
                'is_active' => true,
            ]
        );
        $standard = QualityStandard::firstOrCreate(
            ['code' => 'ISO-N27'],
            [
                'name' => 'Tiêu chuẩn test N27',
                'is_active' => true,
            ]
        );

        $n27Product = Product::firstOrCreate(
            ['product_code' => 'N27'],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard->id,
                'product_name' => 'Nối thẳng phun PVC-U DN27 PN10',
                'unit' => 'cái',
                'nominal_size' => 'DN27',
                'is_active' => true,
            ]
        );
        $t27Product = Product::firstOrCreate(
            ['product_code' => 'T27'],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard->id,
                'product_name' => 'Ba chạc 90 độ phun PVC-U DN27 PN10',
                'unit' => 'cái',
                'nominal_size' => 'DN27',
                'is_active' => true,
            ]
        );
        $l27Product = Product::firstOrCreate(
            ['product_code' => 'L27'],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard->id,
                'product_name' => 'Nối góc 90 độ phun PVC-U DN27 PN10',
                'unit' => 'cái',
                'nominal_size' => 'DN27',
                'is_active' => true,
            ]
        );

        return [$n27Product, $t27Product, $l27Product];
    }

    private function createSessionRow(string $id, User $user): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);
    }

    private function testUsernames(): array
    {
        return [
            'admin',
            'lanhdao',
            'viewer',
            'trungtam_np',
            'trungtam_tp',
            'trungtam_hp',
            'trungtam_hd',
            'trungtam_th',
            'dvkh',
            'ptn',
            'truongptn',
        ];
    }
}
