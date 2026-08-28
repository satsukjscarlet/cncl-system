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
                'deny' => ['/users', '/role-permissions', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue'],
            ],
            'trungtam_np' => [
                'allow' => ['/dashboard', '/customers', '/certificate-requests', '/quality-certificates'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue'],
            ],
            'dvkh' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/dvkh/requests'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/ptn/requests', '/quality-certificates/signing-queue'],
            ],
            'ptn' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/ptn/requests', '/ptn/requests/direct-create'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/quality-certificates/signing-queue'],
            ],
            'truongptn' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates', '/quality-certificates/signing-queue', '/print-logs'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/ptn/requests'],
            ],
            'viewer' => [
                'allow' => ['/dashboard', '/certificate-requests', '/quality-certificates'],
                'deny' => ['/users', '/role-permissions', '/reports/summary', '/activity-logs', '/dvkh/requests', '/ptn/requests', '/quality-certificates/signing-queue'],
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

    private function createRequestForCenter(
        DistributionCenter $center,
        User $creator,
        string $requestNo,
        string $status = 'WAIT_DVKH'
    ): CertificateRequest {
        $customer = Customer::create([
            'distribution_center_id' => $center->id,
            'customer_code' => 'KH-' . $center->code,
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
