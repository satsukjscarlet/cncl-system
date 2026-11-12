<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\HardCopySupplement;
use App\Models\QualityCertificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;

class WorkReportService
{
    private array $coveredDays = [];

    private array $coveredTurns = [];

    public const DEPARTMENTS = ['CENTER' => 'Trung tâm', 'DVKH' => 'Dịch vụ khách hàng', 'PTN' => 'Phòng thử nghiệm'];

    public const KINDS = [
        'sales_unit_submit' => ['Đơn vị bán hàng gửi Trung tâm', 'CENTER'],
        'request_submit' => ['Trung tâm gửi yêu cầu', 'CENTER'],
        'dvkh_approve' => ['DVKH xác nhận chuyển PTN', 'DVKH'],
        'ptn_create' => ['PTN lập phiếu CNCL', 'PTN'],
        'manager_approve' => ['Duyệt / gửi ký số', 'PTN'],
        'signed' => ['Phiếu ký số thành công (kết quả hệ thống)', 'PTN'],
        'supplement_approve' => ['DVKH duyệt bổ sung ký tươi', 'DVKH'],
        'supplement_complete' => ['PTN hoàn tất bổ sung ký tươi', 'PTN'],
    ];

    public static function kind(string $module, string $action, mixed $old, mixed $new): ?string
    {
        if ($module === 'Yêu cầu cấp phiếu' && in_array($action, ['create', 'update', 'submit'], true)
            && data_get($new, 'status') === 'WAIT_CENTER' && data_get($old, 'status') !== 'WAIT_CENTER') return 'sales_unit_submit';
        if ($module === 'Yêu cầu cấp phiếu' && in_array($action, ['create', 'update', 'submit'], true)
            && data_get($new, 'status') === 'WAIT_DVKH'
            && ($action === 'submit' || data_get($old, 'status') !== 'WAIT_DVKH')) {
            return 'request_submit';
        }
        if ($module === 'DVKH kiểm tra yêu cầu' && $action === 'approve') {
            return 'dvkh_approve';
        }
        if (in_array($module, ['PTN lập phiếu', 'PTN lập phiếu trực tiếp'], true)
            && in_array($action, ['create_certificate', 'create_certificate_from_request', 'direct_create_certificate'], true)) {
            return 'ptn_create';
        }
        if ($module === 'Phiếu CNCL' && in_array($action, ['approve_for_signing', 'smartca_request', 'smartca_request_resend'], true)) {
            return 'manager_approve';
        }
        if ($module === 'Bổ sung bản ký tươi' && $action === 'approve') {
            return 'supplement_approve';
        }
        if ($module === 'Bổ sung bản ký tươi' && $action === 'complete') {
            return 'supplement_complete';
        }

        return null;
    }

    public static function snapshot(string $module, string $action, mixed $old, mixed $new, ?Model $subject): ?array
    {
        $kind = self::kind($module, $action, $old, $new);
        if (! $kind || ! $subject) {
            return null;
        }
        $request = $subject instanceof CertificateRequest ? $subject
            : ($subject instanceof QualityCertificate ? $subject->request : ($subject instanceof HardCopySupplement ? $subject->certificate?->request : null));

        return [
            'version' => 1, 'kind' => $kind, 'department' => self::KINDS[$kind][1],
            'actor_id' => auth()->id(), 'actor_name' => auth()->user()?->name,
            'request_id' => $request?->id, 'center_id' => $request?->distribution_center_id,
        ];
    }

    public function filters(Request $request): array
    {
        // Old links used a separate manager department; these steps now belong to PTN.
        if ($request->input('department') === 'MANAGER') {
            $request->merge(['department' => 'PTN']);
        }
        $data = $request->validate([
            'month' => ['nullable', 'integer', 'between:1,12'], 'year' => ['nullable', 'integer', 'between:2000,2100'],
            'department' => ['nullable', Rule::in(array_keys(self::DEPARTMENTS))],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'kind' => ['nullable', Rule::in(array_keys(self::KINDS))],
            'distribution_center_id' => ['nullable', 'integer', 'exists:distribution_centers,id'],
            'group_by' => ['nullable', Rule::in(['center', 'account'])],
            'detail_day' => ['nullable', 'integer', 'between:1,31'],
            'detail_row' => ['nullable', 'regex:/^(center|account):[0-9]+$/'],
            'detail_kind' => ['nullable', Rule::in(array_keys(self::KINDS))],
        ]);

        return array_merge(['month' => now()->month, 'year' => now()->year, 'department' => '', 'user_id' => '',
            'kind' => '', 'distribution_center_id' => '', 'group_by' => 'center'], array_filter($data, fn ($value) => $value !== null && $value !== ''));
    }

    public function report(array $filters, ?User $viewer = null): array
    {
        $viewer ??= auth()->user();
        abort_unless($viewer, 403);
        $access = app(WorkReportAccess::class);
        $filters = $access->constrain($filters, $viewer);
        $scope = $access->scope($viewer);
        $scope['label'] = match ($scope['mode']) {
            'self' => 'Cá nhân: '.$viewer->name.' ('.$viewer->username.')',
            'team' => 'Phòng được quản lý: '.implode(', ', array_map(fn ($department) => self::DEPARTMENTS[$department], $scope['departments'])),
            default => 'Toàn hệ thống',
        };
        $kindOptions = array_filter(self::KINDS, fn ($meta, $kind) => in_array($meta[1], $scope['departments'], true)
            && ($scope['mode'] !== 'self' || $kind !== 'signed'), ARRAY_FILTER_USE_BOTH);
        $start = Carbon::create($filters['year'], $filters['month'], 1)->startOfDay();
        $end = $start->copy()->addMonth();
        $centers = DistributionCenter::orderBy('code')->get()->keyBy('id');
        $users = User::with('roles')->orderBy('name')->get()->keyBy('id');
        $events = $this->events($start, $end, $filters, array_keys($kindOptions));
        $events = $events->filter(fn ($event) => in_array($event['department'], $scope['departments'], true)
            && ($scope['mode'] !== 'self' || ($event['actor_id'] === $viewer->id && $event['kind'] !== 'signed')));
        if ($scope['mode'] !== 'all') {
            $actorIds = $events->pluck('actor_id')->filter()->unique()->all();
            $users = $users->filter(fn ($account) => $scope['mode'] === 'self' ? $account->id === $viewer->id
                : (! $account->hasRole('TrungTam') && (in_array($access->department($account), $scope['departments'], true) || in_array($account->id, $actorIds, true))));
        }
        if ($filters['user_id']) {
            abort_unless($users->has((int) $filters['user_id']), 403);
        }
        if (str_starts_with($filters['detail_row'] ?? '', 'account:') && $filters['detail_row'] !== 'account:0') {
            abort_unless($users->has((int) substr($filters['detail_row'], 8)), 403);
        }
        $events = $events->filter(fn ($e) => (! $filters['department'] || $e['department'] === $filters['department'])
            && (! $filters['kind'] || $e['kind'] === $filters['kind'])
            && (! $filters['user_id'] || $e['actor_id'] === (int) $filters['user_id'])
            && (! $filters['distribution_center_id'] || $e['center_id'] === (int) $filters['distribution_center_id']));

        // A retry or duplicate log is not extra work; a different actor/day is a separate processing turn.
        $unique = [];
        foreach ($events->sortBy('occurred_at') as $event) {
            $key = $this->coverageKey($event).'|'.($event['actor_id'] ?? 0);
            $unique[$key] ??= $event;
        }
        $events = collect(array_values($unique));
        $events = $events->map(function ($event) use ($centers, $users, $filters) {
            $account = $users->get($event['actor_id']);
            $event['actor_label'] = $event['actor_name'] ?: ($account?->name ?? ($event['actor_id'] ? 'Tài khoản đã xóa #'.$event['actor_id'] : 'Chưa xác định / hệ thống'));
            if ($account) {
                $event['actor_label'] .= ' ('.$account->username.')';
            }
            $event['center_label'] = $centers->get($event['center_id'])?->code ?? 'Chưa xác định';
            $event['row_key'] = $filters['group_by'] === 'center' ? 'center:'.($event['center_id'] ?? 0) : 'account:'.($event['actor_id'] ?? 0);
            $event['row_label'] = $filters['group_by'] === 'center' ? $event['center_label'] : $event['actor_label'];

            return $event;
        });
        $sections = [];
        foreach (self::KINDS as $kind => [$label, $department]) {
            if (! in_array($department, $scope['departments'], true) || ($scope['mode'] === 'self' && $kind === 'signed')) {
                continue;
            }
            if (($filters['department'] && $department !== $filters['department']) || ($filters['kind'] && $filters['kind'] !== $kind)) {
                continue;
            }
            $section = ['kind' => $kind, 'label' => $label, 'rows' => [], 'days' => array_fill(1, $start->daysInMonth, 0), 'total' => 0];
            foreach ($events->where('kind', $kind)->groupBy('row_key') as $key => $group) {
                $days = array_fill(1, $start->daysInMonth, 0);
                foreach ($group as $event) {
                    $day = (int) substr($event['occurred_at'], 8, 2);
                    $days[$day]++;
                    $section['days'][$day]++;
                }
                $section['rows'][] = ['key' => $key, 'label' => $group->first()['row_label'], 'days' => $days, 'total' => $group->count()];
            }
            if ($filters['group_by'] === 'center') {
                $existing = array_column($section['rows'], 'key');
                foreach ($centers as $center) {
                    if ((! $filters['distribution_center_id'] || (int) $filters['distribution_center_id'] === $center->id)
                        && ! in_array('center:'.$center->id, $existing, true)) {
                        $section['rows'][] = ['key' => 'center:'.$center->id, 'label' => $center->code,
                            'days' => array_fill(1, $start->daysInMonth, 0), 'total' => 0];
                    }
                }
            }
            usort($section['rows'], fn ($a, $b) => strnatcasecmp($a['label'], $b['label']));
            $section['total'] = array_sum($section['days']);
            $sections[] = $section;
        }

        return compact('filters', 'start', 'centers', 'users', 'events', 'sections', 'scope', 'kindOptions');
    }

    private function events(Carbon $start, Carbon $end, array $filters, array $allowedKinds): Collection
    {
        $this->coveredDays = [];
        $this->coveredTurns = [];
        $events = collect();
        $allowedKinds = array_values(array_filter($allowedKinds, fn ($kind) => (! $filters['kind'] || $filters['kind'] === $kind)
            && (! $filters['department'] || self::KINDS[$kind][1] === $filters['department'])));
        $actorId = $filters['user_id'] ? (int) $filters['user_id'] : null;
        $modules = [
            'sales_unit_submit' => ['Yêu cầu cấp phiếu'],
            'request_submit' => ['Yêu cầu cấp phiếu'], 'dvkh_approve' => ['DVKH kiểm tra yêu cầu'],
            'ptn_create' => ['PTN lập phiếu', 'PTN lập phiếu trực tiếp'], 'manager_approve' => ['Phiếu CNCL'],
            'signed' => [], 'supplement_approve' => ['Bổ sung bản ký tươi'], 'supplement_complete' => ['Bổ sung bản ký tươi'],
        ];
        $logNames = array_unique(array_merge([], ...array_map(fn ($kind) => $modules[$kind], $allowedKinds)));
        $grammar = DB::connection()->getQueryGrammar();
        $logs = Activity::query()->where('created_at', '>=', $start)->where('created_at', '<', $end)
            ->whereIn('log_name', $logNames)
            ->when($actorId, fn ($query) => $query->where('causer_type', User::class)->where('causer_id', $actorId))
            ->whereIn('properties->action', ['create', 'update', 'submit', 'approve', 'create_certificate', 'create_certificate_from_request', 'direct_create_certificate', 'approve_for_signing', 'smartca_request', 'smartca_request_resend', 'complete'])
            ->select(['id', 'log_name', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'created_at', 'properties->action as work_action'])
            ->selectRaw("CASE WHEN event = 'work_report' THEN ".$grammar->wrap('properties->work').' ELSE NULL END as work_metadata')
            ->selectRaw('CASE WHEN log_name = ? THEN '.$grammar->wrap('properties->old->status').' ELSE NULL END as old_status', ['Yêu cầu cấp phiếu'])
            ->selectRaw('CASE WHEN log_name = ? THEN '.$grammar->wrap('properties->new->status').' ELSE NULL END as new_status', ['Yêu cầu cấp phiếu'])
            ->selectRaw('CASE WHEN subject_id IS NULL THEN '.$grammar->wrap('properties->object_type').' ELSE NULL END as object_type')
            ->selectRaw('CASE WHEN subject_id IS NULL THEN '.$grammar->wrap('properties->object_id').' ELSE NULL END as object_id')
            ->get()->toBase();
        $certIds = $logs->filter(fn ($log) => in_array($log->subject_type, [QualityCertificate::class, (new QualityCertificate)->getMorphClass()], true) || $log->object_type === 'QualityCertificate')->map(fn ($log) => $log->subject_id ?: $log->object_id);
        $suppIds = $logs->filter(fn ($log) => $log->subject_type === HardCopySupplement::class || $log->object_type === 'HardCopySupplement')->map(fn ($log) => $log->subject_id ?: $log->object_id);
        $certificates = QualityCertificate::withTrashed()->whereIn('id', $certIds)->get(['id', 'certificate_request_id', 'certificate_no'])->keyBy('id');
        $supplements = HardCopySupplement::whereIn('id', $suppIds)->get(['id', 'quality_certificate_id'])->keyBy('id');
        $requestIds = $logs->filter(fn ($log) => $log->subject_type === CertificateRequest::class || $log->object_type === 'CertificateRequest')->map(fn ($log) => $log->subject_id ?: $log->object_id)
            ->merge($certificates->pluck('certificate_request_id'));
        $suppCertificates = QualityCertificate::withTrashed()->whereIn('id', $supplements->pluck('quality_certificate_id'))->get(['id', 'certificate_request_id'])->keyBy('id');
        $requestIds = $requestIds->merge($suppCertificates->pluck('certificate_request_id'));
        $requests = CertificateRequest::withTrashed()->whereIn('id', $requestIds)->get(['id', 'request_no', 'distribution_center_id'])->keyBy('id');

        foreach ($logs as $log) {
            $kind = self::kind($log->log_name, (string) $log->work_action, ['status' => $log->old_status], ['status' => $log->new_status]);
            if (! $kind || ! in_array($kind, $allowedKinds, true)) {
                continue;
            }
            $id = $log->subject_id ?: $log->object_id;
            $metadata = is_array($log->work_metadata) ? $log->work_metadata : json_decode($log->work_metadata ?? 'null', true);
            if (str_starts_with($kind, 'supplement_')) {
                $item = $supplements->get($id);
                $certificate = $suppCertificates->get($item?->quality_certificate_id);
                $request = $requests->get($certificate?->certificate_request_id);
                $no = $item?->number ?? 'Bổ sung #'.$id;
                $key = 'supplement:'.$id;
            } elseif (in_array($kind, ['ptn_create', 'manager_approve'])) {
                $certificate = $certificates->get($id);
                $request = $requests->get($certificate?->certificate_request_id);
                $no = $certificate?->certificate_no ?? 'Phiếu #'.$id;
                $key = 'request:'.($metadata['request_id'] ?? $request?->id ?? 'certificate-'.$id);
            } else {
                $request = $requests->get($id);
                $no = $request?->request_no ?? 'Yêu cầu #'.$id;
                $key = 'request:'.$id;
            }
            if (! $id) {
                continue;
            } // Do not guess object identity from free-form descriptions.
            $actor = $log->causer_type === User::class ? $log->causer_id : null;
            $events->push($this->event($kind, $key, $metadata['actor_id'] ?? $actor, $metadata['center_id'] ?? $request?->distribution_center_id,
                $log->created_at, $no, $request?->request_no, 'Lịch sử #'.$log->id, $metadata['actor_name'] ?? null));
        }

        foreach ($events as $event) {
            $key = $this->coverageKey($event);
            $this->coveredDays[$key] = true;
            $this->coveredTurns[$key.'|'.($event['actor_id'] ?? 0)] = true;
        }

        // Native records fill missing old logs without inventing an approver or the person confirming SmartCA.
        if (in_array('sales_unit_submit', $allowedKinds, true)) {
            $unitSubmissions = CertificateRequest::withTrashed()->where('sales_unit_submitted_at', '>=', $start)->where('sales_unit_submitted_at', '<', $end)
                ->when($actorId, fn ($q) => $q->where('sales_unit_submitted_by', $actorId))
                ->get(['id', 'request_no', 'distribution_center_id', 'sales_unit_submitted_at', 'sales_unit_submitted_by']);
            foreach ($unitSubmissions as $item) $this->fallback($events, $this->event('sales_unit_submit', 'request:'.$item->id,
                $item->sales_unit_submitted_by, $item->distribution_center_id, $item->sales_unit_submitted_at, $item->request_no, $item->request_no, 'Mốc đơn vị gửi Trung tâm'));
        }
        $submitted = in_array('request_submit', $allowedKinds, true)
            ? CertificateRequest::withTrashed()->where('submitted_at', '>=', $start)->where('submitted_at', '<', $end)
                ->when($actorId, fn ($query) => $query->where('submitted_by', $actorId))->get(['id', 'request_no', 'distribution_center_id', 'submitted_at', 'submitted_by']) : collect();
        foreach ($submitted as $item) {
            $this->fallback($events, $this->event('request_submit', 'request:'.$item->id, $item->submitted_by, $item->distribution_center_id, $item->submitted_at, $item->request_no, $item->request_no, 'Mốc gửi yêu cầu'));
        }
        $sentToPtn = in_array('dvkh_approve', $allowedKinds, true) && ! $actorId
            ? CertificateRequest::withTrashed()->where('sent_to_ptn_at', '>=', $start)->where('sent_to_ptn_at', '<', $end)->get(['id', 'request_no', 'distribution_center_id', 'sent_to_ptn_at']) : collect();
        foreach ($sentToPtn as $item) {
            $this->fallback($events, $this->event('dvkh_approve', 'request:'.$item->id, null, $item->distribution_center_id, $item->sent_to_ptn_at, $item->request_no, $item->request_no, 'Mốc chuyển PTN; thiếu người xác nhận'));
        }
        $includeCreated = in_array('ptn_create', $allowedKinds, true);
        $includeSigned = in_array('signed', $allowedKinds, true) && ! $actorId;
        $nativeCertificates = $includeCreated || $includeSigned ? QualityCertificate::withTrashed()
            ->where(function ($query) use ($includeCreated, $includeSigned, $start, $end) {
                if ($includeCreated) {
                    $query->whereBetween('created_at', [$start, $end->copy()->subSecond()]);
                }
                if ($includeSigned) {
                    $query->orWhereBetween('signed_at', [$start, $end->copy()->subSecond()]);
                }
            })
            ->when($actorId, fn ($query) => $query->where('created_by', $actorId))
            ->with(['request' => fn ($q) => $q->withTrashed()->select('id', 'request_no', 'distribution_center_id')])
            ->get(['id', 'certificate_request_id', 'certificate_no', 'created_by', 'created_at', 'signed_at']) : collect();
        foreach ($nativeCertificates as $item) {
            if ($includeCreated && $item->created_at >= $start && $item->created_at < $end) {
                $this->fallback($events, $this->event('ptn_create', 'request:'.$item->certificate_request_id, $item->created_by, $item->request?->distribution_center_id, $item->created_at, $item->certificate_no, $item->request?->request_no, 'Mốc lập phiếu'));
            }
            if ($includeSigned && $item->signed_at && $item->signed_at >= $start && $item->signed_at < $end) {
                $events->push($this->event('signed', 'request:'.$item->certificate_request_id, null, $item->request?->distribution_center_id, $item->signed_at, $item->certificate_no, $item->request?->request_no, 'Ngày ký số; không quy cho tài khoản kiểm tra kết quả'));
            }
        }
        $supplementsToRead = array_intersect(['supplement_approve', 'supplement_complete'], $allowedKinds) ? HardCopySupplement::query()
            ->where(fn ($q) => $q->whereBetween('approved_at', [$start, $end->copy()->subSecond()])->orWhereBetween('completed_at', [$start, $end->copy()->subSecond()]))
            ->when($actorId, fn ($query) => $query->where(fn ($q) => $q->where('approved_by', $actorId)->orWhere('completed_by', $actorId)))
            ->with(['certificate' => fn ($q) => $q->select('id', 'certificate_request_id'), 'certificate.request' => fn ($q) => $q->withTrashed()->select('id', 'request_no', 'distribution_center_id')])
            ->get(['id', 'quality_certificate_id', 'approved_at', 'approved_by', 'completed_at', 'completed_by']) : collect();
        foreach ($supplementsToRead as $item) {
            foreach (['supplement_approve' => ['approved_at', 'approved_by'], 'supplement_complete' => ['completed_at', 'completed_by']] as $kind => [$at, $by]) {
                if (in_array($kind, $allowedKinds, true) && (! $actorId || $actorId === (int) $item->$by) && $item->$at && $start <= $item->$at && $end > $item->$at) {
                    $this->fallback($events, $this->event($kind, 'supplement:'.$item->id, $item->$by, $item->certificate?->request?->distribution_center_id, $item->$at, $item->number, $item->certificate?->request?->request_no, 'Mốc xử lý bổ sung'));
                }
            }
        }

        return $events;
    }

    private function event(string $kind, string $key, ?int $actor, ?int $center, Carbon $at, string $no, ?string $requestNo, string $source, ?string $actorName = null): array
    {
        return ['kind' => $kind, 'department' => self::KINDS[$kind][1], 'object_key' => $key, 'actor_id' => $actor,
            'center_id' => $center, 'occurred_at' => $at->format('Y-m-d H:i:s'), 'object_no' => $no,
            'request_no' => $requestNo, 'source' => $source, 'actor_name' => $actorName];
    }

    private function fallback(Collection $events, array $candidate): void
    {
        $key = $this->coverageKey($candidate);
        $turn = $key.'|'.($candidate['actor_id'] ?? 0);
        $covered = $candidate['actor_id'] === null ? isset($this->coveredDays[$key]) : isset($this->coveredTurns[$turn]);
        if (! $covered) {
            $events->push($candidate);
            $this->coveredDays[$key] = true;
            $this->coveredTurns[$turn] = true;
        }
    }

    private function coverageKey(array $event): string
    {
        return implode('|', [$event['kind'], $event['object_key'], substr($event['occurred_at'], 0, 10)]);
    }
}
