<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityCertificate;
use App\Models\QualityStandard;
use App\Models\User;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionSeeder::class,
            DistributionCenterSeeder::class,
            UserSeeder::class,
        ]);

        $this->product = $this->createProduct();
    }

    public function test_standard_certificate_request_workflow_reaches_signing_queue(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $ptn = User::where('username', 'ptn')->firstOrFail();
        $truongPtn = User::where('username', 'truongptn')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser);

        $this->actingAs($centerUser)
            ->post(route('certificate-requests.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'delivery_date' => '2026-08-08',
                'invoice_no' => 'INV-E2E-001',
                'require_hard_copy' => '0',
                'hard_copy_quantity' => 0,
                'is_urgent' => '0',
                'requester_name' => 'Nguoi tao NP',
                'note' => 'Yeu cau test e2e',
                'product_id' => [$this->product->id],
                'quantity' => [12],
            ])
            ->assertRedirect(route('certificate-requests.index'));

        $certificateRequest = CertificateRequest::with('details')
            ->where('invoice_no', 'INV-E2E-001')
            ->firstOrFail();

        $this->assertSame('WAIT_DVKH', $certificateRequest->status);
        $this->assertSame($centerUser->distribution_center_id, $certificateRequest->distribution_center_id);
        $this->assertSame($customer->id, $certificateRequest->customer_id);
        $this->assertCount(1, $certificateRequest->details);

        $this->actingAs($dvkh)
            ->post(route('dvkh.requests.approve', $certificateRequest))
            ->assertRedirect(route('dvkh.requests.index'));

        $this->assertSame('WAIT_PTN', $certificateRequest->fresh()->status);

        $this->actingAs($ptn)
            ->post(route('ptn.requests.receive-and-create-certificate', $certificateRequest))
            ->assertRedirect();

        $certificateRequest->refresh();
        $certificate = QualityCertificate::with('details')
            ->where('certificate_request_id', $certificateRequest->id)
            ->firstOrFail();

        $this->assertSame('PTN_PROCESSING', $certificateRequest->status);
        $this->assertSame('DRAFT', $certificate->status);
        $this->assertNull($certificate->signed_at);
        $this->assertCount(1, $certificate->details);
        $this->assertSame('DN110', $certificate->details->first()->nominal_size);
        $this->assertSame('AC-TCVN-PVC', $certificate->details->first()->quality_standard);

        $this->actingAs($truongPtn)
            ->get(route('quality-certificates.signing-queue', ['status' => 'READY']))
            ->assertOk()
            ->assertSee($certificate->certificate_no);
    }

    public function test_head_of_lab_can_return_unsigned_certificate_to_dvkh(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $ptn = User::where('username', 'ptn')->firstOrFail();
        $truongPtn = User::where('username', 'truongptn')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-E2E-RETURN');

        $this->actingAs($centerUser)->post(route('certificate-requests.store'), [
            'customer_mode' => 'existing',
            'customer_id' => $customer->id,
            'delivery_date' => '2026-08-08',
            'invoice_no' => 'INV-E2E-RETURN',
            'require_hard_copy' => '0',
            'hard_copy_quantity' => 0,
            'is_urgent' => '0',
            'requester_name' => 'Nguoi tao NP',
            'note' => 'Yeu cau test tra lai',
            'product_id' => [$this->product->id],
            'quantity' => [5],
        ]);

        $certificateRequest = CertificateRequest::where('invoice_no', 'INV-E2E-RETURN')->firstOrFail();

        $this->actingAs($dvkh)->post(route('dvkh.requests.approve', $certificateRequest));
        $this->actingAs($ptn)->post(route('ptn.requests.receive-and-create-certificate', $certificateRequest));

        $certificate = QualityCertificate::where('certificate_request_id', $certificateRequest->id)->firstOrFail();

        $this->actingAs($truongPtn)
            ->post(route('quality-certificates.reject-signature', $certificate), [
                'reject_to' => 'DVKH',
                'rejected_reason' => 'Thong tin khach hang can DVKH xac nhan lai.',
            ])
            ->assertRedirect(route('quality-certificates.show', $certificate));

        $this->assertSame('REJECTED', $certificate->fresh()->status);
        $this->assertSame('DVKH', $certificate->fresh()->rejected_to);
        $this->assertSame('WAIT_DVKH', $certificateRequest->fresh()->status);
        $this->assertStringContainsString('Thong tin khach hang can DVKH xac nhan lai', $certificateRequest->fresh()->note);
    }

    private function createProduct(): Product
    {
        $group = ProductGroup::create([
            'code' => 'TEST-GROUP',
            'name' => 'Nhom test',
            'is_active' => true,
        ]);

        $standard = QualityStandard::create([
            'code' => 'AC-TCVN-PVC',
            'name' => 'Tieu chuan PVC',
            'is_active' => true,
        ]);

        return Product::create([
            'product_group_id' => $group->id,
            'quality_standard_id' => $standard->id,
            'product_code' => 'PVC-DN110',
            'product_name' => 'Ong PVC-U DN110',
            'unit' => 'm',
            'nominal_size' => 'DN110',
            'technical_requirements' => 'PVC-U; kich thuoc danh nghia DN110.',
            'certificate_type' => 'CNCL',
            'certificate_template' => 'default',
            'is_active' => true,
        ]);
    }

    private function createCustomerForCenter(User $user, string $code = 'KH-E2E-NP'): Customer
    {
        return Customer::create([
            'distribution_center_id' => $user->distribution_center_id,
            'customer_code' => $code,
            'customer_name' => 'Acceptance Khach hang NP',
            'customer_address' => 'Dia chi khach hang NP',
            'email' => 'khachhang@example.com',
            'project_name' => 'Acceptance Cong trinh NP',
            'project_address' => 'Dia diem cong trinh NP',
            'is_active' => true,
        ]);
    }
}
