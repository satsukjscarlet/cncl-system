<?php

namespace App\Http\Controllers;

use App\Exports\CertificateSummaryExport;
use App\Exports\HardCopyReportExport;
use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\PrintLog;
use App\Models\QualityCertificate;
use App\Models\SalesUnit;
use App\Models\SlaConfig;
use App\Services\HardCopyBatchCertificatePdfService;
use App\Services\HardCopyCertificatePdfService;
use App\Services\SlaClockService;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(private readonly SlaClockService $slaClock)
    {
    }

    public function summary(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canViewAllCenters = $this->canViewAllCenters($user);

        if (!$canViewAllCenters && !$user->hasRole('TrungTam')) {
            abort(403, 'Tài khoản này không được xem báo cáo tổng hợp.');
        }

        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'qualityCertificate',
            'qualityCertificates',
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

        $requestStatus = $request->input('request_status', $request->input('status'));

        if (filled($requestStatus)) {
            $query->where('status', $requestStatus);
        }

        if ($request->filled('distribution_center_id') && $canViewAllCenters) {
            $query->where('distribution_center_id', $request->distribution_center_id);
        }

        $certificateStatusBaseQuery = clone $query;

        if ($request->filled('certificate_status')) {
            $this->applyCertificateStatusFilter($query, $request->certificate_status);
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

        $certificateStatusCounts = $this->certificateStatusCounts($certificateStatusBaseQuery);
        $revokedCertificateCount = $certificateStatusCounts[QualityCertificate::STATUS_REVOKED] ?? 0;

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
        $certificateStatusOptions = $this->certificateStatusOptions();

        return view('reports.summary', compact(
            'requests',
            'centers',
            'statusOptions',
            'certificateStatusOptions',
            'canViewAllCenters',
            'totalRequests',
            'completedRequests',
            'cancelledRequests',
            'certificateCount',
            'certificateStatusCounts',
            'revokedCertificateCount',
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
            abort(403, 'Tài khoản này không được xuất báo cáo tổng hợp.');
        }

        $distributionCenterId = !$canViewAllCenters
            ? $user->distribution_center_id
            : ($request->distribution_center_id ? (int) $request->distribution_center_id : null);
        $requestStatus = $request->input('request_status', $request->input('status'));

        return Excel::download(
            new CertificateSummaryExport(
                $request->date_from,
                $request->date_to,
                $requestStatus,
                $distributionCenterId,
                $request->certificate_status
            ),
            'bao_cao_tong_hop_cncl.xlsx'
        );
    }

    public function exportHardCopy(Request $request): BinaryFileResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canViewAllCenters = $this->canViewAllCenters($user);

        if (!$canViewAllCenters && !$user->hasRole('TrungTam')) {
            abort(403, 'Tài khoản này không được xuất báo cáo ký tươi.');
        }

        $query = $this->hardCopyCertificateQuery($request, $canViewAllCenters, $user);
        $certificates = (clone $query)->latest('signed_at')->get();
        $this->ensureHardCopyPageCounts($certificates);
        $centerStats = $this->hardCopyCenterStats(
            $certificates,
            $this->normalPrintSummaryByCenter($certificates->pluck('id')->all())
        );
        $salesUnitStats = $this->hardCopySalesUnitStats($certificates);
        $monthlyStats = $this->hardCopyMonthlyStats($certificates);
        $filterSummary = $this->hardCopyFilterSummary($request, $canViewAllCenters);

        return Excel::download(
            new HardCopyReportExport($certificates, $centerStats, $salesUnitStats, $monthlyStats, $filterSummary),
            'bao_cao_ky_tuoi.xlsx'
        );
    }

    public function hardCopy(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $canViewAllCenters = $this->canViewAllCenters($user);

        if (!$canViewAllCenters && !$user->hasRole('TrungTam')) {
            abort(403, 'Tài khoản này không được xem báo cáo ký tươi.');
        }

        $query = $this->hardCopyCertificateQuery($request, $canViewAllCenters, $user);
        $availableDateRange = $this->hardCopyDateRange($request, $canViewAllCenters, $user);

        $allCertificates = (clone $query)->get();
        $this->ensureHardCopyPageCounts($allCertificates);

        $summary = $this->hardCopySummary($allCertificates);
        $printSummary = $this->normalPrintSummary($allCertificates->pluck('id')->all());
        $centerStats = $this->hardCopyCenterStats(
            $allCertificates,
            $this->normalPrintSummaryByCenter($allCertificates->pluck('id')->all())
        );
        $salesUnitStats = $this->hardCopySalesUnitStats($allCertificates);
        $monthlyStats = $this->hardCopyMonthlyStats($allCertificates);
        $filterSummary = $this->hardCopyFilterSummary($request, $canViewAllCenters);

        $certificates = (clone $query)
            ->latest('signed_at')
            ->paginate(20)
            ->withQueryString();
        $this->ensureHardCopyPageCounts($certificates->getCollection());

        $centers = DistributionCenter::where('is_active', true)
            ->orderBy('name')
            ->get();
        $salesUnits = $this->hardCopySalesUnits($request, $canViewAllCenters, $user);

        return view('reports.hard_copy', compact(
            'certificates',
            'centers',
            'salesUnits',
            'canViewAllCenters',
            'summary',
            'printSummary',
            'centerStats',
            'salesUnitStats',
            'monthlyStats',
            'filterSummary',
            'availableDateRange'
        ));
    }

    private function canViewAllCenters($user): bool
    {
        if ($user->hasRole('TrungTam')) {
            return false;
        }

        return $user->can('report.view') || $user->can('report.export');
    }

    private function hardCopyCertificateQuery(
        Request $request,
        bool $canViewAllCenters,
        $user,
        bool $applyDateFilters = true
    )
    {
        $query = QualityCertificate::with([
            'request.distributionCenter',
            'request.customer',
            'request.salesUnit',
            'details.product',
            'printLogs.user',
        ])
            ->where('status', QualityCertificate::STATUS_ISSUED)
            ->whereNotNull('signed_at')
            ->whereHas('request', function ($q) {
                $q->where('require_hard_copy', true)
                    ->where('hard_copy_quantity', '>', 0);
            });

        if (!$canViewAllCenters) {
            $query->whereHas('request', function ($q) use ($user) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            });
        }

        if ($applyDateFilters && $request->filled('date_from')) {
            $query->whereDate('signed_at', '>=', $request->date_from);
        }

        if ($applyDateFilters && $request->filled('date_to')) {
            $query->whereDate('signed_at', '<=', $request->date_to);
        }

        if ($request->filled('distribution_center_id') && $canViewAllCenters) {
            $query->whereHas('request', function ($q) use ($request) {
                $q->where('distribution_center_id', $request->distribution_center_id);
            });
        }

        if ($request->filled('sales_unit_id')) {
            $query->whereHas('request', function ($q) use ($request) {
                $q->where('sales_unit_id', $request->sales_unit_id);
            });
        }

        if (in_array($request->input('print_template'), ['single', 'batch'], true)) {
            $query->whereHas('printLogs', function ($q) use ($request) {
                $q->where('print_mode', 'normal')
                    ->where('print_template', $request->input('print_template'));
            });
        }

        if (in_array($request->input('print_status'), ['not_printed', 'printed_single', 'printed_batch', 'printed_both'], true)) {
            $printStatus = $request->input('print_status');

            if ($printStatus === 'not_printed') {
                $query->whereDoesntHave('printLogs', fn ($q) => $q->where('print_mode', 'normal'));
            }

            if ($printStatus === 'printed_single') {
                $query->whereHas('printLogs', fn ($q) => $q->where('print_mode', 'normal')->where('print_template', 'single'))
                    ->whereDoesntHave('printLogs', fn ($q) => $q->where('print_mode', 'normal')->where('print_template', 'batch'));
            }

            if ($printStatus === 'printed_batch') {
                $query->whereHas('printLogs', fn ($q) => $q->where('print_mode', 'normal')->where('print_template', 'batch'))
                    ->whereDoesntHave('printLogs', fn ($q) => $q->where('print_mode', 'normal')->where('print_template', 'single'));
            }

            if ($printStatus === 'printed_both') {
                $query->whereHas('printLogs', fn ($q) => $q->where('print_mode', 'normal')->where('print_template', 'single'))
                    ->whereHas('printLogs', fn ($q) => $q->where('print_mode', 'normal')->where('print_template', 'batch'));
            }
        }

        if ($request->filled('keyword')) {
            $keyword = trim((string) $request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('certificate_no', 'like', '%' . $keyword . '%')
                    ->orWhereHas('request', function ($requestQuery) use ($keyword) {
                        $requestQuery
                            ->where('request_no', 'like', '%' . $keyword . '%')
                            ->orWhere('invoice_no', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('request.customer', function ($customerQuery) use ($keyword) {
                        $customerQuery
                            ->where('customer_name', 'like', '%' . $keyword . '%')
                            ->orWhere('project_name', 'like', '%' . $keyword . '%')
                            ->orWhere('project_address', 'like', '%' . $keyword . '%');
                    })
                    ->orWhereHas('request.salesUnit', function ($salesUnitQuery) use ($keyword) {
                        $salesUnitQuery
                            ->where('code', 'like', '%' . $keyword . '%')
                            ->orWhere('name', 'like', '%' . $keyword . '%');
                    });
            });
        }

        return $query;
    }

    private function hardCopyDateRange(Request $request, bool $canViewAllCenters, $user): array
    {
        $query = $this->hardCopyCertificateQuery($request, $canViewAllCenters, $user, false);

        return [
            'from' => (clone $query)->min('signed_at'),
            'to' => (clone $query)->max('signed_at'),
        ];
    }

    private function hardCopySalesUnits(Request $request, bool $canViewAllCenters, $user)
    {
        return SalesUnit::query()
            ->where('is_active', true)
            ->when(!$canViewAllCenters, function ($q) use ($user) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            })
            ->when($canViewAllCenters && $request->filled('distribution_center_id'), function ($q) use ($request) {
                $q->where('distribution_center_id', $request->distribution_center_id);
            })
            ->with('distributionCenter')
            ->orderBy('name')
            ->get();
    }

    private function hardCopyFilterSummary(Request $request, bool $canViewAllCenters): array
    {
        $centerText = 'Tất cả trung tâm';
        if ($canViewAllCenters && $request->filled('distribution_center_id')) {
            $center = DistributionCenter::find($request->distribution_center_id);
            $centerText = $center ? trim($center->code . ' - ' . $center->name) : 'Trung tâm không tồn tại';
        }

        $salesUnitText = 'Tất cả ĐVBH';
        if ($request->filled('sales_unit_id')) {
            $salesUnit = SalesUnit::with('distributionCenter')->find($request->sales_unit_id);
            $salesUnitText = $salesUnit
                ? trim($salesUnit->code . ' - ' . $salesUnit->name . ($salesUnit->distributionCenter ? ' (' . $salesUnit->distributionCenter->code . ')' : ''))
                : 'ĐVBH không tồn tại';
        }

        return [
            [
                'label' => 'Từ ngày ký số',
                'value' => $request->filled('date_from')
                    ? Carbon::parse($request->date_from)->format('d/m/Y')
                    : 'Không giới hạn',
            ],
            [
                'label' => 'Đến ngày ký số',
                'value' => $request->filled('date_to')
                    ? Carbon::parse($request->date_to)->format('d/m/Y')
                    : 'Không giới hạn',
            ],
            ['label' => 'Trung tâm', 'value' => $centerText],
            ['label' => 'Đơn vị bán hàng', 'value' => $salesUnitText],
            [
                'label' => 'Kiểu in',
                'value' => match ($request->input('print_template')) {
                    'single' => 'In đơn',
                    'batch' => 'In bộ',
                    default => 'Tất cả kiểu in',
                },
            ],
            [
                'label' => 'Ghi nhận bấm in',
                'value' => match ($request->input('print_status')) {
                    'not_printed' => 'Chưa ghi nhận bấm in',
                    'printed_single' => 'Đã bấm In đơn',
                    'printed_batch' => 'Đã bấm In bộ',
                    'printed_both' => 'Đã bấm cả hai',
                    default => 'Tất cả lượt bấm in',
                },
            ],
            [
                'label' => 'Từ khóa',
                'value' => filled($request->keyword) ? (string) $request->keyword : 'Không nhập',
            ],
        ];
    }

    private function ensureHardCopyPageCounts($certificates): void
    {
        $singleService = app(HardCopyCertificatePdfService::class);
        $batchService = app(HardCopyBatchCertificatePdfService::class);

        foreach ($certificates as $certificate) {
            if ($certificate->hard_copy_single_page_count && $certificate->hard_copy_batch_page_count) {
                continue;
            }

            $certificate->loadMissing([
                'request.customer',
                'details.product',
            ]);

            $singlePageCount = $certificate->hard_copy_single_page_count
                ?: $singleService->pageCount($certificate);
            $batchPageCount = $certificate->hard_copy_batch_page_count
                ?: $batchService->pageCount($certificate);

            $certificate->forceFill([
                'hard_copy_single_page_count' => $singlePageCount,
                'hard_copy_batch_page_count' => $batchPageCount,
            ])->save();
        }
    }

    private function hardCopySummary($certificates): array
    {
        $requestIds = [];
        $certificateCount = 0;
        $copyCount = 0;
        $singlePageCount = 0;
        $singleSheetCount = 0;
        $batchPageCount = 0;
        $batchSheetCount = 0;

        foreach ($certificates as $certificate) {
            $request = $certificate->request;
            $quantity = max(0, (int) ($request?->hard_copy_quantity ?? 0));
            $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
            $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);

            if (!$request || !$quantity) {
                continue;
            }

            $requestIds[$request->id] = true;
            $certificateCount++;
            $copyCount += $quantity;
            $singlePageCount += $singlePages;
            $singleSheetCount += $quantity * $singlePages;
            $batchPageCount += $batchPages;
            $batchSheetCount += $quantity * $batchPages;
        }

        return [
            'request_count' => count($requestIds),
            'certificate_count' => $certificateCount,
            'copy_count' => $copyCount,
            'single_page_count' => $singlePageCount,
            'single_sheet_count' => $singleSheetCount,
            'batch_page_count' => $batchPageCount,
            'batch_sheet_count' => $batchSheetCount,
        ];
    }

    private function normalPrintSummary(array $certificateIds): array
    {
        if ($certificateIds === []) {
            return [
                'single_print_count' => 0,
                'batch_print_count' => 0,
            ];
        }

        $counts = PrintLog::query()
            ->whereIn('quality_certificate_id', $certificateIds)
            ->where('print_mode', 'normal')
            ->selectRaw('print_template, COUNT(*) as total')
            ->groupBy('print_template')
            ->pluck('total', 'print_template');

        return [
            'single_print_count' => (int) ($counts['single'] ?? 0),
            'batch_print_count' => (int) ($counts['batch'] ?? 0),
        ];
    }

    private function hardCopyCenterStats($certificates, array $printCountsByCenter): array
    {
        $rows = [];

        foreach ($certificates as $certificate) {
            $request = $certificate->request;
            $center = $request?->distributionCenter;
            $centerId = (int) ($center?->id ?? 0);
            $quantity = max(0, (int) ($request?->hard_copy_quantity ?? 0));

            if (!$request || !$centerId || !$quantity) {
                continue;
            }

            $rows[$centerId] ??= [
                'center' => $center,
                'request_ids' => [],
                'certificate_count' => 0,
                'copy_count' => 0,
                'single_page_count' => 0,
                'single_sheet_count' => 0,
                'batch_page_count' => 0,
                'batch_sheet_count' => 0,
                'single_print_count' => $printCountsByCenter[$centerId]['single_print_count'] ?? 0,
                'batch_print_count' => $printCountsByCenter[$centerId]['batch_print_count'] ?? 0,
            ];

            $rows[$centerId]['request_ids'][$request->id] = true;
            $rows[$centerId]['certificate_count']++;
            $rows[$centerId]['copy_count'] += $quantity;
            $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
            $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
            $rows[$centerId]['single_page_count'] += $singlePages;
            $rows[$centerId]['single_sheet_count'] += $quantity * $singlePages;
            $rows[$centerId]['batch_page_count'] += $batchPages;
            $rows[$centerId]['batch_sheet_count'] += $quantity * $batchPages;
        }

        foreach ($rows as &$row) {
            $row['request_count'] = count($row['request_ids']);
            unset($row['request_ids']);
        }
        unset($row);

        uasort($rows, fn (array $a, array $b): int => strcmp(
            (string) ($a['center']->name ?? ''),
            (string) ($b['center']->name ?? '')
        ));

        return array_values($rows);
    }

    private function hardCopySalesUnitStats($certificates): array
    {
        $rows = [];

        foreach ($certificates as $certificate) {
            $request = $certificate->request;
            $salesUnit = $request?->salesUnit;
            $center = $request?->distributionCenter;
            $key = $salesUnit?->id ? 'sales_unit_' . $salesUnit->id : 'none_' . ($center?->id ?? 0);
            $quantity = max(0, (int) ($request?->hard_copy_quantity ?? 0));

            if (!$request || !$quantity) {
                continue;
            }

            $rows[$key] ??= [
                'center' => $center,
                'sales_unit' => $salesUnit,
                'request_ids' => [],
                'certificate_count' => 0,
                'copy_count' => 0,
                'single_page_count' => 0,
                'single_sheet_count' => 0,
                'batch_page_count' => 0,
                'batch_sheet_count' => 0,
            ];

            $rows[$key]['request_ids'][$request->id] = true;
            $rows[$key]['certificate_count']++;
            $rows[$key]['copy_count'] += $quantity;
            $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
            $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
            $rows[$key]['single_page_count'] += $singlePages;
            $rows[$key]['single_sheet_count'] += $quantity * $singlePages;
            $rows[$key]['batch_page_count'] += $batchPages;
            $rows[$key]['batch_sheet_count'] += $quantity * $batchPages;
        }

        foreach ($rows as &$row) {
            $row['request_count'] = count($row['request_ids']);
            unset($row['request_ids']);
        }
        unset($row);

        uasort($rows, function (array $a, array $b): int {
            $centerCompare = strcmp(
                (string) ($a['center']->name ?? ''),
                (string) ($b['center']->name ?? '')
            );

            if ($centerCompare !== 0) {
                return $centerCompare;
            }

            return strcmp(
                (string) ($a['sales_unit']->name ?? ''),
                (string) ($b['sales_unit']->name ?? '')
            );
        });

        return array_values($rows);
    }

    private function hardCopyMonthlyStats($certificates): array
    {
        $rows = [];

        foreach ($certificates as $certificate) {
            $request = $certificate->request;
            $quantity = max(0, (int) ($request?->hard_copy_quantity ?? 0));

            if (!$request || !$quantity || !$certificate->signed_at) {
                continue;
            }

            $monthKey = $certificate->signed_at->format('Y-m');
            $rows[$monthKey] ??= [
                'month_key' => $monthKey,
                'month_label' => $certificate->signed_at->format('m/Y'),
                'request_ids' => [],
                'certificate_count' => 0,
                'copy_count' => 0,
                'single_page_count' => 0,
                'single_sheet_count' => 0,
                'batch_page_count' => 0,
                'batch_sheet_count' => 0,
            ];

            $rows[$monthKey]['request_ids'][$request->id] = true;
            $rows[$monthKey]['certificate_count']++;
            $rows[$monthKey]['copy_count'] += $quantity;
            $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
            $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
            $rows[$monthKey]['single_page_count'] += $singlePages;
            $rows[$monthKey]['single_sheet_count'] += $quantity * $singlePages;
            $rows[$monthKey]['batch_page_count'] += $batchPages;
            $rows[$monthKey]['batch_sheet_count'] += $quantity * $batchPages;
        }

        foreach ($rows as &$row) {
            $row['request_count'] = count($row['request_ids']);
            unset($row['request_ids']);
        }
        unset($row);

        ksort($rows);

        return array_values($rows);
    }

    private function normalPrintSummaryByCenter(array $certificateIds): array
    {
        if ($certificateIds === []) {
            return [];
        }

        $rows = PrintLog::query()
            ->join('quality_certificates', 'quality_certificates.id', '=', 'print_logs.quality_certificate_id')
            ->join('certificate_requests', 'certificate_requests.id', '=', 'quality_certificates.certificate_request_id')
            ->whereIn('print_logs.quality_certificate_id', $certificateIds)
            ->where('print_logs.print_mode', 'normal')
            ->selectRaw('certificate_requests.distribution_center_id as center_id')
            ->selectRaw('print_logs.print_template as print_template')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('certificate_requests.distribution_center_id')
            ->groupBy('print_logs.print_template')
            ->get();

        $counts = [];

        foreach ($rows as $row) {
            $centerId = (int) $row->center_id;
            $counts[$centerId] ??= [
                'single_print_count' => 0,
                'batch_print_count' => 0,
            ];

            if ($row->print_template === 'single') {
                $counts[$centerId]['single_print_count'] = (int) $row->total;
            }

            if ($row->print_template === 'batch') {
                $counts[$centerId]['batch_print_count'] = (int) $row->total;
            }
        }

        return $counts;
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

    private function certificateStatusOptions(): array
    {
        return [
            'NO_CERTIFICATE' => 'Chưa lập phiếu',
            'WAIT_PTN_MANAGER_APPROVAL' => 'Chờ Trưởng PTN duyệt',
            'READY_TO_SIGN' => 'Chờ gửi ký số',
            'SIGN_PENDING' => 'Đang chờ ký số',
            'SIGN_EXPIRED' => 'Quá hạn ký số',
            'ISSUED' => 'Đã ký / phát hành',
            'REJECTED' => 'Trưởng PTN trả lại',
            'REVOKED' => 'Đã hủy / thu hồi',
        ];
    }

    private function certificateStatusCounts($baseQuery): array
    {
        $counts = [];

        foreach (array_keys($this->certificateStatusOptions()) as $status) {
            $query = clone $baseQuery;
            $this->applyCertificateStatusFilter($query, $status);
            $counts[$status] = $query->count();
        }

        return $counts;
    }

    private function applyCertificateStatusFilter($query, string $status): void
    {
        if ($status === 'NO_CERTIFICATE') {
            $query->whereDoesntHave('qualityCertificates');

            return;
        }

        $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

        if ($status === 'WAIT_PTN_MANAGER_APPROVAL') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->whereNull('signed_at')
                    ->whereIn('status', [
                        QualityCertificate::STATUS_DRAFT,
                        QualityCertificate::STATUS_WAIT_PTN_MANAGER_APPROVAL,
                    ])
                    ->where(function ($q) {
                        $q->whereNull('smartca_status')
                            ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                    });
            });

            return;
        }

        if ($status === 'READY_TO_SIGN') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->whereNull('signed_at')
                    ->where('status', QualityCertificate::STATUS_READY_TO_SIGN)
                    ->where(function ($q) {
                        $q->whereNull('smartca_status')
                            ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                    });
            });

            return;
        }

        if ($status === 'SIGN_PENDING') {
            $query->whereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                $certificate
                    ->whereNull('signed_at')
                    ->where('smartca_status', 'PENDING')
                    ->where('smartca_requested_at', '>', $expiredBefore);
            });

            return;
        }

        if ($status === 'SIGN_EXPIRED') {
            $query->whereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                $certificate
                    ->whereNull('signed_at')
                    ->where(function ($q) use ($expiredBefore) {
                        $q->where('status', QualityCertificate::STATUS_SIGN_EXPIRED)
                            ->orWhere('smartca_status', 'EXPIRED')
                            ->orWhere(function ($pending) use ($expiredBefore) {
                                $pending->where('smartca_status', 'PENDING')
                                    ->where('smartca_requested_at', '<=', $expiredBefore);
                            });
                    });
            });

            return;
        }

        if ($status === 'ISSUED') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->where('status', QualityCertificate::STATUS_ISSUED)
                    ->whereNotNull('signed_at');
            });

            return;
        }

        if (in_array($status, [
            QualityCertificate::STATUS_REJECTED,
            QualityCertificate::STATUS_REVOKED,
        ], true)) {
            $query->whereHas('qualityCertificates', fn ($certificate) => $certificate->where('status', $status));
        }
    }

    private function smartCaPendingTtlMinutes(): int
    {
        return max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
    }

    private function slaCounts($baseQuery, ?SlaConfig $slaDvkh, ?SlaConfig $slaPtn): array
    {
        $warningCount = 0;
        $overdueCount = 0;

        foreach ([
            ['DVKH', ['WAIT_DVKH'], $slaDvkh],
            ['PTN', ['WAIT_PTN', 'PTN_PROCESSING'], $slaPtn],
        ] as [$step, $statuses, $sla]) {
            if (!$sla) {
                continue;
            }

            $overdueQuery = (clone $baseQuery)->whereIn('status', $statuses);
            $this->slaClock->applyFilter($overdueQuery, 'overdue', $sla, $step);
            $overdueCount += $overdueQuery->count();

            $warningQuery = (clone $baseQuery)->whereIn('status', $statuses);
            $this->slaClock->applyFilter($warningQuery, 'warning', $sla, $step);
            $warningCount += $warningQuery->count();
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
