<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLoginDeviceIsApproved;
use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityCertificate;
use App\Models\QualityStandard;
use App\Models\SalesUnit;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\SlaClockService;
use App\Services\WorkReportService;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SalesUnitAccessSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesUnitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $center;

    private User $seller;

    private User $otherSeller;

    private Product $product;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(EnsureLoginDeviceIsApproved::class);
        $this->seed([PermissionSeeder::class, DistributionCenterSeeder::class, UserSeeder::class]);
        $this->center = User::where('username', 'trungtam_np')->firstOrFail();
        foreach (['seller' => 'UNIT-A', 'otherSeller' => 'UNIT-B'] as $property => $code) {
            $unit = SalesUnit::create(['code' => $code, 'name' => $code, 'distribution_center_id' => $this->center->distribution_center_id, 'is_active' => true]);
            $this->$property = User::factory()->create(['distribution_center_id' => $this->center->distribution_center_id]);
            $this->$property->forceFill(['sales_unit_id' => $unit->id])->save();
            $this->$property->assignRole('DonViBanHang');
        }
        $this->customer = Customer::create(['customer_code' => 'KH-UNIT', 'customer_name' => 'Khach hang don vi A',
            'project_name' => 'Cong trinh A', 'project_address' => 'Dia chi A', 'is_active' => true, 'distribution_center_id' => $this->center->distribution_center_id]);
        $this->customer->forceFill(['sales_unit_id' => $this->seller->sales_unit_id])->save();
        $group = ProductGroup::create(['code' => 'UNIT-GROUP', 'name' => 'Group', 'is_active' => true]);
        $standard = QualityStandard::create(['code' => 'UNIT-STD', 'name' => 'Standard', 'is_active' => true]);
        $this->product = Product::create(['product_code' => 'UNIT-PRODUCT', 'product_name' => 'Test product', 'product_group_id' => $group->id,
            'quality_standard_id' => $standard->id, 'unit' => 'm', 'nominal_size' => 'DN27', 'technical_requirements' => 'PN10',
            'certificate_type' => 'CNCL', 'certificate_template' => 'default', 'is_active' => true]);
    }

    public function test_sales_unit_submission_is_not_sent_to_dvkh_and_is_isolated_from_other_units(): void
    {
        $item = $this->createRequest();
        $this->assertSame('WAIT_CENTER', $item->status);
        $this->assertSame('SALES_UNIT', $item->request_source);
        $this->assertNull($item->submitted_at);
        $this->assertNull(app(SlaClockService::class)->startedAt($item, 'DVKH'));
        $this->assertTrue(UserNotification::where('user_id', $this->center->id)->where('type', 'sales_unit_request')->exists());
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $this->assertFalse(UserNotification::where('user_id', $dvkh->id)->exists());
        $this->actingAs($this->otherSeller)->get(route('certificate-requests.show', $item))->assertForbidden();
        $this->get(route('certificate-requests.index', ['status_group' => 'all']))->assertOk()->assertDontSee($item->request_no);
        $this->actingAs($this->center)->get(route('certificate-requests.show', $item))->assertOk()->assertSee('Trả lại đơn vị');
        $this->actingAs($dvkh)->get(route('dvkh.requests.show', $item))->assertForbidden();
        $this->get(route('dvkh.requests.index', ['status' => '']))->assertOk()->assertDontSee($item->request_no);
        $this->get(route('certificate-requests.show', $item))->assertForbidden();
    }

    public function test_return_resubmit_center_approval_and_dvkh_return_keep_the_same_request_and_sla_origin(): void
    {
        $item = $this->createRequest('draft');
        $number = $item->request_no;
        $this->actingAs($this->seller)->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => '1'])->assertRedirect();
        $this->assertSame('WAIT_CENTER', $item->fresh()->status);
        $this->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => '1'])->assertStatus(409);
        $this->actingAs($this->center)->post(route('certificate-requests.return-sales-unit', $item), ['reason' => 'Bo sung thong tin'])->assertRedirect();
        $this->assertSame('RETURNED_SALES_UNIT', $item->fresh()->status);
        $this->actingAs($this->seller)->get(route('certificate-requests.edit', $item))->assertOk();
        $this->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => '1'])->assertRedirect();
        Carbon::setTestNow('2026-11-11 15:00:00');
        try {
            $this->actingAs($this->center)->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => '1'])->assertRedirect();
            $this->assertSame('WAIT_DVKH', $item->fresh()->status);
            $this->assertSame('2026-11-11 15:00:00', $item->fresh()->submitted_at->format('Y-m-d H:i:s'));
            $this->assertSame($this->center->id, $item->fresh()->submitted_by);
            $dvkh = User::where('username', 'dvkh')->firstOrFail();
            $this->actingAs($dvkh)->post(route('dvkh.requests.reject', $item), ['reason' => 'Kiem tra lai'])->assertRedirect();
            $this->assertSame('DRAFT', $item->fresh()->status);
            $this->assertSame('TRUNG_TAM', $item->fresh()->last_returned_to);
            $this->actingAs($this->seller)->get(route('certificate-requests.edit', $item))->assertRedirect();
            $this->actingAs($this->center)->post(route('certificate-requests.return-sales-unit', $item), ['reason' => 'DVKH can bo sung'])->assertRedirect();
            $this->assertSame('RETURNED_SALES_UNIT', $item->fresh()->status);
            $this->assertSame($number, $item->fresh()->request_no);
            $this->actingAs($this->seller)->put(route('certificate-requests.update', $item), $this->payload())
                ->assertRedirect()->assertSessionMissing('error');
            $this->assertSame('WAIT_CENTER', $item->fresh()->status);
            $this->assertNull($item->fresh()->last_returned_to);
            $this->actingAs($this->center)->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => 1])->assertRedirect();
            $this->actingAs($dvkh)->post(route('dvkh.requests.approve', $item))->assertRedirect();
            $ptn = User::where('username', 'ptn')->firstOrFail();
            $this->actingAs($ptn)->post(route('ptn.requests.receive-and-create-certificate', $item))->assertRedirect();
            $certificate = QualityCertificate::where('certificate_request_id', $item->id)->firstOrFail();
            $this->assertSame('CNCL-'.substr($number, 3), $certificate->certificate_no);
            $this->assertSame('WAIT_PTN_MANAGER_APPROVAL', $certificate->status);
            $this->actingAs(User::where('username', 'truongptn')->firstOrFail())
                ->post(route('quality-certificates.approve-for-signing', $certificate))->assertRedirect();
            $this->assertSame('READY_TO_SIGN', $certificate->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_posted_scope_internal_permissions_and_customer_lookups_cannot_escape_unit(): void
    {
        $this->actingAs($this->seller)->post(route('certificate-requests.store'), array_merge($this->payload(), ['sales_unit_id' => $this->otherSeller->sales_unit_id]))->assertForbidden();
        $this->seller->givePermissionTo(['dvkh.process', 'ptn.process', 'certificate.print', 'report.view', 'user.view']);
        foreach (['/dvkh/requests', '/ptn/requests', '/reports/summary', '/reports/work', '/users', '/hard-copy-supplements', '/customers', '/print-logs'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->get(route('certificate-requests.customer-options'))->assertOk()->assertJsonCount(1, 'results');
        $this->actingAs($this->otherSeller)->get(route('certificate-requests.customer-options'))->assertOk()->assertJsonCount(0, 'results');
        $this->actingAs($this->seller)->post(route('certificate-requests.store'), array_merge($this->payload(), [
            'customer_mode' => 'new', 'new_customer_name' => 'Khach moi', 'new_project_name' => 'Cong trinh moi', 'new_project_address' => 'Dia diem moi',
        ]))->assertRedirect()->assertSessionMissing('error');
        $this->assertSame($this->seller->sales_unit_id, Customer::where('customer_name', 'Khach moi')->firstOrFail()->sales_unit_id);
    }

    public function test_only_signed_own_certificates_and_original_signed_pdf_can_be_opened(): void
    {
        Storage::fake('local');
        $item = $this->createRequest();
        $certificate = QualityCertificate::create(['certificate_request_id' => $item->id, 'certificate_no' => 'CNCL-UNIT-1', 'status' => 'WAIT_PTN_MANAGER_APPROVAL', 'created_by' => $this->center->id]);
        $this->actingAs($this->seller)->get(route('quality-certificates.show', $certificate))->assertForbidden();
        $this->get(route('quality-certificates.pdf', $certificate))->assertForbidden();
        $bytes = '%PDF-1.7 original /ByteRange [0 10 20 30]';
        Storage::disk('local')->put('signed-unit.pdf', $bytes);
        $certificate->update(['status' => 'ISSUED', 'signed_at' => now(), 'pdf_path' => 'signed-unit.pdf', 'pades_status' => 'SIGNED_PDF']);
        app(\App\Services\NotificationService::class)->notifyCertificateSigned($certificate);
        $this->assertTrue(UserNotification::where('user_id', $this->seller->id)->where('type', 'certificate_signed')->exists());
        $this->assertFalse(UserNotification::where('user_id', $this->otherSeller->id)->where('type', 'certificate_signed')->exists());
        $this->get(route('quality-certificates.pdf', $certificate))->assertOk()->assertContent($bytes);
        $response = $this->get(route('quality-certificates.show', $certificate))->assertOk()
            ->assertDontSee('id="hard-copy-supplement-panel"', false)->assertDontSee('data-target="#reissueModal"', false);
        $list = $this->get(route('quality-certificates.index'))->assertOk()->assertSee('CNCL-UNIT-1')->assertDontSee('class="bulk-reissue-checkbox"', false);
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) mkdir($directory, 0777, true);
            file_put_contents($directory.'/sales-unit-certificate.html', $response->getContent());
            file_put_contents($directory.'/sales-unit-certificates.html', $list->getContent());
        }
        $this->actingAs($this->otherSeller)->get(route('quality-certificates.pdf', $certificate))->assertForbidden();
        $this->get(route('quality-certificates.index'))->assertOk()->assertDontSee('CNCL-UNIT-1');
    }

    public function test_admin_assigns_unit_and_profile_cannot_change_assignment(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin)->post(route('users.store'), ['name' => 'Seller new', 'username' => 'seller_new', 'role' => 'DonViBanHang',
            'sales_unit_id' => $this->seller->sales_unit_id, 'password' => 'Secure1234', 'password_confirmation' => 'Secure1234', 'is_active' => 1])->assertRedirect(route('users.index'));
        $user = User::where('username', 'seller_new')->firstOrFail();
        $this->assertSame($this->seller->sales_unit_id, $user->sales_unit_id);
        $this->assertSame($this->seller->distribution_center_id, $user->distribution_center_id);
        $this->actingAs($this->seller)->patch(route('profile.update'), ['name' => $this->seller->name, 'email' => $this->seller->email, 'sales_unit_id' => $this->otherSeller->sales_unit_id])->assertRedirect();
        $this->assertNotSame($this->otherSeller->sales_unit_id, $this->seller->fresh()->sales_unit_id);
        $this->seller->salesUnit->update(['is_active' => false]);
        $this->get(route('certificate-requests.index'))->assertForbidden();
    }

    public function test_reports_separate_sales_submission_from_center_submission_and_scopes_are_consistent(): void
    {
        Carbon::setTestNow('2026-11-11 09:00:00');
        try {
            $item = $this->createRequest();
            $admin = User::where('username', 'admin')->firstOrFail();
            $service = app(WorkReportService::class);
            $filters = $service->filters(Request::create('/', 'GET', ['year' => 2026, 'month' => 11]));
            $report = $service->report($filters, $admin);
            $this->assertCount(1, $report['events']->where('kind', 'sales_unit_submit'));
            $this->assertCount(0, $report['events']->where('kind', 'request_submit'));
            $this->actingAs($this->center)->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => 1])->assertRedirect();
            $report = $service->report($filters, $admin);
            $this->assertCount(1, $report['events']->where('kind', 'sales_unit_submit'));
            $this->assertCount(1, $report['events']->where('kind', 'request_submit'));
            $this->assertSame($this->center->id, $report['events']->firstWhere('kind', 'request_submit')['actor_id']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_screens_render_for_seller_and_center_without_cross_unit_options(): void
    {
        $item = $this->createRequest();
        $pages = [];
        $pages['sales-unit-create'] = $this->actingAs($this->seller)->get(route('certificate-requests.create'))->assertOk()->assertSee('Gửi Trung tâm kiểm tra')->assertDontSee('UNIT-B');
        $pages['sales-unit-list'] = $this->get(route('certificate-requests.index'))->assertOk()->assertSee($item->request_no);
        $pages['sales-unit-detail'] = $this->get(route('certificate-requests.show', $item))->assertOk()->assertDontSee('data-target="#submitDraftModal"', false);
        $this->get(route('dashboard'))->assertOk();
        $pages['sales-unit-center-review'] = $this->actingAs($this->center)->get(route('certificate-requests.show', $item))->assertOk()->assertSee('Trả lại đơn vị');
        $pages['sales-unit-center-edit'] = $this->get(route('certificate-requests.edit', $item))->assertOk();
        $this->actingAs(User::where('username', 'admin')->firstOrFail())->get(route('users.edit', $this->seller))->assertOk()->assertSee('user_sales_unit_id');
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            foreach ($pages as $name => $response) {
                file_put_contents($directory.'/'.$name.'.html', $response->getContent());
            }
        }
    }

    public function test_center_old_workflow_and_existing_permissions_are_preserved_by_additive_seeder(): void
    {
        $this->center->givePermissionTo('product.view');
        $before = $this->center->getAllPermissions()->pluck('name')->sort()->values()->all();
        $this->seed(SalesUnitAccessSeeder::class);
        $this->assertSame($before, $this->center->fresh()->getAllPermissions()->pluck('name')->sort()->values()->all());
        $this->actingAs($this->center)->post(route('certificate-requests.store'), $this->payload())->assertRedirect()->assertSessionMissing('error');
        $item = CertificateRequest::latest('id')->firstOrFail();
        $this->assertSame('WAIT_DVKH', $item->status);
        $this->assertNull($item->request_source);
        $this->assertNotNull($item->submitted_at);
    }

    public function test_stale_edits_and_duplicate_checks_cannot_modify_or_expose_other_requests(): void
    {
        $item = $this->createRequest('draft');
        $item->update(['invoice_no' => 'UNIT-INVOICE']);
        $other = $item->replicate();
        $other->request_no = 'YC-OTHER-UNIT';
        $other->sales_unit_id = $this->otherSeller->sales_unit_id;
        $other->save();
        $this->actingAs($this->seller)->getJson(route('certificate-requests.check-invoice', ['invoice_no' => 'UNIT-INVOICE']))
            ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $item->id);
        $this->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => 1])->assertRedirect();
        $this->put(route('certificate-requests.update', $item), array_merge($this->payload(), ['quantity' => [999]]))->assertRedirect();
        $this->assertEquals(12, $item->details()->firstOrFail()->quantity);
        $this->delete(route('certificate-requests.destroy', $item))->assertForbidden();
        $this->assertFalse($item->fresh()->trashed());
        $foreignCenter = User::where('username', 'trungtam_hp')->firstOrFail();
        $this->actingAs($foreignCenter)->post(route('certificate-requests.return-sales-unit', $item), ['reason' => 'Wrong center'])->assertForbidden();
        $this->assertSame('WAIT_CENTER', $item->fresh()->status);
        $other->update(['status' => 'WAIT_DVKH']);
        $this->actingAs(User::where('username', 'dvkh')->firstOrFail())->get(route('dvkh.requests.show', $other))
            ->assertOk()->assertViewHas('invoiceDuplicates', fn ($duplicates) => $duplicates->isEmpty());
    }

    public function test_unit_draft_can_be_shared_with_its_own_accounts_but_center_drafts_remain_private(): void
    {
        $item = $this->createRequest('draft');
        $peer = User::factory()->create(['distribution_center_id' => $this->seller->distribution_center_id]);
        $peer->forceFill(['sales_unit_id' => $this->seller->sales_unit_id])->save();
        $peer->assignRole('DonViBanHang');
        $this->actingAs($peer)->get(route('certificate-requests.edit', $item))->assertOk();
        $this->actingAs($this->center)->get(route('certificate-requests.edit', $item))->assertRedirect();
        $item->forceFill(['request_source' => null])->save();
        $this->actingAs($peer)->get(route('certificate-requests.show', $item))->assertForbidden();
    }

    public function test_additive_migration_is_repeatable_and_does_not_backfill_existing_records(): void
    {
        $item = $this->createRequest();
        $before = $item->getRawOriginal();
        $migration = require database_path('migrations/2026_11_11_000001_add_sales_unit_request_workflow.php');
        $migration->up();
        $this->assertSame($before, $item->fresh()->getRawOriginal());
        $this->assertSame($this->seller->sales_unit_id, $this->seller->fresh()->sales_unit_id);
    }

    public function test_sales_accounts_inherit_center_device_approval_without_changing_existing_settings(): void
    {
        $this->withMiddleware(EnsureLoginDeviceIsApproved::class);
        $this->actingAs($this->seller)->get(route('certificate-requests.create'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_external_accounts_without_email_can_save_drafts_but_cannot_create_or_update_and_submit(): void
    {
        $this->customer->update(['email' => 'customer@example.com']);
        foreach ([$this->seller, $this->center] as $actor) {
            $actor->update(['email' => null]);
            $this->actingAs($actor)->post(route('certificate-requests.store'), array_merge($this->payload(), ['new_email' => 'not-account@example.com']))
                ->assertSessionHasErrors('account_email');
            $before = CertificateRequest::count();
            $this->postJson(route('certificate-requests.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('account_email');
            $this->assertSame($before, CertificateRequest::count());
            $this->post(route('certificate-requests.store'), array_merge($this->payload(), ['request_action' => 'draft']))
                ->assertRedirect()->assertSessionMissing('error');
            $item = CertificateRequest::latest('id')->firstOrFail();
            $this->assertSame('DRAFT', $item->status);
            $this->put(route('certificate-requests.update', $item), array_merge($this->payload(), ['quantity' => [999]]))->assertSessionHasErrors('account_email');
            $this->assertEquals(12, $item->details()->firstOrFail()->quantity);
            $this->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => 1])->assertSessionHasErrors('account_email');
            $this->assertSame('DRAFT', $item->fresh()->status);
            $this->assertNull($item->fresh()->submitted_at);
            $this->put(route('certificate-requests.update', $item), array_merge($this->payload(), ['request_action' => 'draft', 'quantity' => [13]]))
                ->assertRedirect()->assertSessionMissing('error');
            $this->assertEquals(13, $item->details()->firstOrFail()->quantity);
            $actor->update(['email' => 'sender@example.com']);
            $this->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => 1])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($actor->isSalesUnit() ? 'WAIT_CENTER' : 'WAIT_DVKH', $item->fresh()->status);
        }
    }

    public function test_center_missing_email_cannot_forward_sales_request_but_can_return_it(): void
    {
        $item = $this->createRequest();
        $this->center->update(['email' => '  ']);
        $this->actingAs($this->center)->post(route('certificate-requests.submit-draft', $item), ['customer_commitment_confirmed' => 1])
            ->assertSessionHasErrors('account_email');
        $this->put(route('certificate-requests.update', $item), $this->payload())->assertSessionHasErrors('account_email');
        $this->assertSame('WAIT_CENTER', $item->fresh()->status);
        $this->assertNull($item->fresh()->submitted_at);
        $this->assertNull($item->fresh()->center_reviewed_at);
        $this->post(route('certificate-requests.return-sales-unit', $item), ['reason' => 'Bo sung thong tin'])->assertRedirect();
        $this->assertSame('RETURNED_SALES_UNIT', $item->fresh()->status);
        $this->assertFalse(\App\Support\RequestSubmissionEmail::missing(User::where('username', 'ptn')->firstOrFail()->forceFill(['email' => null])));
    }

    public function test_missing_email_warning_and_profile_link_are_rendered_on_forms_and_detail(): void
    {
        $item = $this->createRequest('draft');
        $this->seller->update(['email' => '']);
        $this->actingAs($this->seller);
        $pages = [];
        foreach (['create' => route('certificate-requests.create'), 'edit' => route('certificate-requests.edit', $item), 'detail' => route('certificate-requests.show', $item)] as $name => $url) {
            $pages['account-email-'.$name] = $this->get($url)->assertOk()->assertSee('data-account-email-warning', false)->assertSee(route('profile.edit'), false);
        }
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) mkdir($directory, 0777, true);
            foreach ($pages as $name => $response) file_put_contents($directory.'/'.$name.'.html', $response->getContent());
        }
        $this->seller->update(['email' => 'sender@example.com']);
        $this->get(route('certificate-requests.create'))->assertOk()->assertDontSee('data-account-email-warning', false);
    }

    private function createRequest(string $action = 'submit'): CertificateRequest
    {
        $this->actingAs($this->seller)->post(route('certificate-requests.store'), array_merge($this->payload(), ['request_action' => $action]))
            ->assertRedirect()->assertSessionMissing('error');

        return CertificateRequest::latest('id')->firstOrFail();
    }

    private function payload(): array
    {
        return ['customer_mode' => 'existing', 'customer_id' => $this->customer->id, 'sales_unit_id' => $this->seller->sales_unit_id,
            'delivery_date' => '2026-11-10', 'requester_name' => 'Nguoi yeu cau', 'customer_commitment_confirmed' => 1,
            'product_id' => [$this->product->id], 'quantity' => [12], 'request_action' => 'submit'];
    }
}
