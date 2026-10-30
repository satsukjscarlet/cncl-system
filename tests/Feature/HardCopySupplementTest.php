<?php

namespace Tests\Feature;

use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\HardCopySupplement;
use App\Models\QualityCertificate;
use App\Models\PrintLog;
use App\Models\SalesUnit;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\HardCopyCertificatePdfService;
use App\Services\HardCopySupplementService;
use App\Services\WorkQueueService;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardCopySupplementTest extends TestCase
{
    use RefreshDatabase;
    private User $center;
    private User $dvkh;
    private User $ptn;
    private QualityCertificate $certificate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\EnsureLoginDeviceIsApproved::class);
        $this->seed([PermissionSeeder::class, DistributionCenterSeeder::class, UserSeeder::class]);
        $this->center = User::where('username', 'trungtam_np')->firstOrFail();
        $this->dvkh = User::where('username', 'dvkh')->firstOrFail();
        $this->ptn = User::where('username', 'ptn')->firstOrFail();
        $this->certificate = $this->certificate($this->center, 10);
    }

    private function certificate(User $user, int $copies = 0): QualityCertificate
    {
        $n = QualityCertificate::count() + 1;
        $customer = Customer::create(['distribution_center_id' => $user->distribution_center_id,
            'customer_code' => 'C-' . $n, 'customer_name' => 'Khách hàng bổ sung ' . $n,
            'project_name' => 'Công trình bổ sung', 'project_address' => 'Hải Phòng', 'is_active' => true]);
        $sales = SalesUnit::create(['distribution_center_id' => $user->distribution_center_id, 'code' => 'S-' . $n,
            'name' => 'Đơn vị bán hàng ' . $n, 'is_active' => true]);
        $request = CertificateRequest::create(['request_no' => 'YC-SUP-' . $n, 'distribution_center_id' => $user->distribution_center_id,
            'customer_id' => $customer->id, 'sales_unit_id' => $sales->id, 'delivery_date' => '2026-09-01',
            'requester_name' => 'Người yêu cầu test', 'status' => 'COMPLETED', 'created_by' => $user->id,
            'require_hard_copy' => $copies > 0, 'hard_copy_quantity' => $copies]);
        return QualityCertificate::create(['certificate_no' => 'CNCL-SUP-' . $n, 'certificate_request_id' => $request->id,
            'status' => 'ISSUED', 'created_by' => $user->id, 'signed_at' => '2026-09-02', 'pdf_path' => 'private-original-' . $n . '.pdf',
            'pades_status' => 'SIGNED_PDF', 'hard_copy_single_page_count' => 4, 'hard_copy_batch_page_count' => 2, 'print_count' => 0]);
    }

    private function create(?QualityCertificate $certificate = null, int $quantity = 5): HardCopySupplement
    {
        $certificate ??= $this->certificate;
        $this->actingAs($this->center)->post(route('hard-copy-supplements.store', $certificate), [
            'quantity' => $quantity, 'reason' => 'Xin thêm bản giấy', 'note' => 'Nhận tại Công ty',
        ])->assertSessionHasNoErrors()->assertRedirect();
        return $certificate->hardCopySupplements()->latest('id')->firstOrFail();
    }

    private function action(User $user, HardCopySupplement $item, string $action, array $data = [])
    {
        return $this->actingAs($user)->post(route('hard-copy-supplements.action', [$item, $action]), $data);
    }

    private function mockPrint(): void
    {
        $this->mock(HardCopyCertificatePdfService::class, function ($mock) {
            $mock->shouldReceive('render')->andReturn('%PDF-1.7 test content');
            $mock->shouldReceive('pageCount')->andReturn(4);
        });
        $this->mock(\App\Services\HardCopyBatchCertificatePdfService::class, function ($mock) {
            $mock->shouldReceive('render')->andReturn('%PDF-1.7 batch test content');
            $mock->shouldReceive('pageCount')->andReturn(2);
        });
    }

    public function test_complete_flow_preserves_original_and_print_does_not_auto_complete(): void
    {
        $original = $this->certificate->only(['certificate_no', 'pdf_path', 'signed_at', 'status']);
        $item = $this->create();
        $this->assertSame('WAIT_DVKH', $item->status);
        $this->assertTrue(collect(app(WorkQueueService::class)->forUser($this->dvkh)['items'])->contains(fn ($i) => str_contains($i['label'], 'Bổ sung ký tươi')));
        $notice = UserNotification::where('user_id', $this->dvkh->id)->where('type', 'hard_copy_supplement')->firstOrFail();
        $this->actingAs($this->dvkh)->get(route('notifications.open', $notice))->assertRedirect(route('hard-copy-supplements.show', $item));
        $this->action($this->dvkh, $item, 'approve')->assertSessionHasNoErrors();
        $this->assertSame('WAIT_PTN', $item->fresh()->status);
        $this->action($this->ptn, $item, 'complete')->assertSessionHasErrors('supplement');
        $this->mockPrint();
        $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('WAIT_PTN', $item->fresh()->status);
        $this->assertSame(4, $item->fresh()->page_count);
        $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'batch'])->assertOk();
        $this->assertSame('batch', $item->fresh()->print_template);
        $this->assertSame(2, $item->fresh()->page_count);
        $this->assertDatabaseHas('print_logs', ['hard_copy_supplement_id' => $item->id, 'print_template' => 'batch']);
        $this->assertSame([['single', 4], ['batch', 2]], $item->events()->where('action', 'printed')->get()
            ->map(fn ($event) => [$event->data['print_template'], $event->data['page_count']])->all());
        $this->get(route('hard-copy-supplements.show', $item))->assertOk()
            ->assertSee('value="single"', false)->assertSee('value="batch"', false);
        $this->actingAs(User::where('username', 'admin')->firstOrFail())->get(route('reports.hard-copy-supplements'))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['approved'] === 5 && $summary['single_sheets'] === 0 && $summary['batch_sheets'] === 10);
        $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertOk();
        $this->assertSame('single', $item->fresh()->print_template);
        $this->assertSame(4, $item->fresh()->page_count);
        $this->action($this->dvkh, $item, 'return', ['comment' => 'Muốn đổi'])->assertSessionHasErrors('supplement');
        $this->action($this->dvkh, $item, 'cancel', ['comment' => 'Muốn hủy'])->assertSessionHasErrors('supplement');
        $this->assertDatabaseHas('print_logs', ['hard_copy_supplement_id' => $item->id, 'print_template' => 'single']);
        $this->action($this->ptn, $item, 'complete', ['completion_note' => 'Đã ký đủ bản'])->assertSessionHasNoErrors();
        $this->assertSame('COMPLETED', $item->fresh()->status);
        $this->assertNull($item->fresh()->active_certificate_id);
        $this->assertSame(6, $item->events()->count());
        $this->assertEquals($original, $this->certificate->fresh()->only(array_keys($original)));
        $this->assertSame(10, $this->certificate->request->fresh()->hard_copy_quantity);
        $this->assertSame(1, QualityCertificate::count());
        $this->action($this->ptn, $item, 'complete')->assertSessionHasErrors('supplement');
        $this->assertNotSame($item->id, $this->create(quantity: 3)->id);
    }

    public function test_switching_templates_does_not_bypass_each_templates_page_snapshot(): void
    {
        $item = $this->create();
        $this->action($this->dvkh, $item, 'approve')->assertSessionHasNoErrors();
        $this->mockPrint();
        $this->mock(HardCopyCertificatePdfService::class, function ($mock) {
            $mock->shouldReceive('render')->andReturn('%PDF-1.7 single');
            $mock->shouldReceive('pageCount')->andReturn(4, 5);
        });
        $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertOk();
        $this->post(route('hard-copy-supplements.print', $item), ['print_template' => 'batch'])->assertOk();
        $this->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertSessionHasErrors('supplement');
        $this->assertSame('batch', $item->fresh()->print_template);
        $this->assertSame(2, $item->fresh()->page_count);
        $this->assertSame(2, $item->printLogs()->count());
        $this->assertSame(2, $item->events()->where('action', 'printed')->count());
    }

    public function test_main_report_includes_only_completed_supplements_and_separates_initial_prints(): void
    {
        $first = $this->create(quantity: 5);
        $first->update(['status' => 'COMPLETED', 'completed_at' => now(), 'active_certificate_id' => null]);
        $second = $this->create(quantity: 3);
        $second->update(['status' => 'COMPLETED', 'completed_at' => now(), 'active_certificate_id' => null]);
        $pending = $this->create(quantity: 20);
        $pending->update(['status' => 'WAIT_PTN']);
        $onlySupplement = $this->certificate($this->center, 0);
        $onlySupplement->update(['hard_copy_single_page_count' => 5, 'hard_copy_batch_page_count' => 3]);
        $extra = $this->create($onlySupplement, 4);
        $extra->update(['status' => 'COMPLETED', 'completed_at' => now(), 'active_certificate_id' => null]);
        $cancelled = $this->create($onlySupplement, 13);
        $cancelled->update(['status' => 'CANCELLED', 'active_certificate_id' => null]);
        $returned = $this->create($onlySupplement, 7);
        $returned->update(['status' => 'RETURNED']);
        $notFinished = $this->certificate($this->center, 0);
        $this->create($notFinished, 9);
        $revoked = $this->certificate($this->center, 10);
        $revokedExtra = $this->create($revoked, 7);
        $revokedExtra->update(['status' => 'COMPLETED', 'active_certificate_id' => null]);
        $revoked->update(['revoked_at' => now()]);
        PrintLog::create(['quality_certificate_id' => $this->certificate->id, 'user_id' => $this->ptn->id,
            'print_no' => 1, 'print_template' => 'single', 'print_mode' => 'normal', 'reason' => 'In ban đầu']);
        foreach ([[$this->certificate, $first, 'batch'], [$onlySupplement, $extra, 'single']] as [$certificate, $supplement, $template]) {
            PrintLog::create(['quality_certificate_id' => $certificate->id, 'hard_copy_supplement_id' => $supplement->id,
                'user_id' => $this->ptn->id, 'print_no' => 2, 'print_template' => $template, 'print_mode' => 'normal', 'reason' => 'In bổ sung']);
        }
        $admin = User::where('username', 'admin')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('reports.hard-copy', ['date_from' => '2026-09-01', 'date_to' => '2026-09-30']))->assertOk();
        $summary = $response->viewData('summary');
        $this->assertSame(2, $summary['certificate_count']);
        $this->assertSame(10, $summary['copy_count']);
        $this->assertSame(12, $summary['supplement_copy_count']);
        $this->assertSame(22, $summary['total_copy_count']);
        $this->assertSame(92, $summary['single_sheet_count']);
        $this->assertSame(48, $summary['batch_sheet_count']);
        $this->assertSame(['single_print_count' => 1, 'batch_print_count' => 0], $response->viewData('printSummary'));
        foreach (['centerStats', 'salesUnitStats', 'monthlyStats'] as $key) {
            $this->assertSame(12, collect($response->viewData($key))->sum('supplement_copy_count'));
            $this->assertSame(22, collect($response->viewData($key))->sum('total_copy_count'));
            $this->assertSame(92, collect($response->viewData($key))->sum('single_sheet_count'));
            $this->assertSame(48, collect($response->viewData($key))->sum('batch_sheet_count'));
        }
        $this->assertCount(1, $response->viewData('certificates')->getCollection()->firstWhere('id', $this->certificate->id)->printLogs);
        $this->get(route('reports.hard-copy', ['print_status' => 'printed_single']))->assertOk()->assertViewHas('certificates', fn ($p) => $p->total() === 1);
        $this->get(route('reports.hard-copy', ['print_template' => 'batch']))->assertOk()->assertViewHas('certificates', fn ($p) => $p->total() === 0);
        $this->get(route('reports.hard-copy', ['print_status' => 'not_printed']))->assertOk()->assertViewHas('certificates', fn ($p) => $p->total() === 1 && $p->first()->id === $onlySupplement->id);
        $this->get(route('reports.hard-copy', ['sales_unit_id' => $onlySupplement->request->sales_unit_id]))->assertOk()->assertViewHas('summary', fn ($s) => $s['copy_count'] === 0 && $s['total_copy_count'] === 4);
        $export = new \App\Exports\HardCopyReportExport($response->viewData('certificates')->getCollection(), $response->viewData('centerStats'),
            $response->viewData('salesUnitStats'), $response->viewData('monthlyStats'), $response->viewData('filterSummary'));
        foreach ($export->sheets() as $sheet) {
            foreach ($sheet->collection() as $row) $this->assertCount(count($sheet->headings()), $row);
        }
        $detail = $export->sheets()[4]->collection();
        $this->assertSame(10, $detail->sum('so_ban_ban_dau'));
        $this->assertSame(12, $detail->sum('bo_sung_da_hoan_tat'));
        $this->assertSame(9, $detail->sum('trang_in_don'));
        $this->assertSame(5, $detail->sum('trang_in_bo'));
        $response->assertSee('Định nghĩa số liệu')->assertSee('Tờ In đơn dự kiến')->assertSee('Trang In bộ')->assertSee('Ghi nhận bấm in ban đầu');
        $this->assertSame(22, $detail->sum('tong_so_ban'));
        $this->assertSame(92, $detail->sum('to_in_don_du_kien'));
        $this->assertSame(48, $detail->sum('to_in_bo_du_kien'));
        $this->get(route('reports.hard-copy.export'))->assertOk()->assertDownload();
        $this->get(route('hard-copy-supplements.index', ['quality_certificate_id' => $onlySupplement->id, 'status' => 'COMPLETED']))
            ->assertOk()->assertViewHas('items', fn ($p) => $p->total() === 1 && $p->first()->id === $extra->id);
        $other = User::where('username', 'trungtam_tp')->firstOrFail();
        $other->givePermissionTo('report.view');
        $this->actingAs($other)->get(route('reports.hard-copy', ['distribution_center_id' => $this->center->distribution_center_id]))
            ->assertOk()->assertViewHas('certificates', fn ($p) => $p->total() === 0);
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0770, true);
            file_put_contents($directory . '/hard-copy-completed-report.html', $response->getContent());
        }
    }

    public function test_return_edit_resubmit_and_cancellation_rules(): void
    {
        $item = $this->create();
        $this->action($this->dvkh, $item, 'return')->assertSessionHasErrors('comment');
        $this->action($this->dvkh, $item, 'return', ['comment' => 'Bổ sung lý do'])->assertSessionHasNoErrors();
        $this->assertSame('RETURNED', $item->fresh()->status);
        $this->action($this->center, $item, 'resubmit', ['quantity' => 8, 'reason' => 'Lý do mới'])->assertSessionHasNoErrors();
        $this->assertSame(8, $item->fresh()->quantity);
        $this->action($this->dvkh, $item, 'approve')->assertSessionHasNoErrors();
        $this->action($this->center, $item, 'resubmit', ['quantity' => 9, 'reason' => 'Sai bước'])->assertSessionHasErrors('supplement');
        $this->action($this->center, $item, 'cancel', ['comment' => 'Không nhận'])->assertForbidden();
        $this->action($this->dvkh, $item, 'return', ['comment' => 'Trao đổi lại'])->assertSessionHasNoErrors();
        $this->action($this->center, $item, 'cancel', ['comment' => 'Không cần thêm'])->assertSessionHasNoErrors();
        $this->assertSame('CANCELLED', $item->fresh()->status);
        $this->assertNull($item->fresh()->active_certificate_id);
    }

    public function test_scope_authorization_quantity_and_duplicate_guards(): void
    {
        foreach ([0, -1, '', 1.5] as $quantity) {
            $this->actingAs($this->center)->post(route('hard-copy-supplements.store', $this->certificate), ['quantity' => $quantity, 'reason' => 'Test'])->assertSessionHasErrors('quantity');
        }
        $item = $this->create();
        $this->actingAs($this->center)->post(route('hard-copy-supplements.store', $this->certificate), ['quantity' => 1, 'reason' => 'Trùng'])->assertSessionHasErrors('supplement');
        $this->assertSame(1, HardCopySupplement::count());
        $other = User::where('username', 'trungtam_tp')->firstOrFail();
        $this->actingAs($other)->get(route('hard-copy-supplements.show', $item))->assertForbidden();
        $this->actingAs($other)->get(route('hard-copy-supplements.index', ['distribution_center_id' => $this->center->distribution_center_id]))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 0);
        $this->actingAs($other)->post(route('hard-copy-supplements.store', $this->certificate), ['quantity' => 1, 'reason' => 'Khác trung tâm'])->assertForbidden();
        $this->action($this->center, $item, 'approve')->assertForbidden();
        $this->action($this->dvkh, $item, 'complete')->assertForbidden();
        $this->actingAs($this->center)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertForbidden();
    }

    public function test_revoked_and_reissue_pending_certificates_block_processing(): void
    {
        $item = $this->create();
        $this->certificate->update(['status' => 'REVOKED', 'revoked_at' => now()]);
        $this->action($this->dvkh, $item, 'approve')->assertSessionHasErrors('supplement');
        $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertSessionHasErrors('supplement');
        $this->action($this->dvkh, $item, 'cancel', ['comment' => 'Phiếu thu hồi'])->assertSessionHasNoErrors();
        $this->certificate->update(['status' => 'ISSUED', 'revoked_at' => null]);
        $request = $this->certificate->request->replicate();
        $request->fill(['request_no' => 'YC-REISSUE', 'request_type' => 'REISSUE', 'status' => 'DRAFT', 'reissue_of_certificate_id' => $this->certificate->id])->save();
        $this->actingAs($this->center)->post(route('hard-copy-supplements.store', $this->certificate), ['quantity' => 1, 'reason' => 'Test'])->assertSessionHasErrors('supplement');
    }

    public function test_report_counts_unique_original_and_approved_not_completed_twice_and_filters_dates(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 26));
        $a = $this->create();
        $this->action($this->dvkh, $a, 'approve')->assertSessionHasNoErrors();
        $a->update(['print_template' => 'single', 'page_count' => 4]);
        $this->action($this->ptn, $a, 'complete')->assertSessionHasNoErrors();
        $b = $this->create(quantity: 3);
        $this->action($this->dvkh, $b, 'approve')->assertSessionHasNoErrors();
        $second = $this->certificate($this->center);
        $c = $this->create($second, 2);
        $this->action($this->dvkh, $c, 'approve')->assertSessionHasNoErrors();
        $c->update(['print_template' => 'batch', 'page_count' => 2]);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
        $response = $this->get(route('reports.hard-copy-supplements', ['date_from' => '2026-10-01', 'date_to' => '2026-10-31']));
        $response->assertOk();
        $summary = $response->viewData('summary');
        $this->assertSame(3, $summary['requests']);
        $this->assertSame(2, $summary['certificates']);
        $this->assertEquals(10, $summary['original']);
        $this->assertEquals(10, $summary['approved']);
        $this->assertEquals(5, $summary['completed']);
        $this->assertEquals(20, $summary['total']);
        $this->assertEquals(20, $summary['single_sheets']);
        $this->assertEquals(4, $summary['batch_sheets']);
        $this->assertEquals(3, $summary['unassigned']);
        $this->get(route('reports.hard-copy-supplements', ['date_basis' => 'signed_at', 'date_from' => '2026-10-01']))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 0);
        $this->get(route('reports.hard-copy-supplements', ['date_basis' => 'completed_at', 'date_from' => '2026-10-01']))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 1);
        $this->get(route('reports.hard-copy-supplements', ['keyword' => $second->certificate_no]))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 1);
        $this->get(route('reports.hard-copy-supplements', ['sales_unit_id' => $second->request->sales_unit_id]))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 1);
        $this->get(route('reports.hard-copy-supplements.export'))->assertOk()->assertDownload('bao_cao_bo_sung_ky_tuoi.xlsx');
        $this->get(route('reports.hard-copy-supplements', ['date_from' => '2026-10-31', 'date_to' => '2026-10-01']))->assertSessionHasErrors('date_to');
        $this->get(route('reports.hard-copy-supplements', ['status' => 'COMPLETED', 'print_template' => 'single']))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 1);
        $other = User::where('username', 'trungtam_tp')->firstOrFail();
        $other->givePermissionTo(['report.view', 'report.export']);
        $this->actingAs($other)->get(route('reports.hard-copy-supplements', ['distribution_center_id' => $this->center->distribution_center_id]))->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 0);
        $this->travelBack();
    }

    public function test_workspaces_render_for_each_role_and_snapshots_are_optional(): void
    {
        $item = $this->create();
        $directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR');
        foreach (['center' => $this->center, 'dvkh' => $this->dvkh, 'ptn' => $this->ptn] as $role => $user) {
            if ($role === 'ptn') $this->action($this->dvkh, $item, 'approve')->assertSessionHasNoErrors();
            $response = $this->actingAs($user)->get(route('hard-copy-supplements.show', $item));
            $response->assertOk();
            $response->assertSee('supplement-detail', false)->assertSee('supplement-overview', false)
                ->assertSee('Mới nhất trước')->assertSee('Lượt mở file không phải số bản thực tế đã in.');
            $listResponse = $this->get(route('hard-copy-supplements.index'))->assertOk();
            $listResponse->assertSee('supplement-index', false)
                ->assertSee('supplement-filter-panel', false)
                ->assertSee('Danh sách yêu cầu bổ sung')
                ->assertSee('Xem chi tiết ' . $item->number);
            if ($role === 'center') $listResponse->assertDontSee('id="supplement-center"', false);
            else $listResponse->assertSee('id="supplement-center"', false);
            if ($directory) {
                if (!is_dir($directory)) mkdir($directory, 0777, true);
                file_put_contents($directory . '/supplement-' . $role . '.html', $response->getContent());
                file_put_contents($directory . '/supplement-list-' . $role . '.html', $listResponse->getContent());
            }
        }
        $response = $this->actingAs(User::where('username', 'admin')->firstOrFail())->get(route('reports.hard-copy-supplements'));
        $response->assertOk()->assertSee('supplement-report-kpis', false)->assertSee('Định nghĩa số liệu')->assertSee('Tổng không trùng');
        if ($directory) file_put_contents($directory . '/supplement-report.html', $response->getContent());
        $emptyResponse = $this->get(route('reports.hard-copy-supplements', ['keyword' => '__khong_co_ket_qua__']))->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['requests'] === 0);
        if ($directory) file_put_contents($directory . '/supplement-report-empty.html', $emptyResponse->getContent());
        $this->center->givePermissionTo('report.view');
        $centerReport = $this->actingAs($this->center)->get(route('reports.hard-copy-supplements'))->assertOk()
            ->assertDontSee('id="supplement-center"', false);
        if ($directory) file_put_contents($directory . '/supplement-report-center.html', $centerReport->getContent());
    }

    public function test_detail_back_links_use_the_same_role_filters_as_the_menu(): void
    {
        $item = $this->create();
        $filter = new \App\AdminLte\Menu\PendingSupplementBadgeFilter;
        foreach ([$this->center, $this->dvkh, $this->ptn, User::where('username', 'admin')->firstOrFail(),
            User::where('username', 'viewer')->firstOrFail()] as $user) {
            $response = $this->actingAs($user)->get(route('hard-copy-supplements.show', $item))->assertOk();
            $menu = $filter->transform(['url' => 'hard-copy-supplements']);
            $expected = url($menu['url']);
            $dom = new \DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new \DOMXPath($dom);
            foreach (['supplement-links', 'workflow-action-buttons'] as $class) {
                $links = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]/a');
                $this->assertGreaterThan(0, $links->length);
                $this->assertSame($expected, $links->item(0)->getAttribute('href'));
            }
        }
    }

    public function test_menu_badge_and_work_queue_follow_permissions_and_certificate_eligibility(): void
    {
        $first = $this->create();
        $secondCertificate = $this->certificate($this->center);
        $second = $this->create($secondCertificate);
        $this->action($this->dvkh, $second, 'approve')->assertSessionHasNoErrors();
        $invalidCertificate = $this->certificate($this->center);
        $this->create($invalidCertificate);
        $invalidCertificate->update(['revoked_at' => now()]);
        $filter = new \App\AdminLte\Menu\PendingSupplementBadgeFilter;
        $menu = ['url' => 'hard-copy-supplements', 'text' => 'Bổ sung bản ký tươi'];
        foreach (['dvkh' => [$this->dvkh, 1, $first->id], 'ptn' => [$this->ptn, 1, $second->id],
            'admin' => [User::where('username', 'admin')->firstOrFail(), 2, null]] as [$user, $count, $id]) {
            $this->actingAs($user);
            $badge = $filter->transform($menu);
            $this->assertSame((string) $count, $badge['label']);
            $this->assertSame('hard-copy-supplements?work_queue=1', $badge['url']);
            $response = $this->get(route('hard-copy-supplements.index', ['work_queue' => 1]))->assertOk();
            $response->assertViewHas('items', fn ($items) => $items->total() === $count && (!$id || $items->first()->id === $id));
            $response->assertSee('Chỉ việc chờ xử lý của tôi');
            $this->get(route('hard-copy-supplements.index'))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 3);
        }
        $this->actingAs($this->center);
        $this->assertSame($menu, $filter->transform($menu));
        $this->get(route('hard-copy-supplements.index'))->assertOk()->assertDontSee('id="supplement-work-queue"', false);
        $this->get(route('hard-copy-supplements.index', ['work_queue' => 1]))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 0);
        $this->action($this->dvkh, $first, 'approve')->assertSessionHasNoErrors();
        $this->actingAs($this->dvkh);
        $this->assertArrayNotHasKey('label', $filter->transform($menu));
        $this->get(route('hard-copy-supplements.index', ['work_queue' => 1]))->assertOk()->assertViewHas('items', fn ($items) => $items->total() === 0);
    }

    public function test_workflow_menu_badges_match_dvkh_ptn_and_manager_queues(): void
    {
        $this->certificate->request->update(['status' => 'WAIT_DVKH']);
        $waiting = $this->certificate($this->center);
        $waiting->request->update(['status' => 'WAIT_PTN']);
        $returned = $this->certificate($this->center);
        $returned->request->update(['status' => 'PTN_PROCESSING', 'last_returned_to' => 'PTN']);
        $other = $this->certificate($this->center);
        $other->request->update(['status' => 'PTN_PROCESSING', 'last_returned_to' => null]);
        $draft = $this->certificate($this->center);
        $draft->request->update(['status' => 'DRAFT']);
        $waiting->update(['status' => 'WAIT_PTN_MANAGER_APPROVAL', 'signed_at' => null, 'smartca_status' => null]);
        $returned->update(['status' => 'DRAFT', 'signed_at' => null, 'smartca_status' => null]);
        $other->update(['status' => 'WAIT_PTN_MANAGER_APPROVAL', 'signed_at' => null, 'smartca_status' => 'PENDING']);
        $draft->update(['status' => 'WAIT_PTN_MANAGER_APPROVAL', 'signed_at' => null, 'smartca_status' => 'EXPIRED']);
        $filter = new \App\AdminLte\Menu\PendingWorkflowBadgeFilter;
        $cases = [
            ['dvkh/requests', $this->dvkh, '1'],
            ['ptn/requests', $this->ptn, '2'],
            ['quality-certificates/signing-queue', User::where('username', 'admin')->firstOrFail(), '2'],
        ];
        foreach ($cases as [$url, $user, $expected]) {
            $this->actingAs($user);
            $this->assertSame($expected, $filter->transform(['url' => $url])['label']);
        }
        $badge = $filter->transform(['url' => 'quality-certificates/signing-queue']);
        $this->assertSame('quality-certificates/signing-queue?status=WAIT_APPROVAL', $badge['url']);
        $this->get('/quality-certificates/signing-queue?status=WAIT_APPROVAL')->assertOk()
            ->assertViewHas('certificates', fn ($items) => $items->total() === 2);
        $this->actingAs($this->ptn)->get('/ptn/requests')->assertOk()
            ->assertViewHas('requests', fn ($items) => $items->total() === 2);
        $this->actingAs($this->dvkh)->get('/dvkh/requests')->assertOk()
            ->assertViewHas('requests', fn ($items) => $items->total() === 1);
        $this->certificate->request->update(['status' => 'WAIT_PTN']);
        $this->assertArrayNotHasKey('label', $filter->transform(['url' => 'dvkh/requests']));
        $this->assertSame(['url' => 'ptn/requests'], $filter->transform(['url' => 'ptn/requests']));
        $this->actingAs($this->center);
        foreach ($cases as [$url]) $this->assertSame(['url' => $url], $filter->transform(['url' => $url]));
        $this->assertSame(['url' => 'other'], $filter->transform(['url' => 'other']));
    }

    public function test_returned_and_cancelled_detail_does_not_show_active_processing_stage(): void
    {
        $item = $this->create();
        $this->action($this->dvkh, $item, 'return', ['comment' => 'Cần kiểm tra lại số bản'])->assertSessionHasNoErrors();
        $this->actingAs($this->center)->get(route('hard-copy-supplements.show', $item))->assertOk()
            ->assertSee('Cần kiểm tra lại số bản')->assertSee('Điều chỉnh và gửi lại DVKH')
            ->assertDontSee('supplement-stage current', false);
        $this->action($this->center, $item, 'cancel', ['comment' => 'Không còn nhu cầu bổ sung'])->assertSessionHasNoErrors();
        $this->get(route('hard-copy-supplements.show', $item))->assertOk()
            ->assertSee('Không còn nhu cầu bổ sung')->assertSee('Đã dừng')
            ->assertDontSee('supplement-stage current', false);
    }

    public function test_real_batch_and_single_rendering_do_not_modify_signed_pdf_or_original_request(): void
    {
        $group = \App\Models\ProductGroup::create(['code' => 'SUP', 'name' => 'Nhóm test', 'is_active' => true]);
        $product = \App\Models\Product::create(['product_group_id' => $group->id, 'product_code' => 'SUP110',
            'product_name' => 'Ống PVC-U DN110', 'unit' => 'm', 'nominal_size' => 'DN110', 'technical_requirements' => 'PN10', 'is_active' => true]);
        $this->certificate->details()->create(['product_id' => $product->id, 'quantity' => 45, 'nominal_size' => 'DN110',
            'technical_requirements' => 'PN10', 'quality_standard' => 'ISO 1452-2:2009']);
        foreach (['single', 'batch'] as $template) {
            $item = $this->create();
            $this->action($this->dvkh, $item, 'approve')->assertSessionHasNoErrors();
            $response = $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => $template]);
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->assertGreaterThanOrEqual(1, $item->fresh()->page_count);
            $this->action($this->ptn, $item, 'complete')->assertSessionHasNoErrors();
        }
        $this->assertSame('private-original-1.pdf', $this->certificate->fresh()->pdf_path);
        $this->assertSame(10, $this->certificate->request->fresh()->hard_copy_quantity);
    }

    public function test_cancel_after_print_needs_internal_confirmation_and_is_not_counted_as_approved(): void
    {
        $item = $this->create();
        $this->action($this->dvkh, $item, 'approve')->assertSessionHasNoErrors();
        $this->mockPrint();
        $this->actingAs($this->ptn)->post(route('hard-copy-supplements.print', $item), ['print_template' => 'single'])->assertOk();
        $this->action($this->center, $item, 'cancel', ['comment' => 'Xin hủy', 'confirm_stop_printing' => 1])->assertForbidden();
        $this->action($this->dvkh, $item, 'cancel', ['comment' => 'Đã phối hợp PTN dừng in', 'confirm_stop_printing' => 1])->assertSessionHasNoErrors();
        $this->assertSame('CANCELLED', $item->fresh()->status);
        $response = $this->actingAs(User::where('username', 'admin')->firstOrFail())->get(route('reports.hard-copy-supplements'));
        $response->assertOk();
        $this->assertEquals(0, $response->viewData('summary')['approved']);
        $this->assertEquals(0, $response->viewData('summary')['single_sheets']);
    }

    public function test_certificate_detail_exposes_supplement_action_and_explains_internal_read_only_access(): void
    {
        $response = $this->actingAs($this->center)->get(route('quality-certificates.show', $this->certificate));
        $response->assertOk()->assertSee('Yêu cầu bổ sung bản ký tươi')->assertSee('href="#hard-copy-supplement-panel"', false)
            ->assertSee('id="hard-copy-supplement-panel"', false)->assertSee(route('hard-copy-supplements.store', $this->certificate), false);
        $admin = User::where('username', 'admin')->firstOrFail();
        $response = $this->actingAs($admin)->get(route('quality-certificates.show', $this->certificate));
        $response->assertOk()->assertSee('Bổ sung bản ký tươi')->assertSee('Chỉ tài khoản Trung tâm sở hữu phiếu')
            ->assertDontSee(route('hard-copy-supplements.store', $this->certificate), false);
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0777, true);
            file_put_contents($directory . '/supplement-certificate-admin.html', $response->getContent());
            $response = $this->actingAs($this->center)->get(route('quality-certificates.show', $this->certificate));
            file_put_contents($directory . '/supplement-certificate-center.html', $response->getContent());
        }
    }

    public function test_list_search_sort_and_status_counts_keep_other_filters(): void
    {
        $a = $this->create(quantity: 4);
        $second = $this->certificate($this->center);
        $b = $this->create($second, 9);
        $this->action($this->dvkh, $b, 'return', ['comment' => 'Cần bổ sung thông tin'])->assertSessionHasNoErrors();
        $this->actingAs($this->dvkh);
        $this->get(route('hard-copy-supplements.index', ['keyword' => strtolower($a->number)]))->assertOk()
            ->assertViewHas('items', fn ($rows) => $rows->pluck('id')->all() === [$a->id]);
        $this->get(route('hard-copy-supplements.index', ['keyword' => 'Khách hàng bổ sung 2']))->assertOk()
            ->assertViewHas('items', fn ($rows) => $rows->pluck('id')->all() === [$b->id]);
        foreach (['asc' => [$a->id, $b->id], 'desc' => [$b->id, $a->id]] as $direction => $ids) {
            $this->get(route('hard-copy-supplements.index', ['sort' => 'quantity', 'direction' => $direction]))->assertOk()
                ->assertViewHas('items', fn ($rows) => $rows->pluck('id')->all() === $ids);
        }
        $this->get(route('hard-copy-supplements.index', ['sort' => 'certificate_no', 'direction' => 'asc']))->assertOk()
            ->assertViewHas('items', fn ($rows) => $rows->total() === 2);
        $response = $this->get(route('hard-copy-supplements.index', ['status' => 'WAIT_DVKH']));
        $response->assertOk()->assertViewHas('items', fn ($rows) => $rows->total() === 1);
        $this->assertEquals(1, $response->viewData('statusCounts')['RETURNED']);
        $this->assertEquals(1, $response->viewData('statusCounts')['WAIT_DVKH']);
        $response = $this->get(route('hard-copy-supplements.index', ['status' => 'WAIT_DVKH', 'sales_unit_id' => $this->certificate->request->sales_unit_id]));
        $response->assertOk();
        $this->assertEquals(1, $response->viewData('statusCounts')->sum());
        $this->get(route('hard-copy-supplements.show', $b))->assertOk()->assertSee('Cần bổ sung thông tin')->assertSee('supplementCancelModal')->assertDontSee('id="resubmitSupplementForm"', false);
    }
}
