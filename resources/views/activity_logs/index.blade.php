@extends('adminlte::page')

@section('title', 'Nhật ký hệ thống')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Nhật ký hệ thống</h1>
        <small class="text-muted">Theo dõi thao tác người dùng trong hệ thống CNCL</small>
    </div>
</div>
@stop

@section('content')

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Bộ lọc nhật ký
        </h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           value="{{ request('keyword') }}"
                           placeholder="Nội dung, module, model...">
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Module</label>
                    <select name="log_name" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($logNames as $logName)
                            <option value="{{ $logName }}" {{ request('log_name') == $logName ? 'selected' : '' }}>
                                {{ $logName ?: 'Không xác định' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Người thao tác</label>
                    <select name="causer_id" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('causer_id') == $user->id ? 'selected' : '' }}>
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

            <div class="col-lg-2 col-md-12">
                <div class="form-group">
                    <label>Đến ngày</label>
                    <input type="date"
                           name="date_to"
                           class="form-control"
                           value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="col-12">
                <div class="form-group filter-actions mb-0">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-secondary">
                        <i class="fas fa-sync"></i> Làm mới
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title">
            <i class="fas fa-history"></i> Danh sách nhật ký
        </h3>

        <div class="card-tools">
            <span class="badge badge-info">
                Tổng số: {{ $logs->total() }}
            </span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">ID</th>
                    <th style="width:160px">Thời gian</th>
                    <th style="width:160px">Module</th>
                    <th>Diễn giải</th>
                    <th style="width:220px">Người thao tác</th>
                    <th style="width:180px">Đối tượng</th>
                    <th style="width:90px" class="text-center">Xem</th>
                </tr>
            </thead>

            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>

                        <td>{{ optional($log->created_at)->format('d/m/Y H:i:s') }}</td>

                        <td>
                            <span class="badge badge-primary">
                                {{ $log->log_name ?: 'default' }}
                            </span>
                        </td>

                        <td>
                            <strong>{{ $log->description }}</strong>

                            @if($log->properties && $log->properties->count())
                                <div class="text-muted small mt-1">
                                    Có dữ liệu chi tiết
                                </div>
                            @endif
                        </td>

                        <td>
                            @if($log->causer)
                                {{ $log->causer->name ?? '-' }}
                                <div class="text-muted small">
                                    {{ $log->causer->username ?? '' }}
                                </div>
                            @else
                                <span class="text-muted">Hệ thống</span>
                            @endif
                        </td>

                        <td>
                            @if($log->subject_type)
                                <span class="text-muted small">
                                    {{ class_basename($log->subject_type) }}
                                    #{{ $log->subject_id }}
                                </span>
                            @else
                                -
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('activity-logs.show', $log) }}"
                               class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <br>
                            Chưa có nhật ký hệ thống.
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
