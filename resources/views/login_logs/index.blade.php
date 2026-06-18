@extends('adminlte::page')

@section('title', 'Nhật ký đăng nhập')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div>
    <h1 class="m-0">Nhật ký đăng nhập</h1>
    <small class="text-muted">Theo dõi lịch sử đăng nhập, đăng xuất và các lần đăng nhập thất bại</small>
</div>
@stop

@section('content')

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Bộ lọc
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
                           placeholder="Username, IP, trình duyệt...">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Người dùng</label>
                    <select name="user_id" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} - {{ $user->username }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Thành công</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Thất bại</option>
                        <option value="logout" {{ request('status') == 'logout' ? 'selected' : '' }}>Đăng xuất</option>
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

            <div class="col-12">
                <div class="form-group filter-actions mb-0">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                    <a href="{{ route('login-logs.index') }}" class="btn btn-secondary">
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
            <i class="fas fa-sign-in-alt"></i> Danh sách nhật ký đăng nhập
        </h3>

        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $logs->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">ID</th>
                    <th style="width:170px">Thời gian</th>
                    <th>Người dùng</th>
                    <th style="width:140px">Username</th>
                    <th style="width:130px">IP</th>
                    <th>Trình duyệt</th>
                    <th style="width:120px">Trạng thái</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>

            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ optional($log->logged_at)->format('d/m/Y H:i:s') }}</td>
                        <td>{{ $log->user->name ?? '-' }}</td>
                        <td>{{ $log->username }}</td>
                        <td>{{ $log->ip_address }}</td>
                        <td class="text-muted small">{{ $log->user_agent }}</td>
                        <td>
                            @if($log->status === 'success')
                                <span class="badge badge-success">Thành công</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-danger">Thất bại</span>
                            @else
                                <span class="badge badge-secondary">Đăng xuất</span>
                            @endif
                        </td>
                        <td>{{ $log->message }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Chưa có nhật ký đăng nhập.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $logs->links() }}
    </div>
</div>

@stop
