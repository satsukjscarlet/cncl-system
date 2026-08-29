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

        $certificateCount = (clone $query)
            ->whereHas('qualityCertificate', function ($certificateQuery) {
                $certificateQuery->where('status', 'ISSUED');
            })
            ->count();

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();

        [$warningCount, $overdueCount] = $this->slaCounts($query, $slaDvkh, $slaPtn);

        $reportYear = $this->reportYear($request);
        $statisticsCenterId = !$canViewAllCenters
            ? $user->distribution_center_id
            : ($request->filled('distribution_center_id') ? (int) $request->distribution_center_id : null);
        [$monthlyCertificateStats, $monthlyCertificateTotals, $monthlyCertificateGrandTotal] = $this->monthlyCertificateStats(
            $reportYear,
            $statisticsCenterId,
            $canViewAllCenters
        );

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();
        $statusOptions = $this->requestStatusOptions();

        return view('reports.summary', compact(
            'requests',
            'centers',
            'statusOptions',
            'canViewAllCenters',
            'totalRequests',
            'completedRequests',
            'cancelledRequests',
            'certificateCount',
            'warningCount',
            'overdueCount',
            'reportYear',
            'monthlyCertificateStats',
            'monthlyCertificateTotals',
            'monthlyCertificateGrandTotal'
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

    private function requestStatusOptions(): array
    {
        return [
            'DRAFT' => 'Nháp',
            'WAIT_DVKH' => 'Chờ DVKH kiểm tra',
            'WAIT_PTN' => 'Chờ PTN lập phiếu',
            'PTN_PROCESSING' => 'Đã lập phiếu - Chờ Trưởng PTN ký',
            'SIGNED' => 'Đã ký số',
            'COMPLETED' => 'Hoàn tất',
            'CANCELLED' => 'Đã trả lại / hủy',
        ];
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

    private function reportYear(Request $request): int
    {
        $year = (int) $request->input('report_year', now()->year);

        if ($year < 2000 || $year > 2100) {
            return (int) now()->year;
        }

        return $year;
    }

    private function monthlyCertificateStats(int $year, ?int $distributionCenterId, bool $canViewAllCenters): array
    {
        $centersQuery = DistributionCenter::where('is_active', true)->orderBy('name');

        if ($distributionCenterId) {
            $centersQuery->where('id', $distributionCenterId);
        }

        $centers = $centersQuery->get();
        $rowsByCenter = [];

        foreach ($centers as $center) {
            $rowsByCenter[$center->id] = [
                'center' => $center,
                'months' => array_fill(1, 12, 0),
                'total' => 0,
            ];
        }

        $monthExpression = $this->monthExpression('quality_certificates.signed_at');

        $query = QualityCertificate::query()
            ->join('certificate_requests', 'certificate_requests.id', '=', 'quality_certificates.certificate_request_id')
            ->where('quality_certificates.status', 'ISSUED')
            ->whereNotNull('quality_certificates.signed_at')
            ->whereYear('quality_certificates.signed_at', $year)
            ->selectRaw('certificate_requests.distribution_center_id as distribution_center_id')
            ->selectRaw($monthExpression . ' as report_month')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('certificate_requests.distribution_center_id')
            ->groupByRaw($monthExpression);

        if ($distributionCenterId) {
            $query->where('certificate_requests.distribution_center_id', $distributionCenterId);
        } elseif (!$canViewAllCenters) {
            $query->whereRaw('1 = 0');
        }

        $query->get()->each(function ($item) use (&$rowsByCenter) {
            $centerId = (int) $item->distribution_center_id;
            $month = (int) $item->report_month;

            if (!isset($rowsByCenter[$centerId]) || $month < 1 || $month > 12) {
                return;
            }

            $rowsByCenter[$centerId]['months'][$month] = (int) $item->total;
            $rowsByCenter[$centerId]['total'] += (int) $item->total;
        });

        $totals = array_fill(1, 12, 0);
        $grandTotal = 0;

        foreach ($rowsByCenter as $row) {
            foreach ($row['months'] as $month => $count) {
                $totals[$month] += $count;
                $grandTotal += $count;
            }
        }

        return [array_values($rowsByCenter), $totals, $grandTotal];
    }

    private function monthExpression(string $column): string
    {
        if (config('database.default') === 'sqlite') {
            return "CAST(strftime('%m', {$column}) AS INTEGER)";
        }

        return "MONTH({$column})";
    }
}
