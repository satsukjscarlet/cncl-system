@extends('adminlte::page')

@section('title', 'Tài khoản của tôi')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-2') }}">
@stop

@section('content_header')
<div>
    <h1 class="m-0">Tài khoản của tôi</h1>
    <small class="text-muted">Cập nhật thông tin cá nhân và tự đổi mật khẩu đăng nhập</small>
</div>
@stop

@section('content')
@if (session('status') === 'profile-updated')
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Đã cập nhật thông tin tài khoản.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if (session('status') === 'password-updated')
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> Đã đổi mật khẩu.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-circle"></i> Thông tin tài khoản
                </h3>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" class="cncl-form">
                @csrf
                @method('PATCH')

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tên đăng nhập</label>
                                <input type="text" class="form-control" value="{{ $user->username }}" disabled>
                                <small class="text-muted">Tên đăng nhập do quản trị viên quản lý.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vai trò</label>
                                <input type="text" class="form-control"
                                    value="{{ $user->roles->pluck('name')->implode(', ') ?: '-' }}" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Họ tên <span class="text-danger">*</span></label>
                                <input type="text" name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email', $user->email) }}">
                                @error('email')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Email dùng để liên hệ nội bộ, có thể để trống.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SmartCA User ID</label>
                                <input type="text" name="smartca_user_id"
                                    class="form-control @error('smartca_user_id') is-invalid @enderror"
                                    value="{{ old('smartca_user_id', $user->smartca_user_id) }}"
                                    placeholder="CCCD/MST/Số điện thoại SmartCA">
                                @error('smartca_user_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="text-muted">Dùng cho tài khoản có nghiệp vụ ký số VNPT SmartCA.</small>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Trung tâm phân phối</label>
                                <input type="text" class="form-control"
                                    value="{{ $user->distributionCenter ? $user->distributionCenter->code . ' - ' . $user->distributionCenter->name : '-' }}"
                                    disabled>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <button class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thông tin
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-key"></i> Đổi mật khẩu
                </h3>
            </div>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('PUT')

                <div class="card-body">
                    <div class="form-group">
                        <label>Mật khẩu hiện tại <span class="text-danger">*</span></label>
                        <input type="password" name="current_password"
                            class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                            autocomplete="current-password" required>
                        @error('current_password', 'updatePassword')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="password"
                            class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                            autocomplete="new-password" required>
                        @error('password', 'updatePassword')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="text-muted">Nên dùng tối thiểu 8 ký tự và không dùng lại mật khẩu cũ.</small>
                    </div>

                    <div class="form-group">
                        <label>Nhập lại mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control"
                            autocomplete="new-password" required>
                    </div>
                </div>

                <div class="card-footer text-right">
                    <button class="btn btn-warning">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </button>
                </div>
            </form>
        </div>

        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Thông tin đăng nhập
                </h3>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Trạng thái:</strong>
                    @if($user->is_active)
                        <span class="badge badge-success">Hoạt động</span>
                    @else
                        <span class="badge badge-danger">Đã khóa</span>
                    @endif
                </p>
                <p class="mb-2"><strong>Ngày tạo:</strong> {{ optional($user->created_at)->format('d/m/Y H:i') }}</p>
                <p class="mb-0"><strong>Cập nhật gần nhất:</strong> {{ optional($user->updated_at)->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
@stop
