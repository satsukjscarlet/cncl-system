@extends('adminlte::page')

@section('title', 'Lịch sử in ký tươi')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Lịch sử in phiếu ký tươi</h1>
        <small class="text-muted">Theo dõi và kiểm soát các lần in bản giấy phục vụ ký tươi</small>
    </div>
</div>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Bộ lọc dữ liệu
        </h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           value="{{ request('keyword') }}"
                           placeholder="Số phiếu, số yêu cầu, khách hàng, công trình, lý do in">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Người in</label>
                    <select name="user_id"
                            class="form-control select2"
                            data-ajax-url="{{ route('users.options') }}"
                            data-minimum-input-length="1">
                        <option value="">Tất cả</option>
                        @foreach($selectedUsers as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} - {{ $user->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Từ ngày</label>
                    <input type="date"
                           name="date_from"
                           class="form-control"
                           value="{{ request('date_from') }}">
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Đến ngày</label>
                    <input type="date"
                           name="date_to"
                           class="form-control"
                           value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="col-lg-1 col-md-12">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary" title="Tìm">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('print-logs.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title">
            <i class="fas fa-print"></i> Danh sách lịch sử in
        </h3>

        <div class="card-tools">
            <span class="badge badge-info">
                Tổng số: {{ $logs->total() }}
            </span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Số phiếu</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Trung tâm</th>
                    <th style="width:90px">Lần in</th>
                    <th>Người in</th>
                    <th>Lý do</th>
                    <th style="width:150px">Thời gian</th>
                    <th style="width:90px" class="text-center">Xem</th>
                </tr>
            </thead>

            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $logs->firstItem() + $loop->index }}</td>

                        <td>
                            <strong>{{ $log->certificate->certificate_no ?? '-' }}</strong>
                            <div class="text-muted small">
                                YC: {{ $log->certificate->request->request_no ?? '-' }}
                            </div>
                        </td>

                        <td>
                            <strong>{{ $log->certificate->request->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">
                                {{ $log->certificate->request->customer->project_name ?? '' }}
                            </div>
                        </td>

                        <td>{{ $log->certificate->request->distributionCenter->name ?? '-' }}</td>

                        <td>
                            <span class="badge badge-warning">
                                Lần {{ $log->print_no }}
                            </span>
                        </td>

                        <td>
                            {{ $log->user->name ?? '-' }}
                            <div class="text-muted small">
                                {{ $log->user->username ?? '' }}
                            </div>
                        </td>

                        <td>{{ $log->reason }}</td>

                        <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>

                        <td class="text-center">
                            @if($log->certificate)
                                <a href="{{ route('quality-certificates.show', $log->certificate) }}"
                                   class="btn btn-sm btn-info"
                                   title="Xem phiếu">
                                    <i class="fas fa-eye"></i>
                                </a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <br>
                            Chưa có lịch sử in ký tươi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}
                / {{ $logs->total() }} bản ghi
            </div>

            <div class="col-md-6">
                <div class="float-md-right">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@stop
