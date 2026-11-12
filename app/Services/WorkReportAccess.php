<?php

namespace App\Services;

use App\Models\User;

class WorkReportAccess
{
    public const DEPARTMENTS = ['DVKH' => 'Dịch vụ khách hàng', 'PTN' => 'Phòng thử nghiệm'];

    public function department(User $user): ?string
    {
        if ($user->work_department) {
            return array_key_exists($user->work_department, self::DEPARTMENTS) ? $user->work_department : null;
        }
        // Conservative compatibility for existing accounts; explicit assignment takes precedence.
        $departments = [];
        if ($user->hasRole('DVKH')) {
            $departments[] = 'DVKH';
        }
        if ($user->hasAnyRole(['PTN', 'TruongPTN'])) {
            $departments[] = 'PTN';
        }

        return count($departments) === 1 ? $departments[0] : null;
    }

    public function scope(User $user): array
    {
        $department = $this->department($user);
        if ($user->isPermissionDenied('report.view')) return ['mode' => 'none', 'departments' => []];
        if ($user->isExternalAccount()) {
            return ['mode' => 'none', 'departments' => []];
        }
        if (! $user->can('report.view') && ! $user->hasAnyRole(['DVKH', 'PTN', 'TruongPTN'])) {
            return ['mode' => 'none', 'departments' => []];
        }
        if ($user->hasRole('Admin') || $user->work_report_scope === 'all') {
            return ['mode' => 'all', 'departments' => array_keys(WorkReportService::DEPARTMENTS)];
        }
        $mode = $user->work_report_scope ?: ($user->hasRole('TruongPTN') ? 'team' : 'self');
        if ($mode === 'team') {
            $departments = $user->work_report_scope === 'team' ? (array) $user->work_report_departments : ['PTN'];

            return ['mode' => 'team', 'departments' => array_values(array_intersect(array_keys(self::DEPARTMENTS), $departments))];
        }

        return ['mode' => 'self', 'departments' => $department ? [$department] : []];
    }

    public function canView(User $user): bool
    {
        $scope = $this->scope($user);

        return $scope['mode'] !== 'none' && count($scope['departments']) > 0;
    }

    public function constrain(array $filters, User $user): array
    {
        abort_unless($this->canView($user), 403, 'Tài khoản chưa được cấu hình phạm vi báo cáo công việc.');
        $scope = $this->scope($user);
        if ($filters['department']) {
            abort_unless(in_array($filters['department'], $scope['departments'], true), 403);
        }
        foreach (['kind', 'detail_kind'] as $field) {
            if (empty($filters[$field])) {
                continue;
            }
            $kind = $filters[$field];
            abort_unless(in_array(WorkReportService::KINDS[$kind][1], $scope['departments'], true), 403);
            if ($scope['mode'] === 'self') {
                abort_if($kind === 'signed', 403);
            }
        }
        if ($scope['mode'] === 'self') {
            abort_if($filters['user_id'] && (int) $filters['user_id'] !== $user->id, 403);
            if (str_starts_with($filters['detail_row'] ?? '', 'account:')) {
                abort_unless($filters['detail_row'] === 'account:'.$user->id, 403);
            }
            $filters['user_id'] = $user->id;
            $filters['department'] = $scope['departments'][0];
        } elseif (count($scope['departments']) === 1) {
            $filters['department'] = $scope['departments'][0];
        }

        return $filters;
    }
}
