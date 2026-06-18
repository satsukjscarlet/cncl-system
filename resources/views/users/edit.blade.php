@extends('adminlte::page')

@section('title', 'Cập nhật người dùng')

@section('content_header')
<div>
    <h1 class="m-0">Cập nhật người dùng</h1>
    <small class="text-muted">{{ $user->username }} - {{ $user->name }}</small>
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

<div class="row">
    <div class="col-md-8">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-edit"></i> Thông tin tài khoản
                </h3>
            </div>

            <form method="POST" action="{{ route('users.update', $user) }}">
                @method('PUT')
                <div class="card-body">
                    @include('users._form')
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-key"></i> Reset mật khẩu
                </h3>
            </div>

            <form method="POST" action="{{ route('users.reset-password', $user) }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="password"
                            class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>

                    <div class="alert alert-warning">
                        Sau khi reset, người dùng sẽ đăng nhập bằng mật khẩu mới.
                    </div>
                </div>

                <div class="card-footer">
                    <button class="btn btn-danger" onclick="return confirm('Reset mật khẩu tài khoản này?')">
                        <i class="fas fa-key"></i> Reset mật khẩu
                    </button>
                </div>
            </form>
        </div>

        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Thông tin nhanh
                </h3>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> {{ $user->id }}</p>
                <p><strong>Ngày tạo:</strong> {{ optional($user->created_at)->format('d/m/Y H:i') }}</p>
                <p><strong>Cập nhật:</strong> {{ optional($user->updated_at)->format('d/m/Y H:i') }}</p>
                <p>
                    <strong>Vai trò hiện tại:</strong>
                    @foreach($user->roles as $role)
                        <span class="badge badge-info">{{ $role->name }}</span>
                    @endforeach
                </p>
                <p>
                    <strong>Trạng thái:</strong>
                    @if($user->is_active)
                        <span class="badge badge-success">Hoạt động</span>
                    @else
                        <span class="badge badge-danger">Khóa</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
</div>
@stop
