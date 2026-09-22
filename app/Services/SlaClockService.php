<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\SlaConfig;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SlaClockService
{
    public function startedAt(CertificateRequest $request, string $step): ?Carbon
    {
        return match ($step) {
            'DVKH' => $this->dvkhStartedAt($request),
            'PTN' => $this->ptnStartedAt($request),
            default => null,
        };
    }

    public function elapsedMinutes(CertificateRequest $request, string $step): ?int
    {
        $startedAt = $this->startedAt($request, $step);

        return $startedAt ? (int) $startedAt->diffInMinutes(now()) : null;
    }

    public function level(CertificateRequest $request, ?SlaConfig $sla, string $step): ?string
    {
        if (!$sla || !$this->isActiveStep($request, $step)) {
            return null;
        }

        $minutes = $this->elapsedMinutes($request, $step);

        if ($minutes === null) {
            return null;
        }

        if ($minutes >= $sla->limit_minutes) {
            return 'overdue';
        }

        if ($minutes >= $sla->warning_minutes) {
            return 'warning';
        }

        return 'normal';
    }

    public function applyFilter(Builder $query, string $mode, ?SlaConfig $sla, string $step): void
    {
        if (!$sla) {
            return;
        }

        $limitAt = now()->subMinutes((int) $sla->limit_minutes);
        $warningAt = now()->subMinutes((int) $sla->warning_minutes);
        $expression = $this->startExpression($step);

        if ($mode === 'overdue') {
            $query->whereRaw($expression . ' <= ?', [$limitAt]);

            return;
        }

        if ($mode === 'warning') {
            $query->whereRaw($expression . ' <= ?', [$warningAt])
                ->whereRaw($expression . ' > ?', [$limitAt]);

            return;
        }

        if ($mode === 'normal') {
            $query->whereRaw($expression . ' > ?', [$warningAt]);
        }
    }

    public function applyLevelCount(Builder $query, ?SlaConfig $sla, string $step, string $level): int
    {
        if (!$sla) {
            return 0;
        }

        $this->applyFilter($query, $level, $sla, $step);

        return $query->count();
    }

    public function startExpression(string $step): string
    {
        return match ($step) {
            'DVKH' => "COALESCE(CASE WHEN last_returned_to = 'DVKH' THEN last_returned_at END, submitted_at, created_at)",
            'PTN' => "COALESCE(CASE WHEN last_returned_to = 'PTN' THEN last_returned_at END, sent_to_ptn_at, updated_at, created_at)",
            default => 'created_at',
        };
    }

    public function selectStartedAt(string $step, string $alias = 'sla_started_at')
    {
        return DB::raw($this->startExpression($step) . ' as ' . $alias);
    }

    private function dvkhStartedAt(CertificateRequest $request): ?Carbon
    {
        if ($request->status !== 'WAIT_DVKH') {
            return null;
        }

        if ($request->last_returned_to === 'DVKH' && $request->last_returned_at) {
            return $request->last_returned_at;
        }

        return $request->submitted_at ?? $request->created_at;
    }

    private function ptnStartedAt(CertificateRequest $request): ?Carbon
    {
        if (!in_array($request->status, ['WAIT_PTN', 'PTN_PROCESSING'], true)) {
            return null;
        }

        if ($request->last_returned_to === 'PTN' && $request->last_returned_at) {
            return $request->last_returned_at;
        }

        return $request->sent_to_ptn_at ?? $request->updated_at ?? $request->created_at;
    }

    private function isActiveStep(CertificateRequest $request, string $step): bool
    {
        return match ($step) {
            'DVKH' => $request->status === 'WAIT_DVKH',
            'PTN' => in_array($request->status, ['WAIT_PTN', 'PTN_PROCESSING'], true),
            default => false,
        };
    }
}
