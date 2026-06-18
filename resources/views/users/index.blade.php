@extends('adminlte::page')

@section('title', 'Quản lý người dùng')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Quản lý người dùng</h1>
        <small class="text-muted">Quản lý tài khoản, vai trò và trạng thái truy cập hệ thống</small>
    </div>

    <div class="btn-group mt-2 mt-md-0">
        @can('role_permission.manage')
            <a href="{{ route('role-permissions.index') }}" class="btn btn-outline-info">
                <i class="fas fa-user-shield"></i> Phân quyền
            </a>
        @endcan

        @can('user.create')
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm người dùng
            </a>
        @endcan
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
        <i class="fas fa-times-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control"
                        placeholder="Tên, username, email..." value="{{ request('keyword') }}">
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Trung tâm</label>
                    <select name="distribution_center_id" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                {{ $center->name }}
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
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Khóa</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-1 col-md-12">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary" title="Tìm">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-users"></i> Danh sách người dùng</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $users->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">ID</th>
                    <th>Họ tên</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trung tâm</th>
                    <th style="width:120px">Trạng thái</th>
                    <th style="width:170px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email ?: '-' }}</td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge badge-info">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>{{ $user->distributionCenter->name ?? '-' }}</td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-success">Hoạt động</span>
                            @else
                                <span class="badge badge-danger">Khóa</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('user.update')
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-warning btn-sm" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan

                            @can('user.toggle_active')
                                <form action="{{ route('users.toggle-active', $user) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm" title="Khóa/mở khóa">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                </form>
                            @endcan

                            @can('user.delete')
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm"
                                        onclick="return confirm('Xóa tài khoản này?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Không có dữ liệu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        {{ $users->links() }}
    </div>
</div>
@stop
