<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\QualityCertificate;
use App\Models\User;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $response = $this->post('/login', [
                'username' => $username,
                'password' => '123123123',
            ]);

            $response->assertRedirect(route('dashboard', absolute: false));
            $this->assertAuthenticatedAs(User::where('username', $username)->first());

            $this->get('/dashboard')->assertOk();
            $this->post('/logout')->assertRedirect('/');
        }
    }

    public function test_role_route_access_matrix_matches_workspace_permissions(): void
    {
        $matrix = [
            'admin' => [
                'allow' => ['/dashboard', '/users', '/role-permissions', '/reports/summary', '/activity-logs'],
                'deny' => [],
            ],
            'lanhdao' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/reports/summary', '/activity-logs'],
                'deny' => ['/users', '/role-permissions', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'trungtam_np' => [
                'allow' => ['/dashboard', '/customers', '/certificate-requests', '/quality-certificates'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'dvkh' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/dvkh/requests'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'ptn' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/ptn/requests', '/ptn/requests/direct-create'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
            ],
            'truongptn' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign', '/print-logs'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/ptn/requests'],
            ],
            'viewer' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue', '/quality-certificates/ready-to-sign'],
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
