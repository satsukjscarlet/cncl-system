<?php

namespace Tests\Feature;

use App\Exports\WorkReportExport;
use App\Helpers\ActivityLogger;
use App\Http\Middleware\EnsureLoginDeviceIsApproved;
use App\Models\CertificateRequest;
use App\Models\Customer;
use App\Models\DistributionCenter;
use App\Models\HardCopySupplement;
use App\Models\QualityCertificate;
use App\Models\User;
use App\Services\WorkReportService;
use Database\Seeders\DistributionCenterSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class WorkReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([PermissionSeeder::class, DistributionCenterSeeder::class, UserSeeder::class]);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    public function test_center_is_blocked_even_with_report_permissions_and_internal_access_requires_permission(): void
    {
        $this->withoutMiddleware(EnsureLoginDeviceIsApproved::class);
        $center = User::where('username', 'trungtam_np')->firstOrFail();
        $center->givePermissionTo(['report.view', 'report.export']);
        $center->forceFill(['work_report_scope' => 'all'])->save();
        $this->actingAs($center)->get('/reports/work')->assertForbidden();
        $this->get('/reports/work/export')->assertForbidden();
        $viewer = User::factory()->create();
        $this->actingAs($viewer)->get('/reports/work')->assertForbidden();
        $viewer->givePermissionTo('report.view');
        $viewer->forceFill(['work_department' => 'DVKH'])->save();
        $this->get('/reports/work')->assertOk()->assertDontSee('Xuất Excel');
        $this->get('/reports/work/export')->assertForbidden();
    }

    public function test_daily_report_deduplicates_retries_filters_accounts_and_keeps_system_results_separate(): void
    {
        [$request, $certificate, $dvkh, $ptn] = $this->fixtures();
        $this->log($dvkh, $request, 'DVKH kiểm tra yêu cầu', 'approve', '2028-02-04 10:00:00');
        $this->log($dvkh, $request, 'DVKH kiểm tra yêu cầu', 'approve', '2028-02-04 11:00:00');
        $this->log($ptn, $certificate, 'PTN lập phiếu', 'create_certificate', '2028-02-05 10:00:00');
        $this->log($ptn, $certificate, 'PTN lập phiếu', 'create_certificate', '2028-02-05 11:00:00');
        $this->log($ptn, $certificate, 'PTN lập phiếu', 'create_certificate', '2028-02-06 11:00:00');
        $this->log($ptn, $certificate, 'Phiếu CNCL', 'export_pdf', '2028-02-06 12:00:00');
        $admin = User::where('username', 'admin')->firstOrFail();
        $response = $this->actingAs($admin)->get('/reports/work?year=2028&month=2');
        $response->assertOk()->assertSee('Định nghĩa số liệu')->assertSee('29')
            ->assertViewHas('sections', function ($sections) {
                $totals = collect($sections)->pluck('total', 'kind');

                return $totals['request_submit'] === 1 && $totals['dvkh_approve'] === 1
                    && $totals['ptn_create'] === 2 && $totals['signed'] === 1
                    && $totals['supplement_complete'] === 1;
            });
        $this->get('/reports/work?year=2028&month=2&department=PTN&user_id='.$ptn->id.'&group_by=account')
            ->assertOk()->assertViewHas('events', fn ($events) => $events->count() === 3 && $events->pluck('actor_id')->unique()->all() === [$ptn->id]);
        $this->get('/reports/work?year=2028&month=2&detail_kind=ptn_create&detail_day=5&detail_row=center:'.$request->distribution_center_id)
            ->assertOk()->assertViewHas('details', fn ($rows) => $rows->total() === 1)
            ->assertViewHas('sections', fn ($sections) => collect($sections)->firstWhere('kind', 'ptn_create')['total'] === 2);
        $otherCenter = DistributionCenter::where('code', 'TP')->firstOrFail();
        $this->get('/reports/work?year=2028&month=2&distribution_center_id='.$otherCenter->id)->assertOk()
            ->assertViewHas('events', fn ($events) => $events->isEmpty());
        $this->get('/reports/work?year=2028&month=3')->assertOk()->assertViewHas('events', fn ($events) => $events->isEmpty());
        $this->get('/reports/work?year=2028&month=2&department=DVKH&kind=ptn_create')->assertOk()->assertSee('Nhóm công việc không thuộc phòng ban');

        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/work-report.html', $response->getContent());
        }
    }

    public function test_native_fallback_does_not_guess_dvkh_or_signer_and_excludes_drafts(): void
    {
        [$request, $certificate] = $this->fixtures();
        $draft = $request->replicate();
        $draft->request_no = 'YC-DRAFT-WORK';
        $draft->status = 'DRAFT';
        $draft->submitted_at = null;
        $draft->sent_to_ptn_at = null;
        $draft->save();
        $certificate->update(['status' => 'REVOKED']);
        $request->delete();
        $service = app(WorkReportService::class);
        $report = $service->report($service->filters(Request::create('/', 'GET', ['year' => 2028, 'month' => 2])));
        $this->assertSame(1, $report['events']->where('kind', 'request_submit')->count());
        $this->assertNull($report['events']->firstWhere('kind', 'dvkh_approve')['actor_id']);
        $this->assertNull($report['events']->firstWhere('kind', 'signed')['actor_id']);
        $this->assertSame(1, $report['events']->where('kind', 'signed')->count());
    }

    public function test_new_work_metadata_records_workflow_department_and_identity_not_current_roles(): void
    {
        [$request, , $dvkh] = $this->fixtures();
        $this->actingAs($dvkh);
        ActivityLogger::log('DVKH kiểm tra yêu cầu', 'approve', 'Test', ['status' => 'WAIT_DVKH'], ['status' => 'WAIT_PTN'], $request);
        $work = Activity::latest('id')->first()->properties['work'];
        $this->assertSame('dvkh_approve', $work['kind']);
        $this->assertSame('DVKH', $work['department']);
        $this->assertSame($dvkh->id, $work['actor_id']);
        $this->assertSame($request->distribution_center_id, $work['center_id']);
        $report = app(WorkReportService::class)->report(app(WorkReportService::class)->filters(Request::create('/')));
        $this->assertSame($dvkh->id, $report['events']->firstWhere('kind', 'dvkh_approve')['actor_id']);
        $this->assertNull(WorkReportService::snapshot('Yêu cầu cấp phiếu', 'create', null, ['status' => 'DRAFT'], $request));
    }

    public function test_different_people_are_counted_separately_and_snapshot_survives_center_change(): void
    {
        [$request, $certificate, $dvkh, $ptn] = $this->fixtures();
        Carbon::setTestNow('2028-02-08 09:00:00');
        try {
            $this->actingAs($dvkh);
            ActivityLogger::log('DVKH kiểm tra yêu cầu', 'approve', 'Test', [], ['status' => 'WAIT_PTN'], $request);
            $this->actingAs($ptn);
            ActivityLogger::log('DVKH kiểm tra yêu cầu', 'approve', 'Test', [], ['status' => 'WAIT_PTN'], $request);
            $originalCenter = $request->distribution_center_id;
            $request->update(['distribution_center_id' => DistributionCenter::where('code', 'TP')->value('id')]);
            $service = app(WorkReportService::class);
            $this->actingAs(User::where('username', 'admin')->firstOrFail());
            $report = $service->report($service->filters(Request::create('/', 'GET', ['year' => 2028, 'month' => 2])));
            $day = $report['events']->where('kind', 'dvkh_approve')->filter(fn ($event) => str_starts_with($event['occurred_at'], '2028-02-08'));
            $this->assertCount(2, $day);
            $this->assertEqualsCanonicalizing([$dvkh->id, $ptn->id], $day->pluck('actor_id')->all());
            $this->assertSame([$originalCenter], $day->pluck('center_id')->unique()->values()->all());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_export_matches_monthly_matrix_and_actual_workbook_can_be_generated(): void
    {
        $this->fixtures();
        $service = app(WorkReportService::class);
        $report = $service->report($service->filters(Request::create('/', 'GET', ['year' => 2028, 'month' => 2])));
        $export = new WorkReportExport($report);
        $sheets = $export->sheets();
        $this->assertCount(3, $sheets);
        $this->assertCount(33, $sheets[0]->headings());
        $this->assertCount($report['events']->count(), $sheets[1]->array());
        $binary = Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        $this->assertStringStartsWith('PK', $binary);
        $cell = (new Spreadsheet)->getActiveSheet()->getCell('A1');
        $sheets[0]->bindValue($cell, '=HYPERLINK("https://example.invalid")');
        $this->assertSame(DataType::TYPE_STRING, $cell->getDataType());
        Excel::fake();
        $admin = User::where('username', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/reports/work/export?year=2028&month=2&detail_day=15')->assertOk();
        Excel::assertDownloaded('bao-cao-cong-viec-2028-02.xlsx');
        $this->get('/reports/work?month=13')->assertSessionHasErrors('month');
    }

    public function test_employees_only_see_their_department_and_their_own_work_including_excel(): void
    {
        [$request, $certificate, $dvkh, $ptn] = $this->fixtures();
        $other = User::factory()->create();
        $other->assignRole('PTN');
        $this->log($other, $certificate, 'PTN lập phiếu', 'create_certificate', '2028-02-08 10:00:00');
        $this->actingAs($ptn);
        $response = $this->get('/reports/work?year=2028&month=2');
        $response->assertOk()->assertDontSee('id="work-user"', false)->assertDontSee('name="department"', false)
            ->assertDontSee('data-kind="dvkh_approve"', false)->assertDontSee('data-kind="signed"', false)
            ->assertViewHas('events', fn ($events) => $events->count() === 2 && $events->every(fn ($event) => $event['actor_id'] === $ptn->id && $event['department'] === 'PTN'))
            ->assertViewHas('users', fn ($users) => $users->keys()->all() === [$ptn->id]);
        foreach ([['user_id' => $other->id], ['department' => 'DVKH'], ['kind' => 'dvkh_approve'], ['detail_kind' => 'signed'], ['detail_row' => 'account:'.$other->id]] as $attack) {
            $query = http_build_query(['year' => 2028, 'month' => 2] + $attack);
            $this->get('/reports/work?'.$query)->assertForbidden();
            $this->get('/reports/work/export?'.$query)->assertForbidden();
        }
        Excel::fake();
        $this->get('/reports/work/export?year=2028&month=2')->assertOk();
        Excel::assertDownloaded('bao-cao-cong-viec-2028-02.xlsx', function (WorkReportExport $export) use ($ptn) {
            $rows = $export->sheets()[1]->array();

            return count($rows) === 2 && collect($rows)->every(fn ($row) => str_contains($row[3], $ptn->username));
        });
        $this->actingAs($dvkh)->get('/reports/work?year=2028&month=2')->assertOk()
            ->assertViewHas('sections', fn ($sections) => collect($sections)->pluck('kind')->all() === ['dvkh_approve', 'supplement_approve']);
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/work-report-self.html', $response->getContent());
        }
    }

    public function test_manager_only_sees_ptn_and_dvkh_team_requires_explicit_assignment(): void
    {
        [$request, $certificate, $dvkh, $ptn] = $this->fixtures();
        $manager = User::where('username', 'truongptn')->firstOrFail();
        $response = $this->actingAs($manager)->get('/reports/work?year=2028&month=2');
        $response->assertOk()->assertViewHas('scope', fn ($scope) => $scope['mode'] === 'team' && $scope['departments'] === ['PTN'])
            ->assertViewHas('sections', fn ($sections) => collect($sections)->pluck('kind')->all() === ['ptn_create', 'manager_approve', 'signed', 'supplement_complete'])
            ->assertViewHas('events', fn ($events) => $events->every(fn ($event) => $event['department'] === 'PTN'));
        $this->get('/reports/work?department=DVKH')->assertForbidden();
        $this->get('/reports/work?department=MANAGER')->assertOk()->assertViewHas('scope', fn ($scope) => $scope['departments'] === ['PTN']);
        $this->get('/reports/work?user_id='.$dvkh->id)->assertForbidden();
        $this->get('/reports/work/export?department=DVKH')->assertForbidden();
        $dvkh->forceFill(['work_department' => 'DVKH', 'work_report_scope' => 'team', 'work_report_departments' => ['DVKH']])->save();
        $this->actingAs($dvkh)->get('/reports/work?year=2028&month=2')->assertOk()
            ->assertViewHas('scope', fn ($scope) => $scope['mode'] === 'team')
            ->assertViewHas('events', fn ($events) => $events->count() === 2 && $events->every(fn ($event) => $event['department'] === 'DVKH'));
        $this->get('/reports/work?department=PTN')->assertForbidden();
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/work-report-team.html', $response->getContent());
        }
    }

    public function test_only_admin_can_set_report_scope_and_profile_cannot_escalate_it(): void
    {
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $payload = ['name' => $dvkh->name, 'username' => $dvkh->username, 'email' => $dvkh->email, 'role' => 'DVKH', 'is_active' => 1,
            'work_department' => 'DVKH', 'work_report_scope' => 'team', 'work_report_departments' => ['DVKH']];
        $this->put(route('users.update', $dvkh), $payload)->assertRedirect(route('users.index'));
        $this->assertSame('team', $dvkh->fresh()->work_report_scope);
        $this->assertSame(['DVKH'], $dvkh->fresh()->work_report_departments);
        $withoutReportFields = array_diff_key($payload, array_flip(['work_department', 'work_report_scope', 'work_report_departments']));
        $this->put(route('users.update', $dvkh), $withoutReportFields + ['work_department' => 'DVKH'])->assertRedirect(route('users.index'));
        $this->assertSame(['DVKH'], $dvkh->fresh()->work_report_departments);
        if ($directory = getenv('CNCL_SUPPLEMENT_UI_SNAPSHOT_DIR')) {
            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            file_put_contents($directory.'/user-work-scope.html', $this->get(route('users.edit', $dvkh))->assertOk()->getContent());
        }
        $this->put(route('users.update', $dvkh), array_replace($payload, ['work_report_departments' => []]))->assertSessionHasErrors('work_report_departments');
        $dvkh->givePermissionTo('user.update');
        $this->actingAs($dvkh)->put(route('users.update', $dvkh), array_replace($payload, ['work_report_scope' => 'all']))->assertForbidden();
        $this->patch(route('profile.update'), ['name' => $dvkh->name, 'email' => $dvkh->email, 'work_report_scope' => 'all'])->assertRedirect();
        $this->assertSame('team', $dvkh->fresh()->work_report_scope);
        $this->assertSame(['DVKH'], $dvkh->fresh()->work_report_departments);
        $leader = User::where('username', 'lanhdao')->firstOrFail();
        $this->actingAs($leader)->get('/reports/work')->assertForbidden();
        $leader->forceFill(['work_report_scope' => 'all'])->save();
        $this->get('/reports/work')->assertOk()->assertViewHas('scope', fn ($scope) => $scope['mode'] === 'all');
    }

    public function test_scope_migration_can_resume_without_overwriting_existing_account_settings(): void
    {
        $user = User::where('username', 'dvkh')->firstOrFail();
        $user->forceFill(['work_department' => 'DVKH', 'work_report_scope' => 'team', 'work_report_departments' => ['DVKH']])->save();
        $before = $user->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_03_000001_add_work_report_scope_to_users.php');
        $migration->up();
        $this->assertSame($before, $user->fresh()->getAttributes());
    }

    private function log(User $user, Model $subject, string $module, string $action, string $at): void
    {
        Activity::create(['log_name' => $module, 'description' => 'Work test', 'subject_type' => $subject::class, 'subject_id' => $subject->id,
            'causer_type' => User::class, 'causer_id' => $user->id, 'properties' => ['action' => $action], 'created_at' => $at, 'updated_at' => $at]);
    }

    private function fixtures(): array
    {
        $center = DistributionCenter::where('code', 'NP')->firstOrFail();
        $dvkh = User::where('username', 'dvkh')->firstOrFail();
        $ptn = User::where('username', 'ptn')->firstOrFail();
        $customer = Customer::create(['distribution_center_id' => $center->id, 'customer_code' => 'WORK-CUSTOMER', 'customer_name' => 'Khách hàng công việc', 'project_name' => 'Công trình', 'is_active' => true]);
        $request = CertificateRequest::create(['request_no' => 'YC-WORK', 'distribution_center_id' => $center->id, 'customer_id' => $customer->id,
            'created_by' => $ptn->id, 'status' => 'COMPLETED', 'delivery_date' => '2028-02-01', 'submitted_at' => '2028-02-03 10:00:00',
            'submitted_by' => User::where('username', 'trungtam_np')->value('id'), 'sent_to_ptn_at' => '2028-02-04 10:00:00']);
        $certificate = QualityCertificate::create(['certificate_no' => 'CNCL-WORK', 'certificate_request_id' => $request->id, 'created_by' => $ptn->id,
            'status' => 'ISSUED', 'signed_at' => '2028-02-07 10:00:00']);
        $certificate->forceFill(['created_at' => '2028-02-05 10:00:00'])->save();
        HardCopySupplement::create(['quality_certificate_id' => $certificate->id, 'requested_by' => $ptn->id, 'quantity' => 4, 'reason' => 'Test',
            'status' => 'COMPLETED', 'approved_at' => '2028-02-06 10:00:00', 'approved_by' => $dvkh->id,
            'completed_at' => '2028-02-07 10:00:00', 'completed_by' => $ptn->id, 'submitted_at' => '2028-02-05 10:00:00']);

        return [$request, $certificate, $dvkh, $ptn];
    }
}
