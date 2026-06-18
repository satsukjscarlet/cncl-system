@extends('adminlte::page')

@section('title', 'Báo cáo tổng hợp CNCL')

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
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
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalRequests }}</h3>
                <p>Tổng yêu cầu</p>
            </div>
            <div class="icon"><i class="fas fa-file-alt"></i></div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $completedRequests }}</h3>
                <p>Hoàn tất</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $warningCount }}</h3>
                <p>Gần quá hạn SLA</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $overdueCount }}</h3>
                <p>Quá hạn SLA</p>
            </div>
            <div class="icon"><i class="fas fa-fire"></i></div>
        </div>
    </div>
</div>

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc báo cáo</h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Từ ngày tạo</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Đến ngày tạo</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Trung tâm</label>
                    <select name="distribution_center_id" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                {{ $center->code }} - {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="WAIT_DVKH" {{ request('status') == 'WAIT_DVKH' ? 'selected' : '' }}>Chờ DVKH</option>
                        <option value="WAIT_PTN" {{ request('status') == 'WAIT_PTN' ? 'selected' : '' }}>Chờ PTN</option>
                        <option value="PTN_PROCESSING" {{ request('status') == 'PTN_PROCESSING' ? 'selected' : '' }}>PTN xử lý</option>
                        <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Hoàn tất</option>
                        <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Hủy/Trả lại</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-12">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Lọc</button>
                    <a href="{{ route('reports.summary') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-table"></i> Dữ liệu báo cáo</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $requests->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
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
                        <td><strong>{{ $item->request_no }}</strong></td>
                        <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $item->distributionCenter->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $item->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $item->customer->project_name ?? '' }}</div>
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
                            @include('certificate_requests.partials.status_badge', ['status' => $item->status])
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
                Hiển thị {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} / {{ $requests->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $requests->links() }}</div>
            </div>
        </div>
    </div>
</div>
@stop
