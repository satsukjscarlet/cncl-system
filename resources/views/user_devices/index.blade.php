@extends('adminlte::page')

@section('title', 'Thiết bị đăng nhập')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div>
    <h1 class="m-0">Thiết bị đăng nhập</h1>
    <small class="text-muted">Quản lý các máy tính/trình duyệt được phép đăng nhập tài khoản Trung tâm</small>
</div>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Bộ lọc
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
                           placeholder="Tên tài khoản, thiết bị, IP, trình duyệt...">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Người dùng</label>
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
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Đã khóa</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group filter-actions mb-0">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                    <a href="{{ route('user-devices.index') }}" class="btn btn-secondary">
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
            <i class="fas fa-laptop"></i> Danh sách thiết bị
        </h3>

        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $devices->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">ID</th>
                    <th style="width:210px">Người dùng</th>
                    <th>Thiết bị</th>
                    <th style="width:130px">IP</th>
                    <th style="width:120px">Trạng thái</th>
                    <th style="width:170px">Thời gian</th>
                    <th style="width:260px">Lịch sử gần nhất</th>
                    <th style="width:260px" class="text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse($devices as $device)
                    <tr>
                        <td>{{ $device->id }}</td>
                        <td>
                            <strong>{{ $device->user->name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $device->user->username ?? '' }}</div>
                            <div class="small">
                                {{ $device->user?->roles?->pluck('name')->implode(', ') }}
                            </div>
                        </td>
                        <td>
                            <strong>{{ $device->device_name ?: 'Chưa đặt tên thiết bị' }}</strong>
                            <div class="text-muted small">Mã: {{ $device->device_uid }}</div>
                            <div class="text-muted small mt-1">{{ $device->user_agent }}</div>
                        </td>
                        <td>{{ $device->ip_address ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $device->statusBadgeClass() }}">
                                {{ $device->statusLabel() }}
                            </span>
                        </td>
                        <td class="small">
                            <div>Yêu cầu: {{ optional($device->requested_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            <div>Duyệt: {{ optional($device->approved_at)->format('d/m/Y H:i') ?: '-' }}</div>
                            <div>Dùng cuối: {{ optional($device->last_used_at)->format('d/m/Y H:i') ?: '-' }}</div>
                        </td>
                        <td class="small">
                            @forelse(($deviceLogs[$device->id] ?? collect()) as $log)
                                <div class="mb-2">
                                    <div class="font-weight-bold">{{ $log->description }}</div>
                                    <div class="text-muted">
                                        {{ optional($log->created_at)->format('d/m/Y H:i') }}
                                        @if($log->causer)
                                            - {{ $log->causer->name ?? $log->causer->username }}
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <span class="text-muted">Chưa có lịch sử thao tác.</span>
                            @endforelse
                        </td>
                        <td class="text-center">
                            @if($device->status !== \App\Models\UserDevice::STATUS_APPROVED)
                                <button type="button"
                                        class="btn btn-sm btn-success mb-1"
                                        data-toggle="modal"
                                        data-target="#approveDevice{{ $device->id }}">
                                    <i class="fas fa-check"></i> Duyệt
                                </button>
                            @endif

                            @if($device->status !== \App\Models\UserDevice::STATUS_BLOCKED)
                                <form method="POST"
                                      action="{{ route('user-devices.block', $device) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('Khóa thiết bị này? Người dùng sẽ không đăng nhập được bằng thiết bị này nữa.');">
                                    @csrf
                                    <button class="btn btn-sm btn-warning mb-1">
                                        <i class="fas fa-ban"></i> Khóa
                                    </button>
                                </form>
                            @endif

                            <form method="POST"
                                  action="{{ route('user-devices.destroy', $device) }}"
                                  class="d-inline"
                                  onsubmit="return confirm('Xóa thiết bị này khỏi danh sách quản lý?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger mb-1">
                                    <i class="fas fa-trash"></i> Xóa
                                </button>
                            </form>

                            <div class="modal fade" id="approveDevice{{ $device->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST"
                                          action="{{ route('user-devices.approve', $device) }}"
                                          class="modal-content text-left">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-check-circle"></i> Duyệt thiết bị đăng nhập
                                            </h5>
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="mb-2">
                                                Tài khoản:
                                                <strong>{{ $device->user->name ?? '-' }} - {{ $device->user->username ?? '' }}</strong>
                                            </p>
                                            <div class="form-group">
                                                <label>Tên thiết bị</label>
                                                <input type="text"
                                                       name="device_name"
                                                       class="form-control"
                                                       value="{{ $device->device_name }}"
                                                       placeholder="Ví dụ: Máy kế toán Công ty A">
                                            </div>
                                            <div class="alert alert-info mb-0">
                                                Sau khi duyệt, tài khoản này được đăng nhập trên trình duyệt/máy đang yêu cầu.
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                            <button class="btn btn-success">
                                                <i class="fas fa-check"></i> Duyệt thiết bị
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Chưa có thiết bị đăng nhập nào.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $devices->links() }}
    </div>
</div>

@stop
