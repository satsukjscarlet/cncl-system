@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Họ tên <span class="text-danger">*</span></label>
            <input type="text" name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user->name ?? '') }}" required>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Username <span class="text-danger">*</span></label>
            <input type="text" name="username"
                class="form-control @error('username') is-invalid @enderror"
                value="{{ old('username', $user->username ?? '') }}" required>
            @error('username')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $user->email ?? '') }}">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-group">
            <label>Vai trò <span class="text-danger">*</span></label>
            <select name="role" class="form-control @error('role') is-invalid @enderror select2" required>
                <option value="">-- Chọn vai trò --</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}"
                        {{ old('role', isset($user) ? $user->roles->first()?->name : '') == $role->name ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Trung tâm phân phối</label>
            <select name="distribution_center_id" class="form-control select2">
                <option value="">-- Không gán trung tâm --</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}"
                        {{ old('distribution_center_id', $user->distribution_center_id ?? '') == $center->id ? 'selected' : '' }}>
                        {{ $center->code }} - {{ $center->name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">
                Chỉ cần chọn khi tài khoản thuộc nhóm Trung tâm phân phối.
            </small>
        </div>
    </div>

    <div class="col-md-6">
        <label>Trạng thái</label>
        <div class="custom-control custom-switch mt-2">
            <input type="checkbox" name="is_active" value="1"
                class="custom-control-input" id="is_active"
                {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">
                Tài khoản đang hoạt động
            </label>
        </div>
    </div>
</div>

@if(!isset($user))
    <hr>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Mật khẩu <span class="text-danger">*</span></label>
                <input type="password" name="password"
                    class="form-control @error('password') is-invalid @enderror" required>
                @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label>Nhập lại mật khẩu <span class="text-danger">*</span></label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
        </div>
    </div>
@endif

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ route('users.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu dữ liệu
    </button>
</div>
