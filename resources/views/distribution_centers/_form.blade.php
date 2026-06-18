@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Mã trung tâm <span class="text-danger">*</span></label>
            <input type="text"
                name="code"
                class="form-control @error('code') is-invalid @enderror"
                value="{{ old('code', $center->code ?? '') }}"
                placeholder="VD: HN, HCM, DN"
                required>

            @error('code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label>Tên trung tâm <span class="text-danger">*</span></label>
            <input type="text"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $center->name ?? '') }}"
                placeholder="VD: Trung tâm phân phối Hà Nội"
                required>

            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Email</label>
            <input type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email', $center->email ?? '') }}"
                placeholder="email@example.com">

            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Số điện thoại</label>
            <input type="text"
                name="phone"
                class="form-control @error('phone') is-invalid @enderror"
                value="{{ old('phone', $center->phone ?? '') }}"
                placeholder="VD: 0901234567">

            @error('phone')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Người liên hệ</label>
            <input type="text"
                name="contact_person"
                class="form-control @error('contact_person') is-invalid @enderror"
                value="{{ old('contact_person', $center->contact_person ?? '') }}"
                placeholder="Tên người phụ trách">

            @error('contact_person')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Địa chỉ</label>
    <textarea name="address"
        class="form-control @error('address') is-invalid @enderror"
        rows="3"
        placeholder="Nhập địa chỉ trung tâm">{{ old('address', $center->address ?? '') }}</textarea>

    @error('address')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox"
            name="is_active"
            value="1"
            class="custom-control-input"
            id="is_active"
            {{ old('is_active', $center->is_active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">
            Đang hoạt động
        </label>
    </div>
</div>

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ route('distribution-centers.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu dữ liệu
    </button>
</div>
