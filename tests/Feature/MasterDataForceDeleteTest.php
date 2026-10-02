<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityStandard;
use App\Models\UrgentReason;
use App\Models\User;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataForceDeleteTest extends TestCase
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

    public function test_admin_can_force_delete_unused_master_data(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $center = DistributionCenter::firstOrFail();

        $group = ProductGroup::create([
            'code' => 'UNUSED-G',
            'name' => 'Unused group',
            'is_active' => true,
        ]);

        $standard = QualityStandard::create([
            'code' => 'UNUSED-STD',
            'name' => 'Unused standard',
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_group_id' => $group->id,
            'quality_standard_id' => $standard->id,
            'product_code' => 'UNUSED-P',
            'product_name' => 'Unused product',
            'unit' => 'm',
            'nominal_size' => 'DN90',
            'technical_requirements' => 'PN10',
            'certificate_type' => 'CNCL',
            'certificate_template' => 'default',
            'is_active' => true,
        ]);

        $urgentReason = UrgentReason::create([
            'code' => 'UNUSED-U',
            'name' => 'Unused urgent reason',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'distribution_center_id' => $center->id,
            'customer_code' => 'UNUSED-C',
            'customer_name' => 'Unused customer',
            'project_name' => 'Unused project',
            'project_address' => 'Unused address',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->delete(route('products.force-destroy', $product))
            ->assertRedirect(route('products.index'));
        $this->actingAs($admin)->delete(route('product-groups.force-destroy', $group))
            ->assertRedirect(route('product-groups.index'));
        $this->actingAs($admin)->delete(route('quality-standards.force-destroy', $standard))
            ->assertRedirect(route('quality-standards.index'));
        $this->actingAs($admin)->delete(route('urgent-reasons.force-destroy', $urgentReason))
            ->assertRedirect(route('urgent-reasons.index'));
        $this->actingAs($admin)->delete(route('customers.force-destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('quality_standards', ['id' => $standard->id]);
        $this->assertDatabaseMissing('urgent_reasons', ['id' => $urgentReason->id]);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_admin_cannot_force_delete_master_data_with_links(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();

        [$group, $standard, $product, $customer, $urgentReason] = $this->createLinkedMasterData($centerUser);

        $this->actingAs($admin)->delete(route('products.force-destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('product-groups.force-destroy', $group))
            ->assertRedirect(route('product-groups.index'))
            ->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('quality-standards.force-destroy', $standard))
            ->assertRedirect(route('quality-standards.index'))
            ->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('urgent-reasons.force-destroy', $urgentReason))
            ->assertRedirect(route('urgent-reasons.index'))
            ->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('customers.force-destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('product_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('quality_standards', ['id' => $standard->id]);
        $this->assertDatabaseHas('urgent_reasons', ['id' => $urgentReason->id]);
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_non_admin_cannot_force_delete_unused_master_data(): void
    {
        $ptn = User::where('username', 'ptn')->firstOrFail();

        $group = ProductGroup::create([
            'code' => 'PTN-BLOCK',
            'name' => 'PTN block group',
            'is_active' => true,
        ]);

        $this->actingAs($ptn)->delete(route('product-groups.force-destroy', $group))
            ->assertForbidden();

        $this->assertDatabaseHas('product_groups', ['id' => $group->id]);
    }

    private function createLinkedMasterData(User $centerUser): array
    {
        $group = ProductGroup::create([
            'code' => 'LINK-G',
            'name' => 'Linked group',
            'is_active' => true,
        ]);

        $standard = QualityStandard::create([
            'code' => 'LINK-STD',
            'name' => 'Linked standard',
            'is_active' => true,
        ]);

        $product = Product::create([
            'product_group_id' => $group->id,
            'quality_standard_id' => $standard->id,
            'product_code' => 'LINK-P',
            'product_name' => 'Linked product',
            'unit' => 'm',
            'nominal_size' => 'DN110',
            'technical_requirements' => 'PN10',
            'certificate_type' => 'CNCL',
            'certificate_template' => 'default',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'distribution_center_id' => $centerUser->distribution_center_id,
            'customer_code' => 'LINK-C',
            'customer_name' => 'Linked customer',
            'project_name' => 'Linked project',
            'project_address' => 'Linked address',
            'is_active' => true,
        ]);

        $urgentReason = UrgentReason::create([
            'code' => 'LINK-U',
            'name' => 'Linked urgent reason',
            'is_active' => true,
        ]);

        $request = CertificateRequest::create([
            'request_no' => 'YC-LINK-0001',
            'request_type' => 'NORMAL',
            'distribution_center_id' => $centerUser->distribution_center_id,
            'customer_id' => $customer->id,
            'delivery_date' => '2026-10-01',
            'invoice_no' => 'INV-LINK-0001',
            'require_hard_copy' => false,
            'hard_copy_quantity' => 0,
            'is_urgent' => true,
            'urgent_reason_id' => $urgentReason->id,
            'requester_name' => 'Tester',
            'customer_commitment_confirmed' => true,
            'status' => 'WAIT_DVKH',
            'submitted_at' => now(),
            'created_by' => $centerUser->id,
        ]);

        $request->details()->create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        return [$group, $standard, $product, $customer, $urgentReason];
    }
}
