@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Trung tâm phân phối <span class="text-danger">*</span></label>
            <select name="distribution_center_id" class="form-control select2 @error('distribution_center_id') is-invalid @enderror" required>
                <option value="">-- Chọn trung tâm --</option>
                @foreach($centers as $center)
                    <option value="{{ $center->id }}" {{ old('distribution_center_id', $salesUnit->distribution_center_id ?? '') == $center->id ? 'selected' : '' }}>
                        {{ $center->code }} - {{ $center->name }}
                    </option>
                @endforeach
            </select>
            @error('distribution_center_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Mã Bravo <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                   value="{{ old('code', $salesUnit->code ?? '') }}" required>
            @error('code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Tên DVBH <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $salesUnit->name ?? '') }}" required>
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Địa chỉ cửa hàng</label>
    <textarea name="address" class="form-control" rows="2">{{ old('address', $salesUnit->address ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Số ĐT liên lạc</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $salesUnit->phone ?? '') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Mã số thuế</label>
            <input type="text" name="tax_code" class="form-control" value="{{ old('tax_code', $salesUnit->tax_code ?? '') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Số tài khoản</label>
            <input type="text" name="bank_account" class="form-control" value="{{ old('bank_account', $salesUnit->bank_account ?? '') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Đại diện - chức vụ</label>
            <input type="text" name="representative" class="form-control" value="{{ old('representative', $salesUnit->representative ?? '') }}">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Ghi chú</label>
    <textarea name="note" class="form-control" rows="2">{{ old('note', $salesUnit->note ?? '') }}</textarea>
</div>

<div class="custom-control custom-switch mb-3">
    <input type="checkbox" name="is_active" value="1" class="custom-control-input" id="is_active"
           {{ old('is_active', $salesUnit->is_active ?? true) ? 'checked' : '' }}>
    <label class="custom-control-label" for="is_active">Đang sử dụng</label>
</div>

<div class="d-flex justify-content-end">
    <a href="{{ route('sales-units.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu
    </button>
</div>
