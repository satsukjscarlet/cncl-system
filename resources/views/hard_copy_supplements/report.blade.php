@extends('adminlte::page')
@section('title', 'Báo cáo bổ sung bản ký tươi')
@section('css')
<link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20261026') }}">
<link rel="stylesheet" href="{{ asset('css/hard-copy-supplements.css?v=20261029-report-1') }}">
@stop
@section('content_header')
<div class="supplement-header">
    <div><h1>Báo cáo bổ sung bản ký tươi</h1><small class="text-muted">Theo dõi các lần bổ sung, số bản đã duyệt và đã hoàn tất</small></div>
    <div class="supplement-links">
        <a href="{{ route('reports.hard-copy') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Báo cáo ký tươi</a>
        @can('report.export')<a href="{{ route('reports.hard-copy-supplements.export', request()->query()) }}" class="btn btn-success" data-download><i class="fas fa-file-excel"></i> Xuất Excel</a>@endcan
    </div>
</div>
@stop
@section('content')
@php
    $dateLabels = ['submitted_at' => 'Ngày gửi bổ sung', 'approved_at' => 'Ngày DVKH duyệt', 'completed_at' => 'Ngày PTN hoàn tất', 'signed_at' => 'Ngày ký số phiếu'];
    $dateLabel = $dateLabels[request('date_basis') ?: 'submitted_at'] ?? $dateLabels['submitted_at'];
@endphp
@include('hard_copy_supplements.messages')
<div class="supplement-index supplement-report">
    <div class="row supplement-report-kpis">
        @foreach([
            'requests' => ['Lượt yêu cầu bổ sung', 'bg-primary', 'fa-copy'],
            'certificates' => ['Phiếu CNCL không trùng', 'bg-info', 'fa-certificate'],
            'approved' => ['Bản bổ sung đã duyệt', 'bg-warning', 'fa-user-check'],
            'completed' => ['Bản bổ sung đã hoàn tất', 'bg-success', 'fa-check-circle'],
        ] as $key => $metric)
        <div class="col-xl-3 col-md-6"><div class="small-box {{ $metric[1] }}"><div class="inner"><h3>{{ number_format($summary[$key]) }}</h3><p>{{ $metric[0] }}</p></div><div class="icon"><i class="fas {{ $metric[2] }}"></i></div></div></div>
        @endforeach
    </div>
    <div class="supplement-filter-panel">
        <div class="filter-title mb-3"><span class="filter-icon"><i class="fas fa-filter"></i></span> Bộ lọc báo cáo</div>
        @include('hard_copy_supplements.filters', ['reportFilters' => true])
    </div>
    <div class="supplement-report-context">
        <span><i class="far fa-calendar-alt mr-1"></i> <strong>{{ $dateLabel }}:</strong> {{ request('date_from') ? \Illuminate\Support\Carbon::parse(request('date_from'))->format('d/m/Y') : 'Không giới hạn' }} → {{ request('date_to') ? \Illuminate\Support\Carbon::parse(request('date_to'))->format('d/m/Y') : 'Không giới hạn' }}</span>
        <span class="text-muted">{{ number_format($items->total()) }} yêu cầu phù hợp</span>
    </div>
    <dl class="supplement-report-secondary">
        @foreach(['original' => 'Bản ban đầu của các phiếu', 'total' => 'Tổng nhu cầu được duyệt', 'single_sheets' => 'Tờ In đơn bổ sung dự kiến', 'batch_sheets' => 'Tờ In bộ bổ sung dự kiến', 'unassigned' => 'Bản đã duyệt chưa chọn mẫu'] as $key => $label)
        <div><dt>{{ $label }}</dt><dd>{{ number_format($summary[$key]) }}</dd></div>
        @endforeach
    </dl>

    <section class="supplement-list-panel mb-3">
        <div class="supplement-list-heading"><h2><i class="fas fa-building text-primary mr-2"></i>Thống kê theo Trung tâm</h2><span class="badge badge-light border">{{ $groups->count() }} trung tâm</span></div>
        <div class="table-responsive"><table class="table table-bordered table-hover supplement-report-table mb-0">
            <thead><tr><th scope="col">Trung tâm</th><th scope="col">Lượt yêu cầu</th><th scope="col">Phiếu CNCL</th><th scope="col">Bản ban đầu</th><th scope="col">Bổ sung đã duyệt</th><th scope="col">Bổ sung hoàn tất</th><th scope="col">Tổng được duyệt</th><th scope="col">Tờ In đơn dự kiến</th><th scope="col">Tờ In bộ dự kiến</th></tr></thead>
            <tbody>
            @forelse($groups as $row)<tr><td>{{ $row['center'] }}</td>
                @foreach(['requests', 'certificates', 'original', 'approved', 'completed', 'total', 'single_sheets', 'batch_sheets'] as $key)<td class="text-right">{{ number_format($row[$key]) }}</td>@endforeach
            </tr>@empty<tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-inbox mr-2"></i>Không có dữ liệu phù hợp bộ lọc.</td></tr>@endforelse
            </tbody>
            @if($groups->isNotEmpty())<tfoot><tr><th scope="row">Tổng</th>
                @foreach(['requests', 'certificates', 'original', 'approved', 'completed', 'total', 'single_sheets', 'batch_sheets'] as $key)<th class="text-right">{{ number_format($summary[$key]) }}</th>@endforeach
            </tr></tfoot>@endif
        </table></div>
    </section>

    <section class="supplement-list-panel mb-3">
        <div class="supplement-list-heading"><h2><i class="far fa-calendar-alt text-primary mr-2"></i>Thống kê theo tháng</h2><span class="small text-muted">{{ $dateLabel }}</span></div>
        <div class="table-responsive"><table class="table table-bordered table-hover supplement-report-table mb-0">
            <thead><tr><th scope="col">Tháng</th><th scope="col">Lượt yêu cầu</th><th scope="col">Phiếu CNCL trong tháng</th><th scope="col">Bổ sung đã duyệt</th><th scope="col">Bổ sung hoàn tất</th><th scope="col">Tờ In đơn dự kiến</th><th scope="col">Tờ In bộ dự kiến</th></tr></thead>
            <tbody>
            @forelse($monthly as $row)<tr><td>{{ $row['month'] }}</td>
                @foreach(['requests', 'certificates', 'approved', 'completed', 'single_sheets', 'batch_sheets'] as $key)<td class="text-right">{{ number_format($row[$key]) }}</td>@endforeach
            </tr>@empty<tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-2"></i>Không có dữ liệu phù hợp bộ lọc.</td></tr>@endforelse
            </tbody>
            @if($monthly->isNotEmpty())<tfoot><tr><th scope="row">Tổng không trùng</th>
                @foreach(['requests', 'certificates', 'approved', 'completed', 'single_sheets', 'batch_sheets'] as $key)<th class="text-right">{{ number_format($summary[$key]) }}</th>@endforeach
            </tr></tfoot>@endif
        </table></div>
        <p class="small text-muted px-3 pt-2 mb-2">Một phiếu có thể xuất hiện ở nhiều tháng. Tổng phiếu không trùng lấy trên toàn bộ kết quả, không cộng số phiếu từng tháng.</p>
    </section>

    <section class="supplement-list-panel mb-3">
        <div class="supplement-list-heading"><h2><i class="fas fa-book-open text-primary mr-2"></i>Định nghĩa số liệu</h2></div>
        <dl class="row supplement-report-definitions">
            <dt class="col-md-3">Bản đã duyệt / hoàn tất</dt><dd class="col-md-9">Đã duyệt gồm Chờ PTN và Đã hoàn tất, chỉ tính phiếu còn đủ điều kiện. Bản hoàn tất nằm trong số đã duyệt, không cộng lần nữa.</dd>
            <dt class="col-md-3">Bản ban đầu / tổng nhu cầu</dt><dd class="col-md-9">Bản ban đầu chỉ tính một lần cho mỗi phiếu hợp lệ trong kết quả, không phải số bản phát sinh trong kỳ. Tổng nhu cầu = bản ban đầu + bổ sung đã duyệt trong kết quả.</dd>
            <dt class="col-md-3">Tờ dự kiến / mẫu in</dt><dd class="col-md-9">Số bản bổ sung đã duyệt × số trang của mẫu mở gần nhất. Không cộng hai mẫu cho cùng yêu cầu. Bộ lọc mẫu cũng căn cứ mẫu mở gần nhất; bản chưa mở file chưa tính số tờ.</dd>
            <dt class="col-md-3">Phạm vi / lượt mở file</dt><dd class="col-md-9 mb-0">Số liệu theo bộ lọc và trạng thái hiện tại, không phải báo cáo hồi tố. Lượt mở PDF không phải số bản thực in; hoàn tất không đồng nghĩa đã bàn giao. Báo cáo ký tươi chính chỉ cộng phần bổ sung đã hoàn tất.</dd>
        </dl>
    </section>

    <section class="supplement-list-panel">
        <div class="supplement-list-heading"><h2><i class="fas fa-list-alt text-primary mr-2"></i>Chi tiết các lần bổ sung</h2><span class="badge badge-light border">{{ number_format($items->total()) }} yêu cầu</span></div>
        @include('hard_copy_supplements.table', ['sortable' => false])
    </section>
</div>
@stop
