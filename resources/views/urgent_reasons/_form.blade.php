@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Mã lý do <span class="text-danger">*</span></label>
            <input type="text"
                   name="code"
                   class="form-control @error('code') is-invalid @enderror"
                   value="{{ old('code', $urgentReason->code ?? '') }}"
                   placeholder="VD: KHACH_CAN_GAP"
                   required>
            @error('code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label>Tên lý do <span class="text-danger">*</span></label>
            <input type="text"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $urgentReason->name ?? '') }}"
                   placeholder="VD: Khách hàng cần phiếu gấp"
                   required>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Mô tả</label>
    <textarea name="description"
              class="form-control"
              rows="4"
              placeholder="Nhập mô tả nếu có">{{ old('description', $urgentReason->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox"
               name="is_active"
               value="1"
               class="custom-control-input"
               id="is_active"
               {{ old('is_active', $urgentReason->is_active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">
            Đang sử dụng
        </label>
    </div>
</div>

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ route('urgent-reasons.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu dữ liệu
    </button>
</div>
