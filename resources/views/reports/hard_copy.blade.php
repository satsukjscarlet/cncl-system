@extends('adminlte::page')

@section('title', 'Báo cáo ký tươi')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260818-1') }}">
    <style>
        .hard-copy-kpi .small-box {
            border-radius: 8px;
            min-height: 116px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .09);
        }

        .hard-copy-kpi .small-box .inner {
            padding: 16px 18px;
        }

        .hard-copy-kpi .small-box h3 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .hard-copy-table td {
            vertical-align: top;
        }

        .hard-copy-no {
            color: #0b5ed7;
            font-weight: 700;
            white-space: nowrap;
        }

        .hard-copy-customer {
            min-width: 260px;
        }

        .hard-copy-filter-actions {
            display: flex;
            gap: 8px;
        }

        .hard-copy-number {
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }

        .hard-copy-active-filters .badge { max-width:100%; white-space:normal; overflow-wrap:anywhere; line-height:1.5; }
        .hard-copy-center-card .card-header { flex-wrap:wrap; gap:8px; }
        .hard-copy-center-card .card-header > div { min-width:0; }

        .hard-copy-center-card .table th,
        .hard-copy-center-card .table td {
            vertical-align: middle;
            white-space: nowrap;
        }

        .hard-copy-center-card .center-name {
            min-width: 220px;
            white-space: normal;
        }

        .hard-copy-center-card tfoot th {
            background: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .hard-copy-filter-actions .btn {
                flex: 1 1 auto;
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-start">
    <div>
        <h1 class="m-0">Báo cáo ký tươi</h1>
        <small class="text-muted">Số yêu cầu, tổng số bản và tổng trang theo hai phương án in</small>
    </div>

    @can('report.export')
        <a href="{{ route('reports.hard-copy.export', request()->query()) }}" class="btn btn-success mt-2 mt-md-0 mr-2" data-download>
            <i class="fas fa-file-excel"></i> Xuất Excel
        </a>
    @endcan
    <a href="{{ route('reports.summary') }}" class="btn btn-outline-secondary mt-2 mt-md-0">
        <i class="fas fa-chart-bar"></i> Báo cáo tổng hợp
    </a>
</div>
@stop

@section('content')
<div class="mb-3 d-flex flex-wrap" style="gap:8px">
    <span class="font-weight-bold align-self-center">Nhu cầu ký tươi theo ngày ký số</span>
    <a href="{{ route('reports.hard-copy-supplements') }}" class="btn btn-outline-primary"><i class="fas fa-copy"></i> Báo cáo bổ sung bản ký tươi</a>
</div>
<div class="row hard-copy-kpi">
    @foreach([
        'request_count' => ['Yêu cầu cần cung cấp ký tươi', 'bg-primary', 'fa-file-alt'],
        'total_copy_count' => ['Tổng số bản in', 'bg-info', 'fa-copy'],
        'single_sheet_count' => ['Tờ In đơn dự kiến', 'bg-success', 'fa-layer-group'],
        'batch_sheet_count' => ['Tờ In bộ dự kiến', 'bg-warning', 'fa-file-contract'],
    ] as $key => $metric)
    <div class="col-xl-3 col-md-6"><div class="small-box {{ $metric[1] }}"><div class="inner"><h3>{{ number_format($summary[$key]) }}</h3><p>{{ $metric[0] }}</p></div><div class="icon"><i class="fas {{ $metric[2] }}"></i></div></div></div>
    @endforeach
</div>

<div class="card card-primary card-outline">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0"><i class="fas fa-filter"></i> Bộ lọc báo cáo</h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Từ ngày ký số</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Đến ngày ký số</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>

            @if($canViewAllCenters)
                <div class="col-xl-2 col-md-6">
                    <div class="form-group">
                        <label>Trung tâm</label>
                        <select name="distribution_center_id" class="form-control select2">
                            <option value="">Tất cả trung tâm</option>
                            @foreach($centers as $center)
                                <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                    {{ $center->code }} - {{ $center->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Kiểu in</label>
                    <select name="print_template" class="form-control select2">
                        <option value="">Tất cả kiểu in</option>
                        <option value="single" {{ request('print_template') === 'single' ? 'selected' : '' }}>In đơn</option>
                        <option value="batch" {{ request('print_template') === 'batch' ? 'selected' : '' }}>In bộ</option>
                    </select>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Ghi nhận bấm in</label>
                    <select name="print_status" class="form-control select2">
                        <option value="">Tất cả lượt bấm in</option>
                        <option value="not_printed" {{ request('print_status') === 'not_printed' ? 'selected' : '' }}>Chưa ghi nhận bấm in</option>
                        <option value="printed_single" {{ request('print_status') === 'printed_single' ? 'selected' : '' }}>Đã bấm In đơn</option>
                        <option value="printed_batch" {{ request('print_status') === 'printed_batch' ? 'selected' : '' }}>Đã bấm In bộ</option>
                        <option value="printed_both" {{ request('print_status') === 'printed_both' ? 'selected' : '' }}>Đã bấm cả hai</option>
                    </select>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Đơn vị bán hàng</label>
                    <select name="sales_unit_id" class="form-control select2">
                        <option value="">Tất cả ĐVBH</option>
                        @foreach($salesUnits as $salesUnit)
                            <option value="{{ $salesUnit->id }}" {{ request('sales_unit_id') == $salesUnit->id ? 'selected' : '' }}>
                                {{ $salesUnit->code }} - {{ $salesUnit->name }}
                                @if($canViewAllCenters && $salesUnit->distributionCenter)
                                    ({{ $salesUnit->distributionCenter->code }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-xl-2 col-md-8">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control"
                           value="{{ request('keyword') }}"
                           placeholder="Số phiếu, số yêu cầu, hóa đơn, khách hàng, công trình, ĐVBH">
                </div>
            </div>

            <div class="col-xl-2 col-md-4">
                <div class="form-group hard-copy-filter-actions">
                    <button class="btn btn-primary" title="Lọc">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('reports.hard-copy') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
        @php
            $quickDateFrom = $availableDateRange['from'] ? \Illuminate\Support\Carbon::parse($availableDateRange['from']) : null;
            $quickDateTo = $availableDateRange['to'] ? \Illuminate\Support\Carbon::parse($availableDateRange['to']) : null;
            $quickBaseQuery = request()->except(['date_from', 'date_to', 'page']);
            $quickLast30From = $quickDateTo ? $quickDateTo->copy()->subDays(29) : null;
            if ($quickDateFrom && $quickLast30From && $quickLast30From->lt($quickDateFrom)) {
                $quickLast30From = $quickDateFrom->copy();
            }
            $quickMonthFrom = $quickDateTo ? $quickDateTo->copy()->startOfMonth() : null;
            if ($quickDateFrom && $quickMonthFrom && $quickMonthFrom->lt($quickDateFrom)) {
                $quickMonthFrom = $quickDateFrom->copy();
            }
        @endphp
        @if($quickDateFrom && $quickDateTo)
            <div class="border-top pt-3 mt-2">
                <span class="text-muted mr-2">Khoảng ngày nhanh:</span>
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('reports.hard-copy', array_merge($quickBaseQuery, ['date_from' => $quickDateFrom->toDateString(), 'date_to' => $quickDateTo->toDateString()])) }}">
                    Toàn bộ dữ liệu
                </a>
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('reports.hard-copy', array_merge($quickBaseQuery, ['date_from' => $quickLast30From->toDateString(), 'date_to' => $quickDateTo->toDateString()])) }}">
                    30 ngày gần nhất có dữ liệu
                </a>
                <a class="btn btn-sm btn-outline-primary"
                   href="{{ route('reports.hard-copy', array_merge($quickBaseQuery, ['date_from' => $quickMonthFrom->toDateString(), 'date_to' => $quickDateTo->toDateString()])) }}">
                    Tháng gần nhất có dữ liệu
                </a>
            </div>
        @endif
    </div>
</div>

@php
    $hasDateFilter = request()->filled('date_from') || request()->filled('date_to');
    $availableFrom = $availableDateRange['from'] ?? null;
    $availableTo = $availableDateRange['to'] ?? null;
    $salesUnitDisplayLimit = 20;
    $salesUnitRows = collect($salesUnitStats)
        ->sortByDesc('total_copy_count')
        ->values();
    $visibleSalesUnitRows = $salesUnitRows->take($salesUnitDisplayLimit);
    $hiddenSalesUnitCount = max(0, $salesUnitRows->count() - $visibleSalesUnitRows->count());
@endphp

@if(!empty($filterSummary))
    <div class="card">
        <div class="card-header bg-white">
            <h3 class="card-title mb-0"><i class="fas fa-sliders-h"></i> Bộ lọc đang áp dụng</h3>
        </div>
        <div class="card-body py-2">
            <div class="d-flex flex-wrap hard-copy-active-filters" style="gap: 8px;">
                @foreach($filterSummary as $filterItem)
                    <span class="badge badge-light border p-2 text-left">
                        <span class="text-muted">{{ $filterItem['label'] }}:</span>
                        <strong>{{ $filterItem['value'] }}</strong>
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endif

@if($availableFrom && $availableTo)
    <div class="alert {{ $hasDateFilter && $summary['certificate_count'] === 0 ? 'alert-warning' : 'alert-light border' }}">
        <i class="fas fa-calendar-alt"></i>
        Dữ liệu ký tươi hiện có từ
        <strong>{{ \Illuminate\Support\Carbon::parse($availableFrom)->format('d/m/Y') }}</strong>
        đến
        <strong>{{ \Illuminate\Support\Carbon::parse($availableTo)->format('d/m/Y') }}</strong>.
        @if($hasDateFilter && $summary['certificate_count'] === 0)
            Khoảng ngày đang lọc không có phiếu phù hợp, hãy mở rộng khoảng ngày hoặc bấm nút xóa bộ lọc.
        @endif
    </div>
@elseif($hasDateFilter)
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Không có dữ liệu ký tươi phù hợp với các bộ lọc ngoài khoảng ngày hiện tại.
    </div>
@endif

<p class="text-muted small mb-3">
    Tổng số bản = ban đầu + bổ sung đã hoàn tất. Tổng trang dự kiến = tổng số bản × số trang từng mẫu, tính riêng từng phiếu; không cộng In đơn và In bộ.
    Lọc theo ngày ký số; bổ sung tính đến lúc xem báo cáo, không phải số đã in thực tế.
</p>

<section class="card">
    <div class="card-header bg-white"><h2 class="card-title"><i class="fas fa-book-open mr-1"></i> Định nghĩa số liệu</h2></div>
    <div class="card-body small">
        <dl class="row mb-0">
            <dt class="col-md-3">Yêu cầu cần ký tươi</dt><dd class="col-md-9">Số yêu cầu gốc không trùng có phiếu đã ký số, có bản ký tươi ban đầu hoặc bổ sung đã hoàn tất.</dd>
            <dt class="col-md-3">Tổng số bản in</dt><dd class="col-md-9">Số bản yêu cầu ban đầu + số bản bổ sung PTN đã xác nhận hoàn tất. Không gồm bổ sung đang chờ, trả lại hoặc đã hủy.</dd>
            <dt class="col-md-3">Trang In đơn / In bộ</dt><dd class="col-md-9">Số trang của một bản theo từng mẫu. Tại bảng tổng hợp, đây là tổng số trang một bản của từng phiếu, không phải số tờ cần in.</dd>
            <dt class="col-md-3">Tờ In đơn / In bộ dự kiến</dt><dd class="col-md-9">Cộng (tổng số bản của từng phiếu × số trang mẫu tương ứng). Không lấy tổng số bản nhân tổng cột Trang; không cộng hai phương án với nhau.</dd>
            <dt class="col-md-3">Khoảng thời gian</dt><dd class="col-md-9">Lọc theo ngày ký số phiếu; bổ sung là phần đã hoàn tất đến thời điểm xem hoặc xuất, không phải phát sinh theo ngày hoàn tất trong kỳ.</dd>
            <dt class="col-md-3">Ghi nhận mở file</dt><dd class="col-md-9 mb-0">Lượt mở file in ban đầu, không gồm bổ sung hoặc in khẩn cấp; không xác nhận số bản thực tế đã in.</dd>
        </dl>
    </div>
</section>

<section class="card hard-copy-center-card">
    <div class="card-header bg-white"><h2 class="card-title"><i class="fas fa-building mr-1"></i> Theo Trung tâm</h2></div>
    @include('reports.partials.hard_copy_compact_stats', ['rows' => $centerStats, 'groupLabel' => 'Trung tâm', 'groupType' => 'center'])
</section>

<details class="mb-3 border rounded bg-white">
    <summary class="p-3 font-weight-bold">Theo tháng ký số</summary>
    @include('reports.partials.hard_copy_compact_stats', ['rows' => $monthlyStats, 'groupLabel' => 'Tháng ký số', 'groupType' => 'month'])
</details>

<details class="mb-3 border rounded bg-white">
    <summary class="p-3 font-weight-bold">Theo đơn vị bán hàng <span class="badge badge-light border">{{ $salesUnitRows->count() }}</span></summary>
    @if($hiddenSalesUnitCount > 0)<p class="text-muted small px-3">Hiển thị {{ $visibleSalesUnitRows->count() }}/{{ $salesUnitRows->count() }} đơn vị theo tổng số bản; dòng tổng tính tất cả. Xuất Excel để xem đầy đủ.</p>@endif
    @include('reports.partials.hard_copy_compact_stats', ['rows' => $visibleSalesUnitRows, 'totalRows' => $salesUnitRows, 'groupLabel' => 'Đơn vị bán hàng', 'groupType' => 'sales'])
</details>

<section class="card">
    <div class="card-header bg-white"><h2 class="card-title"><i class="fas fa-list mr-1"></i> Chi tiết theo phiếu</h2></div>
    <div class="table-responsive"><table class="table table-bordered table-hover hard-copy-table mb-0">
        <thead class="thead-light"><tr><th>STT</th><th>Số phiếu</th><th>Số yêu cầu</th><th>Ngày ký số</th><th>Trung tâm</th><th>Khách hàng / Công trình</th><th>Đơn vị bán hàng</th><th class="text-right">Số bản ban đầu</th><th class="text-right">Bổ sung đã hoàn tất</th><th class="text-right">Tổng số bản in</th><th class="text-right">Trang In đơn</th><th class="text-right">Tờ In đơn dự kiến</th><th class="text-right">Trang In bộ</th><th class="text-right">Tờ In bộ dự kiến</th><th>Ghi nhận bấm in ban đầu</th><th>Mở file ban đầu gần nhất</th></tr></thead>
        <tbody>
        @forelse($certificates as $certificate)
            @php
                $requestModel = $certificate->request;
                $originalCopies = $requestModel?->require_hard_copy ? max(0, (int) $requestModel->hard_copy_quantity) : 0;
                $extraCopies = (int) $certificate->completed_supplement_quantity;
                $totalCopies = $originalCopies + $extraCopies;
                $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
                $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
                $logs = $certificate->printLogs->where('print_mode', 'normal')->whereNull('hard_copy_supplement_id')
                    ->sortByDesc(fn ($log) => $log->created_at?->timestamp ?? 0);
                $singleOpens = $logs->where('print_template', 'single')->count();
                $batchOpens = $logs->where('print_template', 'batch')->count();
                $latestOpen = $logs->first();
                $printLabel = match (true) {
                    $singleOpens > 0 && $batchOpens > 0 => 'Đã mở cả hai',
                    $singleOpens > 0 => 'Đã mở In đơn',
                    $batchOpens > 0 => 'Đã mở In bộ',
                    default => 'Chưa ghi nhận',
                };
            @endphp
            <tr>
                <td>{{ $certificates->firstItem() + $loop->index }}</td>
                <td><a class="hard-copy-no" href="{{ route('quality-certificates.show', $certificate) }}">{{ $certificate->certificate_no }}</a></td>
                <td>@if($requestModel)<a class="hard-copy-no" href="{{ route('certificate-requests.show', $requestModel) }}">{{ $requestModel->request_no }}</a>@else - @endif</td>
                <td class="text-nowrap">{{ $certificate->signed_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $requestModel?->distributionCenter?->name ?: '-' }}</td>
                <td class="hard-copy-customer"><strong>{{ $requestModel?->customer?->customer_name ?: '-' }}</strong><div class="small text-muted">{{ $requestModel?->customer?->project_name }}</div></td>
                <td><strong>{{ $requestModel?->salesUnit?->code }}</strong><div>{{ $requestModel?->salesUnit?->name ?: '-' }}</div></td>
                <td class="hard-copy-number">{{ number_format($originalCopies) }}</td>
                <td class="hard-copy-number">@if($extraCopies)<a href="{{ route('hard-copy-supplements.index', ['quality_certificate_id' => $certificate->id, 'status' => 'COMPLETED']) }}" title="Xem bổ sung đã hoàn tất">{{ number_format($extraCopies) }}</a>@else 0 @endif</td>
                <td class="hard-copy-number">{{ number_format($totalCopies) }}</td>
                <td class="hard-copy-number">{{ number_format($singlePages) }}</td>
                <td class="hard-copy-number">{{ number_format($totalCopies * $singlePages) }}</td>
                <td class="hard-copy-number">{{ number_format($batchPages) }}</td>
                <td class="hard-copy-number">{{ number_format($totalCopies * $batchPages) }}</td>
                <td><span class="badge {{ $singleOpens || $batchOpens ? 'badge-info' : 'badge-secondary' }}">{{ $printLabel }}</span><div class="small text-muted text-nowrap">In đơn: {{ $singleOpens }} · In bộ: {{ $batchOpens }}</div></td>
                <td>@if($latestOpen)<strong>{{ $latestOpen->user?->name ?: '-' }}</strong><div class="small text-muted text-nowrap">{{ $latestOpen->created_at?->format('d/m/Y H:i') }}</div><div class="small">{{ $latestOpen->print_template === 'batch' ? 'In bộ' : 'In đơn' }}</div>@else - @endif</td>
            </tr>
        @empty<tr><td colspan="16" class="text-center text-muted py-4">Không có dữ liệu ký tươi phù hợp bộ lọc.</td></tr>@endforelse
        </tbody>
    </table></div>
    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
        <span class="small text-muted">{{ $certificates->firstItem() ?? 0 }}–{{ $certificates->lastItem() ?? 0 }} / {{ number_format($certificates->total()) }} phiếu</span>
        {{ $certificates->links() }}
    </div>
</section>
@stop
