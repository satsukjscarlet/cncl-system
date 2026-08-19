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
<div class="row report-kpi">
    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($totalRequests) }}</h3>
                <p>Tổng yêu cầu</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($completedRequests) }}</h3>
                <p>Hoàn tất</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($certificateCount) }}</h3>
                <p>Phiếu đã phát hành</p>
            </div>
            <div class="icon"><i class="fas fa-certificate"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ number_format($cancelledRequests) }}</h3>
                <p>Đã hủy / trả lại</p>
            </div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($warningCount) }}</h3>
                <p>Gần quá hạn SLA</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>

    <div class="col-lg-2 col-md-4 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($overdueCount) }}</h3>
                <p>Quá hạn SLA</p>
            </div>
            <div class="icon"><i class="fas fa-fire"></i></div>
        </div>
    </div>
</div>

<div class="card card-primary card-outline filter-card">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0"><i class="fas fa-filter"></i> Bộ lọc báo cáo</h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Từ ngày tạo</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
            </div>

            <div class="col-xl-2 col-md-6">
                <div class="form-group">
                    <label>Đến ngày tạo</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>

            @if($canViewAllCenters)
                <div class="col-xl-3 col-md-6">
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

            <div class="{{ $canViewAllCenters ? 'col-xl-3' : 'col-xl-6' }} col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả trạng thái</option>
                        @foreach($statusOptions as $status => $label)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
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
                    <th>Trạng thái</th>
                    <th>Người tạo</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $item)
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
                            @include('certificate_requests.partials.status_badge', ['certificateRequest' => $item])
                        </td>
                        <td>{{ $item->creator->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
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
