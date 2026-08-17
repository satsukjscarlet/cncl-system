<?php

namespace App\Http\Controllers;

use App\Exports\CertificateSummaryExport;
use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\QualityCertificate;
use App\Models\SlaConfig;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canViewAllCenters = $this->canViewAllCenters($user);

        if (!$canViewAllCenters && !$user->hasRole('TrungTam')) {
            abort(403, 'Tai khoan nay khong duoc xem bao cao tong hop.');
        }

        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'qualityCertificate',
        ]);

        if (!$canViewAllCenters) {
            $query->where('distribution_center_id', $user->distribution_center_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('distribution_center_id') && $canViewAllCenters) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        $requests = (clone $query)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalRequests = (clone $query)->count();
        $completedRequests = (clone $query)->where('status', 'COMPLETED')->count();
        $cancelledRequests = (clone $query)->where('status', 'CANCELLED')->count();

        $certificateQuery = QualityCertificate::whereHas('request', function ($q) use ($user) {
            if (!$this->canViewAllCenters($user)) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            }
        });

        $certificateCount = $certificateQuery->count();

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();

        [$warningCount, $overdueCount] = $this->slaCounts($query, $slaDvkh, $slaPtn);

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reports.summary', compact(
            'requests',
            'centers',
            'canViewAllCenters',
            'totalRequests',
            'completedRequests',
            'cancelledRequests',
            'certificateCount',
            'warningCount',
            'overdueCount'
        ));
    }

    public function exportSummary(Request $request): BinaryFileResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canViewAllCenters = $this->canViewAllCenters($user);

        if (!$canViewAllCenters && !$user->hasRole('TrungTam')) {
            abort(403, 'Tai khoan nay khong duoc xuat bao cao tong hop.');
        }

        $distributionCenterId = !$canViewAllCenters
            ? $user->distribution_center_id
            : ($request->distribution_center_id ? (int) $request->distribution_center_id : null);

        return Excel::download(
            new CertificateSummaryExport(
                $request->date_from,
                $request->date_to,
                $request->status,
                $distributionCenterId
            ),
            'bao_cao_tong_hop_cncl.xlsx'
        );
    }

    private function canViewAllCenters($user): bool
    {
        return $user->hasAnyRole(['Admin', 'LanhDao']);
    }

    private function slaCounts($baseQuery, ?SlaConfig $slaDvkh, ?SlaConfig $slaPtn): array
    {
        $warningCount = 0;
        $overdueCount = 0;

        foreach ([
            [['WAIT_DVKH'], $slaDvkh],
            [['WAIT_PTN', 'PTN_PROCESSING'], $slaPtn],
        ] as [$statuses, $sla]) {
            if (!$sla) {
                continue;
            }

            $limitAt = now()->subMinutes((int) $sla->limit_minutes);
            $warningAt = now()->subMinutes((int) $sla->warning_minutes);

            $overdueCount += (clone $baseQuery)
                ->whereIn('status', $statuses)
                ->where('created_at', '<=', $limitAt)
                ->count();

            $warningCount += (clone $baseQuery)
                ->whereIn('status', $statuses)
                ->where('created_at', '<=', $warningAt)
                ->where('created_at', '>', $limitAt)
                ->count();
        }

        return [$warningCount, $overdueCount];
    }
}
