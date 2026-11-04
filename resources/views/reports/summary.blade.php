@extends('adminlte::page')

@section('title', 'Báo cáo tổng hợp CNCL')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260818-1') }}">
    <style>
        .report-kpi .small-box {
            border-radius: 8px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .09);
            min-height: 116px;
        }

        .report-kpi .small-box .inner {
            padding: 16px 18px;
        }

        .report-kpi .small-box h3 {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .report-table td {
            vertical-align: top;
        }

        .report-request-no {
            color: #0b5ed7;
            font-weight: 700;
            white-space: nowrap;
        }

        .report-customer {
            min-width: 260px;
        }

        .report-filter-actions {
            display: flex;
            gap: 8px;
        }

        .monthly-stats-card .table th,
        .monthly-stats-card .table td {
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .monthly-stats-card .table .center-name {
            min-width: 190px;
            text-align: left;
            white-space: normal;
        }

        .monthly-stats-card .total-cell {
            font-weight: 700;
            background: #f8fafc;
        }

        .certificate-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }

        .certificate-status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            background: #fff;
        }

        .certificate-status-item span {
            color: #475569;
            font-weight: 600;
        }

        .certificate-status-item strong {
            font-size: 20px;
            color: #0f172a;
        }

        @media (max-width: 767.98px) {
            .report-filter-actions .btn {
                flex: 1 1 auto;
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-start">
    <div>
        <h1 class="m-0">Báo cáo tổng hợp CNCL</h1>
        <small class="text-muted">Theo dõi yêu cầu cấp phiếu, trạng thái xử lý và cảnh báo SLA</small>
    </div>

    @can('report.export')
        <a href="{{ route('reports.summary.export', request()->query()) }}" class="btn btn-success mt-2 mt-md-0">
            <i class="fas fa-file-excel"></i> Xuất Excel
        </a>
    @endcan
</div>
@stop

@section('content')
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif
<div class="alert alert-light border">
    <strong>Phạm vi:</strong> {{ \App\Services\CertificateReportQuery::DATE_OPTIONS[request('date_basis') ?: 'request_created_at'] }};
    mỗi yêu cầu chỉ lấy phiếu mới nhất, không cộng phiếu lịch sử.
    <div>{{ number_format($totalRequests) }} yêu cầu = {{ number_format($noCertificateCount) }} chưa lập phiếu + {{ number_format($currentCertificateCount) }} có phiếu hiện tại.</div>
    <div>Phiếu hiện tại: {{ number_format($certificateCount) }} đã phát hành + {{ number_format($revokedCertificateCount) }} đã hủy/thu hồi + {{ number_format($processingCertificateCount) }} trạng thái khác.</div>
</div>
<div class="row report-kpi">
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($totalRequests) }}</h3>
                <p>Tổng yêu cầu</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($completedRequests) }}</h3>
                <p>Yêu cầu hoàn tất</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($certificateCount) }}</h3>
                <p>Phiếu đã phát hành</p>
                @if($certificateCount)
                    <a class="text-white" href="{{ $certificateListUrls['ISSUED'] }}">Xem danh sách <i class="fas fa-arrow-right"></i></a>
                @endif
            </div>
            <div class="icon"><i class="fas fa-certificate"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($revokedCertificateCount) }}</h3>
                <p>Phiếu đã hủy / thu hồi</p>
                @if($revokedCertificateCount)
                    <a class="text-white" href="{{ $certificateListUrls['REVOKED'] }}">Xem danh sách <i class="fas fa-arrow-right"></i></a>
                @endif
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ number_format($cancelledRequests) }}</h3>
                <p>Yêu cầu bị trả lại / hủy</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $slaDvkh || $slaPtn ? number_format($warningCount) : 'Chưa bật' }}</h3>
                <p>Gần quá hạn SLA</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $slaDvkh || $slaPtn ? number_format($overdueCount) : 'Chưa bật' }}</h3>
                <p>Quá hạn SLA</p>
            </div>
            <div class="icon"><i class="fas fa-fire"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0"><i class="fas fa-certificate"></i> Trạng thái phiếu CNCL hiện tại</h3>
    </div>
    <div class="card-body">
        <div class="certificate-status-grid">
            @foreach($certificateStatusOptions as $status => $label)
                <a href="{{ route('reports.summary', array_merge(request()->except(['page', 'certificate_status']), ['certificate_status' => $status])) }}"
                   class="certificate-status-item">
                    <span>{{ $label }}</span>
                    <strong>{{ number_format($certificateStatusCounts[$status] ?? 0) }}</strong>
                </a>
            @endforeach
        </div>
        <p class="text-muted small mb-0 mt-2">Các nhóm trạng thái giữ nguyên các bộ lọc khác, chưa áp dụng bộ lọc trạng thái phiếu đang chọn. SLA chỉ tính bước đã bật cấu hình: DVKH {{ $slaDvkh ? 'đã bật' : 'chưa bật' }}, PTN {{ $slaPtn ? 'đã bật' : 'chưa bật' }}.</p>
    </div>
</div>

<div class="card card-primary card-outline filter-card">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0"><i class="fas fa-filter"></i> Bộ lọc báo cáo</h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-xl-2 col-md-6 form-group">
                <label>Lọc theo ngày</label>
                <select name="date_basis" class="form-control select2">
                    @foreach(\App\Services\CertificateReportQuery::DATE_OPTIONS as $value => $label)
                        <option value="{{ $value }}" @selected((request('date_basis') ?: 'request_created_at') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Từ ngày</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Đến ngày</label>
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
                    <label>Trạng thái yêu cầu</label>
                    <select name="request_status" class="form-control select2">
                        <option value="">Tất cả yêu cầu</option>
                        @foreach($statusOptions as $status => $label)
                            <option value="{{ $status }}" {{ request('request_status', request('status')) === $status ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Trạng thái phiếu</label>
                    <select name="certificate_status" class="form-control select2">
                        <option value="">Tất cả phiếu</option>
                        @foreach($certificateStatusOptions as $status => $label)
                            <option value="{{ $status }}" {{ request('certificate_status') === $status ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Năm thống kê phiếu</label>
                    <select name="report_year" class="form-control select2">
                        @for($year = now()->year + 1; $year >= now()->year - 5; $year--)
                            <option value="{{ $year }}" {{ (int) request('report_year', $reportYear) === $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="col-xl-2 col-md-12">
                <div class="form-group report-filter-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Lọc
                    </button>
                    <a href="{{ route('reports.summary') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card monthly-stats-card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            <i class="fas fa-chart-bar"></i> Thống kê số lượng phiếu đã phát hành theo trung tâm - {{ $reportYear }}
        </h3>
        <span class="badge badge-primary">Tổng năm: {{ number_format($monthlyCertificateGrandTotal) }}</span>
    </div>

    <p class="px-3 pt-3 text-muted small mb-2">Bảng năm độc lập với khoảng ngày và trạng thái phía trên: tính cả năm theo ngày ký số, chỉ phiếu hiện tại đã phát hành; áp dụng Trung tâm đang chọn.</p>
    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th class="center-name">Trung tâm</th>
                    @for($month = 1; $month <= 12; $month++)
                        <th>T{{ $month }}</th>
                    @endfor
                    <th>Tổng</th>
                </tr>
            </thead>
            <tbody>
                @forelse($monthlyCertificateStats as $row)
                    <tr>
                        <td class="center-name">
                            <strong>{{ $row['center']->code }}</strong> - {{ $row['center']->name }}
                        </td>
                        @foreach($row['months'] as $month => $count)
                            <td>
                                @if($count)
                                    <a href="{{ route('quality-certificates.index', ['certificate_status' => 'ISSUED', 'date_basis' => 'signed_at', 'date_from' => \Carbon\Carbon::create($reportYear, $month, 1)->toDateString(), 'date_to' => \Carbon\Carbon::create($reportYear, $month, 1)->endOfMonth()->toDateString(), 'distribution_center_id' => $row['center']->id]) }}">{{ number_format($count) }}</a>
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                        <td class="total-cell">{{ number_format($row['total']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Không có dữ liệu phiếu đã phát hành trong năm {{ $reportYear }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th class="center-name">Tổng theo tháng</th>
                    @foreach($monthlyCertificateTotals as $count)
                        <th>{{ $count ? number_format($count) : '-' }}</th>
                    @endforeach
                    <th>{{ number_format($monthlyCertificateGrandTotal) }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-table"></i> Dữ liệu báo cáo</h3>
        <span class="badge badge-info">Tổng số: {{ number_format($requests->total()) }}</span>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0 report-table">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Số yêu cầu</th>
                    <th>Ngày tạo</th>
                    <th>Trung tâm</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Ngày xuất hàng</th>
                    <th>Số hóa đơn</th>
                    <th>Ký tươi</th>
                    <th>Số phiếu</th>
                    <th>Trạng thái yêu cầu</th>
                    <th>Trạng thái phiếu</th>
                    <th>Người tạo</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $item)
                    @php
                        $reportCertificate = $item->reportCertificate;
                    @endphp
                    <tr>
                        <td>{{ $requests->firstItem() + $loop->index }}</td>
                        <td>
                            <a href="{{ route('certificate-requests.show', $item) }}" class="report-request-no">
                                {{ $item->request_no }}
                            </a>
                        </td>
                        <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->distributionCenter->name ?? '-' }}</td>
                        <td class="report-customer">
                            <strong>{{ $item->customer->customer_name ?? '-' }}</strong>
                            @if($item->customer?->project_name)
                                <div class="text-muted small">{{ $item->customer->project_name }}</div>
                            @endif
                        </td>
                        <td>{{ $item->delivery_date ? $item->delivery_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $item->invoice_no ?: '-' }}</td>
                        <td>
                            @if($item->require_hard_copy)
                                <span class="badge badge-warning">{{ $item->hard_copy_quantity }} bản</span>
                            @else
                                <span class="badge badge-light">Không</span>
                            @endif
                        </td>
                        <td>
                            @if($reportCertificate)
                                <a href="{{ route('quality-certificates.show', $reportCertificate) }}" class="report-request-no">
                                    {{ $reportCertificate->certificate_no }}
                                </a>
                            @else
                                <span class="text-muted">Chưa lập</span>
                            @endif
                        </td>
                        <td>
                            @include('certificate_requests.partials.status_badge', ['status' => $item->status])
                        </td>
                        <td>
                            @if($reportCertificate)
                                @include('certificate_requests.partials.status_badge', ['qualityCertificate' => $reportCertificate])
                            @else
                                <span class="badge badge-light">Chưa lập phiếu</span>
                            @endif
                        </td>
                        <td>{{ $item->creator->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Không có dữ liệu báo cáo.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} / {{ number_format($requests->total()) }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $requests->links() }}</div>
            </div>
        </div>
    </div>
</div>
@stop
