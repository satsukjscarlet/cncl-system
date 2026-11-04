<?php

namespace App\Http\Controllers;

use App\Exports\WorkReportExport;
use App\Services\WorkReportService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class WorkReportController extends Controller
{
    public function index(Request $request, WorkReportService $service)
    {
        Gate::authorize('work-report.view');
        $report = $service->report($service->filters($request), $request->user());
        $details = $this->details($report);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $report['details'] = new LengthAwarePaginator($details->forPage($page, 30)->values(), $details->count(), 30, $page,
            ['path' => $request->url(), 'query' => $request->query()]);

        return view('reports.work', $report);
    }

    public function export(Request $request, WorkReportService $service)
    {
        Gate::authorize('work-report.view');
        Gate::authorize('report.export');
        $report = $service->report($service->filters($request), $request->user());

        // Export the entire filtered month, not just a drilled-down cell or the visible detail page.
        return Excel::download(new WorkReportExport($report), 'bao-cao-cong-viec-'.$report['start']->format('Y-m').'.xlsx');
    }

    private function details(array $report)
    {
        $filters = $report['filters'];

        return $report['events']->filter(fn ($event) => (empty($filters['detail_day']) || (int) substr($event['occurred_at'], 8, 2) === (int) $filters['detail_day'])
            && (empty($filters['detail_kind']) || $event['kind'] === $filters['detail_kind'])
            && (empty($filters['detail_row']) || $event['row_key'] === $filters['detail_row']))
            ->sortByDesc('occurred_at')->values();
    }
}
