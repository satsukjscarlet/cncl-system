<?php

namespace App\Http\Controllers;

use App\Exports\CertificateSummaryExport;
use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\QualityCertificate;
use App\Models\SlaConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
        ]);

        if ($user->hasRole('TrungTam')) {
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

        if ($request->filled('distribution_center_id') && !$user->hasRole('TrungTam')) {
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
            if ($user->hasRole('TrungTam')) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            }
        });

        $certificateCount = $certificateQuery->count();

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();

        $warningCount = 0;
        $overdueCount = 0;

        foreach ((clone $query)->whereIn('status', ['WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING'])->get() as $item) {
            $minutes = Carbon::parse($item->created_at)->diffInMinutes(now());

            $sla = null;

            if ($item->status === 'WAIT_DVKH') {
                $sla = $slaDvkh;
            }

            if (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING'])) {
                $sla = $slaPtn;
            }

            if (!$sla) {
                continue;
            }

            if ($minutes >= $sla->limit_minutes) {
                $overdueCount++;
            } elseif ($minutes >= $sla->warning_minutes) {
                $warningCount++;
            }
        }

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('reports.summary', compact(
            'requests',
            'centers',
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

        $distributionCenterId = $user->hasRole('TrungTam')
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
}