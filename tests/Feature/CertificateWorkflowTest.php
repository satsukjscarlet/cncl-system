<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityCertificate;
use App\Models\QualityStandard;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\WorkQueueService;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
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
            ->getJson(route('work-queue.feed'))
            ->assertOk()
            ->assertJson([
                'label' => 1,
                'label_color' => 'danger',
            ])
            ->assertJsonPath('dropdown', fn ($html) => str_contains($html, 'Yêu cầu chờ kiểm tra'));

        $this->assertSame(1, UserNotification::where('user_id', $dvkh->id)
            ->where('type', 'request_created')
            ->where('url', route('dvkh.requests.show', $certificateRequest))
            ->count());

        $this->actingAs($dvkh)
            ->post(route('dvkh.requests.approve', $certificateRequest))
            ->assertRedirect(route('dvkh.requests.index'));

        $this->assertSame('WAIT_PTN', $certificateRequest->fresh()->status);
        $this->assertSame(1, UserNotification::where('user_id', $ptn->id)
            ->where('type', 'request_approved')
            ->count());
        $this->assertSame(1, UserNotification::where('user_id', $centerUser->id)
            ->where('type', 'request_approved_for_center')
            ->count());

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
        $this->assertSame(
            'Chờ Trưởng PTN ký',
            $certificateRequest->fresh('qualityCertificate')->displayStatusMeta()['text']
        );
        $this->assertSame(1, UserNotification::where('user_id', $truongPtn->id)
            ->where('type', 'certificate_created')
            ->where('url', route('quality-certificates.show', $certificate))
            ->count());

        $this->actingAs($centerUser)
            ->get(route('certificate-requests.index', ['status' => 'SIGN_READY']))
            ->assertOk()
            ->assertSee($certificateRequest->request_no);

        $this->actingAs($truongPtn)
            ->get(route('quality-certificates.index', ['status' => 'SIGN_READY']))
            ->assertOk()
            ->assertSee($certificate->certificate_no);

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
        $this->assertSame(1, UserNotification::where('user_id', $dvkh->id)
            ->where('type', 'certificate_returned')
            ->where('url', route('quality-certificates.show', $certificate))
            ->count());
    }

    public function test_notification_open_rebuilds_current_url_and_marks_as_read(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-NOTIFY-OPEN');
        $certificate = $this->createIssuedCertificate($centerUser, $customer, 'INV-NOTIFY-OPEN', [[$this->product, 4]]);

        $notification = UserNotification::create([
            'user_id' => $centerUser->id,
            'type' => 'certificate_signed',
            'title' => 'Phiếu CNCL đã ký',
            'message' => 'Test mở thông báo',
            'url' => 'http://192.168.0.227:8000/quality-certificates/' . $certificate->id,
            'data' => [
                'certificate_id' => $certificate->id,
                'certificate_no' => $certificate->certificate_no,
                'request_id' => $certificate->certificate_request_id,
                'distribution_center_id' => $centerUser->distribution_center_id,
            ],
        ]);

        $this->actingAs($centerUser)
            ->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonPath('browser_notification.id', $notification->id)
            ->assertJsonPath('browser_notification.title', 'Phiếu CNCL đã ký')
            ->assertJsonPath('browser_notification.url', route('notifications.open', $notification));

        $this->actingAs($centerUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('quality-certificates.show', $certificate));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_open_falls_back_to_request_when_user_cannot_view_certificate(): void
    {
        Role::findByName('TrungTam')->revokePermissionTo('certificate.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-NOTIFY-FALLBACK');
        $certificate = $this->createIssuedCertificate($centerUser, $customer, 'INV-NOTIFY-FALLBACK', [[$this->product, 4]]);

        $notification = UserNotification::create([
            'user_id' => $centerUser->id,
            'type' => 'certificate_signed',
            'title' => 'Phiếu CNCL đã ký',
            'message' => 'Test fallback thông báo',
            'url' => 'http://192.168.0.227:8000/quality-certificates/' . $certificate->id,
            'data' => [
                'certificate_id' => $certificate->id,
                'certificate_no' => $certificate->certificate_no,
                'request_id' => $certificate->certificate_request_id,
                'distribution_center_id' => $centerUser->distribution_center_id,
            ],
        ]);

        $this->actingAs($centerUser)
            ->get(route('notifications.open', $notification))
            ->assertRedirect(route('certificate-requests.show', $certificate->request));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_work_queue_items_use_matching_filter_urls(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-WORK-QUEUE');

        $waitPtn = CertificateRequest::create([
            'request_no' => 'YC-WORK-WAIT-PTN',
            'request_type' => 'NORMAL',
            'distribution_center_id' => $centerUser->distribution_center_id,
            'customer_id' => $customer->id,
            'delivery_date' => '2026-08-08',
            'invoice_no' => 'INV-WORK-WAIT-PTN',
            'require_hard_copy' => false,
            'hard_copy_quantity' => 0,
            'is_urgent' => false,
            'requester_name' => 'Nguoi tao',
            'status' => 'WAIT_PTN',
            'created_by' => $centerUser->id,
        ]);

        CertificateRequest::create([
            'request_no' => 'YC-WORK-PTN-PROCESSING',
            'request_type' => 'NORMAL',
            'distribution_center_id' => $centerUser->distribution_center_id,
            'customer_id' => $customer->id,
            'delivery_date' => '2026-08-08',
            'invoice_no' => 'INV-WORK-PTN-PROCESSING',
            'require_hard_copy' => false,
            'hard_copy_quantity' => 0,
            'is_urgent' => false,
            'requester_name' => 'Nguoi tao',
            'status' => 'PTN_PROCESSING',
            'created_by' => $centerUser->id,
        ]);

        $centerItems = collect(app(WorkQueueService::class)->forUser($centerUser)['items']);

        $this->assertSame(1, $centerItems->firstWhere('label', 'Đang chờ PTN lập phiếu')['count']);
        $this->assertSame(route('certificate-requests.index', ['status' => 'WAIT_PTN']), $centerItems->firstWhere('label', 'Đang chờ PTN lập phiếu')['url']);
        $this->assertSame(1, $centerItems->firstWhere('label', 'Phiếu đã lập - chờ ký')['count']);
        $this->assertSame(route('certificate-requests.index', ['status' => 'PTN_PROCESSING']), $centerItems->firstWhere('label', 'Phiếu đã lập - chờ ký')['url']);

        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $waitPtn->update([
            'status' => 'WAIT_DVKH',
            'is_urgent' => true,
        ]);

        $dvkhItems = collect(app(WorkQueueService::class)->forUser($dvkh)['items']);

        $this->assertSame(1, $dvkhItems->firstWhere('label', 'Yêu cầu gấp cần kiểm tra')['count']);
        $this->assertSame(route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'urgent' => '1']), $dvkhItems->firstWhere('label', 'Yêu cầu gấp cần kiểm tra')['url']);
    }

    public function test_center_can_save_draft_then_submit_to_dvkh(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-DRAFT-SUBMIT');

        $this->actingAs($centerUser)
            ->post(route('certificate-requests.store'), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'delivery_date' => '2026-08-08',
                'invoice_no' => 'INV-DRAFT-SUBMIT',
                'require_hard_copy' => '0',
                'hard_copy_quantity' => 0,
                'is_urgent' => '0',
                'requester_name' => 'Nguoi tao nhap',
                'note' => 'Luu nhap truoc',
                'product_id' => [$this->product->id],
                'quantity' => [12],
                'request_action' => 'draft',
            ])
            ->assertRedirect(route('certificate-requests.index'));

        $certificateRequest = CertificateRequest::where('invoice_no', 'INV-DRAFT-SUBMIT')->firstOrFail();

        $this->assertSame('DRAFT', $certificateRequest->status);
        $this->assertNull($certificateRequest->submitted_at);
        $this->assertSame(0, UserNotification::where('user_id', $dvkh->id)->where('type', 'request_created')->count());

        $this->actingAs($centerUser)
            ->put(route('certificate-requests.update', $certificateRequest), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'delivery_date' => '2026-08-09',
                'invoice_no' => 'INV-DRAFT-SUBMIT',
                'require_hard_copy' => '0',
                'hard_copy_quantity' => 0,
                'is_urgent' => '0',
                'requester_name' => 'Nguoi gui DVKH',
                'note' => 'Gui DVKH sau khi kiem tra',
                'product_id' => [$this->product->id],
                'quantity' => [15],
                'request_action' => 'submit',
            ])
            ->assertRedirect(route('certificate-requests.index'));

        $certificateRequest->refresh();

        $this->assertSame('WAIT_DVKH', $certificateRequest->status);
        $this->assertNotNull($certificateRequest->submitted_at);
        $this->assertSame($centerUser->id, $certificateRequest->submitted_by);
        $this->assertSame(1, UserNotification::where('user_id', $dvkh->id)->where('type', 'request_created')->count());
    }

    public function test_request_can_create_new_customer_with_manual_customer_code(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();

        $this->actingAs($centerUser)
            ->post(route('certificate-requests.store'), [
                'customer_mode' => 'new',
                'new_customer_code' => 'KH-MANUAL-001',
                'new_customer_name' => 'Khach hang nhap moi',
                'new_customer_address' => 'Dia chi khach hang nhap moi',
                'new_project_name' => 'Cong trinh nhap moi',
                'new_project_address' => 'Dia diem cong trinh nhap moi',
                'delivery_date' => '2026-08-08',
                'invoice_no' => 'INV-NEW-CUSTOMER-CODE',
                'require_hard_copy' => '0',
                'hard_copy_quantity' => 0,
                'is_urgent' => '0',
                'requester_name' => 'Nguoi tao NP',
                'note' => 'Tao khach hang moi co ma khach hang',
                'product_id' => [$this->product->id],
                'quantity' => [12],
            ])
            ->assertRedirect(route('certificate-requests.index'));

        $customer = Customer::where('customer_code', 'KH-MANUAL-001')->firstOrFail();
        $this->assertSame('Khach hang nhap moi', $customer->customer_name);
        $this->assertSame($centerUser->distribution_center_id, $customer->distribution_center_id);

        $certificateRequest = CertificateRequest::where('invoice_no', 'INV-NEW-CUSTOMER-CODE')->firstOrFail();
        $this->assertSame($customer->id, $certificateRequest->customer_id);
    }

    public function test_customer_options_are_scoped_by_distribution_center(): void
    {
        $npUser = User::where('username', 'trungtam_np')->firstOrFail();
        $tpUser = User::where('username', 'trungtam_tp')->firstOrFail();
        $admin = User::where('username', 'admin')->firstOrFail();

        $npCustomer = $this->createCustomerForCenter($npUser, 'KH-AJAX-NP');
        $tpCustomer = $this->createCustomerForCenter($tpUser, 'KH-AJAX-TP');

        $this->actingAs($npUser)
            ->getJson(route('certificate-requests.customer-options', ['q' => 'KH-AJAX']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $npCustomer->id)
            ->assertJsonMissing(['id' => $tpCustomer->id]);

        $this->actingAs($admin)
            ->getJson(route('certificate-requests.customer-options', [
                'q' => 'KH-AJAX',
                'distribution_center_id' => $tpUser->distribution_center_id,
            ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $tpCustomer->id)
            ->assertJsonMissing(['id' => $npCustomer->id]);
    }

    public function test_customer_import_warns_before_updating_duplicate_code_in_same_center(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $existing = $this->createCustomerForCenter($centerUser, 'KH-IMPORT-DUP');
        $fileName = 'tests/customers-import-duplicate.xlsx';
        $preview = null;

        Excel::store(new class implements FromArray, WithHeadings {
            public function headings(): array
            {
                return [
                    'ma_khach_hang',
                    'ten_khach_hang',
                    'dia_chi_khach_hang',
                    'email',
                    'ten_cong_trinh',
                    'dia_diem_cong_trinh',
                    'dang_su_dung',
                ];
            }

            public function array(): array
            {
                return [
                    ['KH-IMPORT-DUP', 'Ten khach hang cap nhat', 'Dia chi moi', 'updated@example.com', 'Cong trinh moi', 'Dia diem moi', '1'],
                    ['KH-IMPORT-NEW', 'Ten khach hang moi', 'Dia chi khach hang moi', 'new@example.com', 'Cong trinh moi 2', 'Dia diem moi 2', '1'],
                ];
            }
        }, $fileName);

        try {
            $uploadedFile = new UploadedFile(
                Storage::path($fileName),
                'customers-import-duplicate.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $response = $this->actingAs($centerUser)
                ->post(route('customers.import'), [
                    'file' => $uploadedFile,
                ]);

            $response
                ->assertRedirect(route('customers.index'))
                ->assertSessionHas('customer_import_preview');

            $this->assertSame('Acceptance Khach hang NP', $existing->fresh()->customer_name);
            $preview = session('customer_import_preview');

            $this->actingAs($centerUser)
                ->post(route('customers.import'), [
                    'temp_path' => $preview['temp_path'],
                    'confirm_update' => 1,
                ])
                ->assertRedirect(route('customers.index'))
                ->assertSessionHas('success');

            $this->assertSame('Ten khach hang cap nhat', $existing->fresh()->customer_name);
            $this->assertDatabaseHas('customers', [
                'distribution_center_id' => $centerUser->distribution_center_id,
                'customer_code' => 'KH-IMPORT-NEW',
                'customer_name' => 'Ten khach hang moi',
            ]);
        } finally {
            Storage::delete($fileName);
            if (!empty($preview['temp_path'] ?? null)) {
                Storage::delete($preview['temp_path']);
            }
        }
    }

    public function test_request_product_excel_import_maps_product_codes_and_merges_quantities(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $secondProduct = $this->createProductVariant('PVC-DN90', 'Ong PVC-U DN90', 'DN90');
        $fileName = 'tests/request-products-import.xlsx';

        Excel::store(new class($this->product, $secondProduct) implements FromArray, WithHeadings {
            public function __construct(private Product $firstProduct, private Product $secondProduct)
            {
            }

            public function headings(): array
            {
                return ['ma_san_pham', 'so_luong'];
            }

            public function array(): array
            {
                return [
                    [$this->firstProduct->product_code, 10],
                    [$this->firstProduct->product_code, 7],
                    [$this->secondProduct->product_code, 5],
                ];
            }
        }, $fileName);

        try {
            $uploadedFile = new UploadedFile(
                Storage::path($fileName),
                'request-products-import.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $response = $this->actingAs($centerUser)
                ->postJson(route('certificate-requests.import-products'), [
                    'file' => $uploadedFile,
                ]);

            $response
                ->assertOk()
                ->assertJsonPath('count', 2);

            $items = collect($response->json('items'));
            $this->assertEquals(17, (float) $items->firstWhere('product_id', $this->product->id)['quantity']);
            $this->assertEquals(5, (float) $items->firstWhere('product_id', $secondProduct->id)['quantity']);
        } finally {
            Storage::delete($fileName);
        }
    }

    public function test_request_product_paste_maps_product_codes_and_reports_clear_errors(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $secondProduct = $this->createProductVariant('PVC-DN75', 'Ong PVC-U DN75', 'DN75');

        $this->actingAs($centerUser)
            ->postJson(route('certificate-requests.paste-products'), [
                'products_text' => "ma_san_pham\tso_luong\n{$this->product->product_code}\t10\n{$this->product->product_code}\t7\n{$secondProduct->product_code}\t5",
            ])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('items.0.quantity', 17);

        $this->actingAs($centerUser)
            ->postJson(route('certificate-requests.paste-products'), [
                'products_text' => "ma_san_pham\tso_luong\nUNKNOWN-CODE\t3\n{$this->product->product_code}\t0\n\t5",
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['Dòng 2: Không tìm thấy mã sản phẩm "UNKNOWN-CODE".'])
            ->assertJsonFragment(['Dòng 3: Số lượng phải là số lớn hơn 0.'])
            ->assertJsonFragment(['Dòng 4: Chưa nhập mã sản phẩm.']);
    }

    public function test_single_reissue_request_can_be_edited_before_dvkh_revokes_old_certificate(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $ptn = User::where('username', 'ptn')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-E2E-REISSUE');
        $oldCertificate = $this->createIssuedCertificate(
            $centerUser,
            $customer,
            'INV-E2E-REISSUE-OLD',
            [[$this->product, 10]]
        );

        $this->actingAs($centerUser)
            ->post(route('quality-certificates.request-reissue', $oldCertificate), [
                'reissue_reason' => 'Sai so luong san pham tren phieu cu.',
            ])
            ->assertRedirect();

        $reissueRequest = CertificateRequest::with(['details', 'reissueCertificates'])
            ->where('request_type', 'REISSUE')
            ->where('reissue_of_certificate_id', $oldCertificate->id)
            ->firstOrFail();

        $this->assertSame('DRAFT', $reissueRequest->status);
        $this->assertSame($oldCertificate->id, $reissueRequest->reissue_of_certificate_id);
        $this->assertTrue($reissueRequest->reissueCertificates->contains($oldCertificate));
        $this->assertSame('ISSUED', $oldCertificate->fresh()->status);

        $this->actingAs($centerUser)
            ->put(route('certificate-requests.update', $reissueRequest), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'delivery_date' => '2026-08-10',
                'invoice_no' => 'INV-E2E-REISSUE-NEW',
                'require_hard_copy' => '0',
                'hard_copy_quantity' => 0,
                'is_urgent' => '0',
                'requester_name' => 'Nguoi sua cap lai',
                'note' => 'Da sua du lieu truoc khi DVKH xac nhan.',
                'product_id' => [$this->product->id],
                'quantity' => [15],
                'request_action' => 'submit',
            ])
            ->assertRedirect(route('certificate-requests.index'));

        $reissueRequest->refresh()->load('details');
        $this->assertSame('WAIT_DVKH', $reissueRequest->status);
        $this->assertSame('INV-E2E-REISSUE-NEW', $reissueRequest->invoice_no);
        $this->assertEquals(15, (float) $reissueRequest->details->first()->quantity);
        $this->assertSame('ISSUED', $oldCertificate->fresh()->status);

        $this->actingAs($dvkh)
            ->post(route('dvkh.requests.approve', $reissueRequest))
            ->assertRedirect(route('dvkh.requests.index'));

        $this->assertSame('WAIT_PTN', $reissueRequest->fresh()->status);
        $this->assertSame('REVOKED', $oldCertificate->fresh()->status);
        $this->assertNotNull($oldCertificate->fresh()->revoked_at);

        $this->actingAs($ptn)
            ->post(route('ptn.requests.receive-and-create-certificate', $reissueRequest))
            ->assertRedirect();

        $newCertificate = QualityCertificate::where('certificate_request_id', $reissueRequest->id)->firstOrFail();
        $this->assertSame($oldCertificate->id, $newCertificate->replaces_certificate_id);
        $this->assertSame($newCertificate->id, $oldCertificate->fresh()->replaced_by_certificate_id);
    }

    public function test_bulk_reissue_merges_old_certificates_and_revokes_all_when_dvkh_approves(): void
    {
        $centerUser = User::where('username', 'trungtam_np')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $ptn = User::where('username', 'ptn')->firstOrFail();
        $customer = $this->createCustomerForCenter($centerUser, 'KH-E2E-BULK-REISSUE');
        $secondProduct = $this->createProductVariant('PVC-DN90', 'Ong PVC-U DN90', 'DN90');

        $firstOldCertificate = $this->createIssuedCertificate(
            $centerUser,
            $customer,
            'INV-E2E-BULK-001',
            [
                [$this->product, 10],
                [$secondProduct, 5],
            ]
        );
        $secondOldCertificate = $this->createIssuedCertificate(
            $centerUser,
            $customer,
            'INV-E2E-BULK-002',
            [
                [$this->product, 7],
            ]
        );

        $this->actingAs($centerUser)
            ->post(route('quality-certificates.bulk-request-reissue'), [
                'certificate_ids' => [$firstOldCertificate->id, $secondOldCertificate->id],
                'reissue_reason' => 'Gom hai phieu cu thanh mot phieu moi.',
            ])
            ->assertRedirect();

        $reissueRequest = CertificateRequest::with(['details', 'reissueCertificates'])
            ->where('request_type', 'REISSUE')
            ->latest('id')
            ->firstOrFail();

        $this->assertCount(2, $reissueRequest->reissueCertificates);
        $this->assertTrue($reissueRequest->reissueCertificates->contains($firstOldCertificate));
        $this->assertTrue($reissueRequest->reissueCertificates->contains($secondOldCertificate));
        $this->assertSame($firstOldCertificate->id, $reissueRequest->reissue_of_certificate_id);
        $this->assertSame('DRAFT', $reissueRequest->status);
        $this->assertSame($centerUser->distribution_center_id, $reissueRequest->distribution_center_id);

        $mergedQuantities = $reissueRequest->details->pluck('quantity', 'product_id');
        $this->assertEquals(17, (float) $mergedQuantities[$this->product->id]);
        $this->assertEquals(5, (float) $mergedQuantities[$secondProduct->id]);

        $this->actingAs($centerUser)
            ->put(route('certificate-requests.update', $reissueRequest), [
                'customer_mode' => 'existing',
                'customer_id' => $customer->id,
                'delivery_date' => optional($reissueRequest->delivery_date)->format('Y-m-d'),
                'invoice_no' => $reissueRequest->invoice_no,
                'require_hard_copy' => $reissueRequest->require_hard_copy ? '1' : '0',
                'hard_copy_quantity' => $reissueRequest->hard_copy_quantity,
                'is_urgent' => $reissueRequest->is_urgent ? '1' : '0',
                'urgent_reason_id' => $reissueRequest->urgent_reason_id,
                'requester_name' => $reissueRequest->requester_name,
                'note' => $reissueRequest->note,
                'product_id' => $reissueRequest->details->pluck('product_id')->all(),
                'quantity' => $reissueRequest->details->pluck('quantity')->all(),
                'request_action' => 'submit',
            ])
            ->assertRedirect(route('certificate-requests.index'));

        $this->assertSame('WAIT_DVKH', $reissueRequest->fresh()->status);

        $this->actingAs($dvkh)
            ->post(route('dvkh.requests.approve', $reissueRequest))
            ->assertRedirect(route('dvkh.requests.index'));

        $this->assertSame('REVOKED', $firstOldCertificate->fresh()->status);
        $this->assertSame('REVOKED', $secondOldCertificate->fresh()->status);
        $this->assertSame('WAIT_PTN', $reissueRequest->fresh()->status);

        $this->actingAs($ptn)
            ->post(route('ptn.requests.receive-and-create-certificate', $reissueRequest))
            ->assertRedirect();

        $newCertificate = QualityCertificate::where('certificate_request_id', $reissueRequest->id)->firstOrFail();
        $this->assertSame($firstOldCertificate->id, $newCertificate->replaces_certificate_id);
        $this->assertSame($newCertificate->id, $firstOldCertificate->fresh()->replaced_by_certificate_id);
        $this->assertSame($newCertificate->id, $secondOldCertificate->fresh()->replaced_by_certificate_id);
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

    private function createProductVariant(string $code, string $name, string $nominalSize): Product
    {
        return Product::create([
            'product_group_id' => ProductGroup::firstOrFail()->id,
            'quality_standard_id' => QualityStandard::firstOrFail()->id,
            'product_code' => $code,
            'product_name' => $name,
            'unit' => 'm',
            'nominal_size' => $nominalSize,
            'technical_requirements' => 'PVC-U; kich thuoc danh nghia ' . $nominalSize . '.',
            'certificate_type' => 'CNCL',
            'certificate_template' => 'default',
            'is_active' => true,
        ]);
    }

    private function createIssuedCertificate(User $centerUser, Customer $customer, string $invoiceNo, array $productRows): QualityCertificate
    {
        $request = CertificateRequest::create([
            'request_no' => 'YC-TEST-' . str_pad((string) (CertificateRequest::count() + 1), 4, '0', STR_PAD_LEFT),
            'request_type' => 'NORMAL',
            'distribution_center_id' => $centerUser->distribution_center_id,
            'customer_id' => $customer->id,
            'delivery_date' => '2026-08-08',
            'invoice_no' => $invoiceNo,
            'require_hard_copy' => false,
            'hard_copy_quantity' => 0,
            'is_urgent' => false,
            'requester_name' => 'Nguoi tao phieu cu',
            'note' => 'Phieu cu da ky so.',
            'status' => 'COMPLETED',
            'created_by' => $centerUser->id,
        ]);

        foreach ($productRows as [$product, $quantity]) {
            $request->details()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        $certificate = QualityCertificate::create([
            'certificate_no' => 'CNCL-TEST-' . str_pad((string) (QualityCertificate::count() + 1), 4, '0', STR_PAD_LEFT),
            'certificate_request_id' => $request->id,
            'status' => 'ISSUED',
            'created_by' => $centerUser->id,
            'signed_at' => now(),
            'signed_by' => 'Truong PTN',
            'pdf_path' => 'quality-certificates/test-' . $request->id . '.pdf',
            'print_count' => 0,
        ]);

        foreach ($productRows as [$product, $quantity]) {
            $certificate->details()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'nominal_size' => $product->nominal_size,
                'technical_requirements' => $product->technical_requirements,
                'quality_standard' => $product->qualityStandard?->code,
            ]);
        }

        return $certificate;
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
