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
        <small class="text-muted">Thống kê yêu cầu ký tươi theo phiếu đã ký số, số bản in yêu cầu và số trang trong phiếu</small>
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
<div class="row hard-copy-kpi">
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($summary['request_count']) }}</h3>
                <p>Yêu cầu ký tươi</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($summary['certificate_count']) }}</h3>
                <p>Phiếu đã ký số cần ký tươi</p>
            </div>
            <div class="icon"><i class="fas fa-certificate"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($summary['copy_count']) }}</h3>
                <p>Tổng số bản in yêu cầu</p>
            </div>
            <div class="icon"><i class="fas fa-copy"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($summary['single_sheet_count']) }}</h3>
                <p>Tờ In đơn dự kiến</p>
            </div>
            <div class="icon"><i class="fas fa-layer-group"></i></div>
        </div>
    </div>
</div>

<div class="row hard-copy-kpi">
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($summary['batch_sheet_count']) }}</h3>
                <p>Tờ In bộ dự kiến</p>
            </div>
            <div class="icon"><i class="fas fa-file-contract"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-light">
            <div class="inner">
                <h3>{{ number_format($printSummary['single_print_count']) }}</h3>
                <p>Lượt bấm In đơn ghi nhận</p>
            </div>
            <div class="icon"><i class="fas fa-print"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-light">
            <div class="inner">
                <h3>{{ number_format($printSummary['batch_print_count']) }}</h3>
                <p>Lượt bấm In bộ ghi nhận</p>
            </div>
            <div class="icon"><i class="fas fa-print"></i></div>
        </div>
    </div>
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
        ->sortByDesc(fn ($row) => ($row['single_sheet_count'] ?? 0) + ($row['batch_sheet_count'] ?? 0))
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
            <div class="d-flex flex-wrap" style="gap: 8px;">
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

<div class="alert alert-info">
    <i class="fas fa-info-circle"></i>
    Báo cáo chỉ tính các phiếu đã ký số/phát hành và có yêu cầu ký tươi. Số tờ dự kiến = số bản in yêu cầu x số trang của từng mẫu in. Lượt bấm in chỉ là số lần người dùng bấm nút in trên hệ thống, không xác nhận số bản thực tế từ máy in.
</div>

<div class="card hard-copy-center-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-calendar-check"></i> Thống kê ký tươi theo tháng ký số
        </h3>
        <span class="badge badge-primary">{{ number_format(count($monthlyStats)) }} tháng</span>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Tháng ký số</th>
                    <th class="text-right">Yêu cầu ký tươi</th>
                    <th class="text-right">Phiếu đã ký số</th>
                    <th class="text-right">Số bản in yêu cầu</th>
                    <th class="text-right">Trang In đơn</th>
                    <th class="text-right">Tờ In đơn dự kiến</th>
                    <th class="text-right">Trang In bộ</th>
                    <th class="text-right">Tờ In bộ dự kiến</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyStats as $row)
                    <tr>
                        <td><strong>{{ $row['month_label'] }}</strong></td>
                        <td class="hard-copy-number">{{ number_format($row['request_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['certificate_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['copy_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_page_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_sheet_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_page_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_sheet_count']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Không có dữ liệu thống kê theo tháng.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($monthlyStats)
                <tfoot>
                    <tr>
                        <th>Tổng</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('request_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('certificate_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('copy_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('single_page_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('single_sheet_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('batch_page_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($monthlyStats)->sum('batch_sheet_count')) }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card hard-copy-center-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h3 class="card-title mb-0">
                <i class="fas fa-store"></i> Thống kê ký tươi theo Đơn vị bán hàng
            </h3>
            @if($hiddenSalesUnitCount > 0)
                <div class="text-muted small mt-1">
                    Đang hiển thị Top {{ number_format($visibleSalesUnitRows->count()) }}/{{ number_format($salesUnitRows->count()) }} đơn vị theo tổng tờ dự kiến. Xuất Excel để xem đầy đủ.
                </div>
            @endif
        </div>
        <span class="badge badge-primary">{{ number_format($salesUnitRows->count()) }} đơn vị</span>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th class="center-name">Trung tâm</th>
                    <th class="center-name">Đơn vị bán hàng</th>
                    <th class="text-right">Yêu cầu ký tươi</th>
                    <th class="text-right">Phiếu đã ký số</th>
                    <th class="text-right">Số bản in yêu cầu</th>
                    <th class="text-right">Trang In đơn</th>
                    <th class="text-right">Tờ In đơn dự kiến</th>
                    <th class="text-right">Trang In bộ</th>
                    <th class="text-right">Tờ In bộ dự kiến</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visibleSalesUnitRows as $row)
                    <tr>
                        <td class="center-name">
                            @if($row['center'])
                                <strong>{{ $row['center']->code }}</strong> - {{ $row['center']->name }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="center-name">
                            @if($row['sales_unit'])
                                <strong>{{ $row['sales_unit']->code }}</strong> - {{ $row['sales_unit']->name }}
                            @else
                                <span class="text-muted">Chưa gắn đơn vị bán hàng</span>
                            @endif
                        </td>
                        <td class="hard-copy-number">{{ number_format($row['request_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['certificate_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['copy_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_page_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_sheet_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_page_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_sheet_count']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Không có dữ liệu thống kê theo Đơn vị bán hàng.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($salesUnitRows->isNotEmpty())
                <tfoot>
                    <tr>
                        <th colspan="2">Tổng</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('request_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('certificate_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('copy_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('single_page_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('single_sheet_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('batch_page_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format($salesUnitRows->sum('batch_sheet_count')) }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0"><i class="fas fa-book-open"></i> Định nghĩa số liệu</h3>
    </div>
    <div class="card-body py-2">
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-2">
                <strong>Yêu cầu ký tươi</strong>
                <div class="text-muted small">Số yêu cầu có tích yêu cầu ký tươi và có ít nhất 1 phiếu đã ký số.</div>
            </div>
            <div class="col-lg-3 col-md-6 mb-2">
                <strong>Phiếu đã ký số</strong>
                <div class="text-muted small">Chỉ tính phiếu trạng thái phát hành, đã có thời gian ký số.</div>
            </div>
            <div class="col-lg-3 col-md-6 mb-2">
                <strong>Tờ dự kiến</strong>
                <div class="text-muted small">Số bản in yêu cầu nhân với số trang của từng mẫu In đơn/In bộ.</div>
            </div>
            <div class="col-lg-3 col-md-6 mb-2">
                <strong>Lượt bấm in ghi nhận</strong>
                <div class="text-muted small">Số lần người dùng bấm nút in trên hệ thống; không xác nhận số bản thực tế từ máy in.</div>
            </div>
        </div>
    </div>
</div>

<div class="card hard-copy-center-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-chart-bar"></i> Thống kê ký tươi theo Trung tâm
        </h3>
        <span class="badge badge-primary">{{ number_format(count($centerStats)) }} trung tâm</span>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th class="center-name">Trung tâm</th>
                    <th class="text-right">Yêu cầu ký tươi</th>
                    <th class="text-right">Phiếu đã ký số</th>
                    <th class="text-right">Số bản in yêu cầu</th>
                    <th class="text-right">Trang In đơn</th>
                    <th class="text-right">Tờ In đơn dự kiến</th>
                    <th class="text-right">Trang In bộ</th>
                    <th class="text-right">Tờ In bộ dự kiến</th>
                    <th class="text-right">Lượt bấm In đơn</th>
                    <th class="text-right">Lượt bấm In bộ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($centerStats as $row)
                    <tr>
                        <td class="center-name">
                            <strong>{{ $row['center']->code }}</strong> - {{ $row['center']->name }}
                        </td>
                        <td class="hard-copy-number">{{ number_format($row['request_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['certificate_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['copy_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_page_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_sheet_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_page_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_sheet_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['single_print_count']) }}</td>
                        <td class="hard-copy-number">{{ number_format($row['batch_print_count']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Không có dữ liệu thống kê theo Trung tâm.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($centerStats)
                <tfoot>
                    <tr>
                        <th class="center-name">Tổng</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('request_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('certificate_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('copy_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('single_page_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('single_sheet_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('batch_page_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('batch_sheet_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('single_print_count')) }}</th>
                        <th class="hard-copy-number">{{ number_format(collect($centerStats)->sum('batch_print_count')) }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-table"></i> Chi tiết phiếu ký tươi</h3>
        <span class="badge badge-info">Tổng số: {{ number_format($certificates->total()) }}</span>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0 hard-copy-table">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Số phiếu</th>
                    <th>Số yêu cầu</th>
                    <th>Ngày ký số</th>
                    <th>Trung tâm</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Đơn vị bán hàng</th>
                    <th class="text-right">Số bản in yêu cầu</th>
                    <th class="text-right">Trang In đơn</th>
                    <th class="text-right">Tờ In đơn dự kiến</th>
                    <th class="text-right">Trang In bộ</th>
                    <th class="text-right">Tờ In bộ dự kiến</th>
                    <th>Ghi nhận bấm in</th>
                    <th>Bấm in gần nhất</th>
                </tr>
            </thead>

            <tbody>
                @forelse($certificates as $certificate)
                    @php
                        $requestModel = $certificate->request;
                        $copyQuantity = max(0, (int) ($requestModel?->hard_copy_quantity ?? 0));
                        $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
                        $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
                        $normalPrintLogs = $certificate->printLogs
                            ->where('print_mode', 'normal')
                            ->sortByDesc(fn ($log) => optional($log->created_at)->timestamp ?? 0);
                        $singlePrintCount = $normalPrintLogs->where('print_template', 'single')->count();
                        $batchPrintCount = $normalPrintLogs->where('print_template', 'batch')->count();
                        $latestNormalPrint = $normalPrintLogs->first();
                        $printStatusLabel = match (true) {
                            $singlePrintCount > 0 && $batchPrintCount > 0 => 'Đã bấm cả hai',
                            $singlePrintCount > 0 => 'Đã bấm In đơn',
                            $batchPrintCount > 0 => 'Đã bấm In bộ',
                            default => 'Chưa ghi nhận',
                        };
                        $printStatusClass = match (true) {
                            $singlePrintCount > 0 && $batchPrintCount > 0 => 'badge-success',
                            $singlePrintCount > 0 || $batchPrintCount > 0 => 'badge-info',
                            default => 'badge-secondary',
                        };
                    @endphp
                    <tr>
                        <td>{{ $certificates->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('quality-certificates.show', $certificate) }}" class="hard-copy-no">
                                {{ $certificate->certificate_no }}
                            </a>
                        </td>
                        <td>
                            @if($requestModel)
                                <a href="{{ route('certificate-requests.show', $requestModel) }}" class="hard-copy-no">
                                    {{ $requestModel->request_no }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{{ optional($certificate->signed_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $requestModel?->distributionCenter?->name ?? '-' }}</td>
                        <td class="hard-copy-customer">
                            <strong>{{ $requestModel?->customer?->customer_name ?? '-' }}</strong>
                            @if($requestModel?->customer?->project_name)
                                <div class="text-muted small">{{ $requestModel->customer->project_name }}</div>
                            @endif
                        </td>
                        <td>
                            @if($requestModel?->salesUnit)
                                <strong>{{ $requestModel->salesUnit->code }}</strong>
                                <div class="text-muted small">{{ $requestModel->salesUnit->name }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="hard-copy-number">{{ number_format($copyQuantity) }}</td>
                        <td class="hard-copy-number">{{ number_format($singlePages) }}</td>
                        <td class="hard-copy-number">{{ number_format($copyQuantity * $singlePages) }}</td>
                        <td class="hard-copy-number">{{ number_format($batchPages) }}</td>
                        <td class="hard-copy-number">{{ number_format($copyQuantity * $batchPages) }}</td>
                        <td>
                            <span class="badge {{ $printStatusClass }}">{{ $printStatusLabel }}</span>
                            @if($singlePrintCount || $batchPrintCount)
                                <div class="text-muted small">
                                    Bấm In đơn: {{ $singlePrintCount }} | Bấm In bộ: {{ $batchPrintCount }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($latestNormalPrint)
                                <strong>{{ $latestNormalPrint->user->name ?? '-' }}</strong>
                                <div class="text-muted small">{{ optional($latestNormalPrint->created_at)->format('d/m/Y H:i') }}</div>
                                <div class="text-muted small">{{ $latestNormalPrint->print_template === 'batch' ? 'In bộ' : 'In đơn' }}</div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Không có dữ liệu ký tươi phù hợp bộ lọc.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $certificates->firstItem() ?? 0 }} - {{ $certificates->lastItem() ?? 0 }} / {{ number_format($certificates->total()) }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $certificates->links() }}</div>
            </div>
        </div>
    </div>
</div>
@stop
