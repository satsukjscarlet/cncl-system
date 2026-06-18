<?php

namespace App\Http\Controllers;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Models\SlaConfig;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $requestQuery = CertificateRequest::query();

        if ($user->hasRole('TrungTam')) {
            $requestQuery->where('distribution_center_id', $user->distribution_center_id);
        }

        $totalRequests = (clone $requestQuery)->count();
        $waitDvkh = (clone $requestQuery)->where('status', 'WAIT_DVKH')->count();
        $waitPtn = (clone $requestQuery)->where('status', 'WAIT_PTN')->count();
        $ptnProcessing = (clone $requestQuery)->where('status', 'PTN_PROCESSING')->count();
        $completed = (clone $requestQuery)->where('status', 'COMPLETED')->count();
        $cancelled = (clone $requestQuery)->where('status', 'CANCELLED')->count();

        $certificateQuery = QualityCertificate::whereHas('request', function ($q) use ($user) {
            if ($user->hasRole('TrungTam')) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            }
        });

        $totalCertificates = (clone $certificateQuery)->count();
        $signedCertificates = (clone $certificateQuery)->whereNotNull('signed_at')->count();
        $unsignedCertificates = (clone $certificateQuery)->whereNull('signed_at')->count();

        $dvkhWaiting = (clone $requestQuery)
            ->with(['distributionCenter', 'customer', 'creator'])
            ->where('status', 'WAIT_DVKH')
            ->oldest()
            ->take(10)
            ->get();

        $ptnWaiting = (clone $requestQuery)
            ->with(['distributionCenter', 'customer', 'creator'])
            ->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING'])
            ->oldest()
            ->take(10)
            ->get();

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();
        $slaTotal = SlaConfig::where('code', 'SLA_TOTAL')->where('is_active', true)->first();

        $slaAlerts = collect();

        $waitingRequests = (clone $requestQuery)
            ->with(['distributionCenter', 'customer'])
            ->whereIn('status', ['WAIT_DVKH', 'WAIT_PTN', 'PTN_PROCESSING'])
            ->oldest()
            ->get();

        foreach ($waitingRequests as $item) {
            $minutes = Carbon::parse($item->created_at)->diffInMinutes(now());

            $sla = null;
            $stepName = null;

            if ($item->status === 'WAIT_DVKH') {
                $sla = $slaDvkh;
                $stepName = 'DVKH kiểm tra';
            }

            if (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING'])) {
                $sla = $slaPtn;
                $stepName = 'PTN xử lý';
            }

            if (!$sla) {
                continue;
            }

            if ($minutes >= $sla->limit_minutes) {
                $item->sla_level = 'danger';
            } elseif ($minutes >= $sla->warning_minutes) {
                $item->sla_level = 'warning';
            } else {
                continue;
            }

            $item->sla_minutes = $minutes;
            $item->sla_step_name = $stepName;
            $item->sla_limit_minutes = $sla->limit_minutes;
            $item->sla_warning_minutes = $sla->warning_minutes;

            $slaAlerts->push($item);
        }

        $monthlyCertificates = (clone $certificateQuery)
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', now()->year)
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month')
            ->toArray();

        $chartLabels = [];
        $chartValues = [];

        for ($month = 1; $month <= 12; $month++) {
            $chartLabels[] = 'T' . $month;
            $chartValues[] = $monthlyCertificates[$month] ?? 0;
        }

        return view('dashboard.index', compact(
            'totalRequests',
            'waitDvkh',
            'waitPtn',
            'ptnProcessing',
            'completed',
            'cancelled',
            'totalCertificates',
            'signedCertificates',
            'unsignedCertificates',
            'dvkhWaiting',
            'ptnWaiting',
            'slaAlerts',
            'chartLabels',
            'chartValues',
            'slaTotal'
        ));
    }
}