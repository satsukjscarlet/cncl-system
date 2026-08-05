@csrf

<div class="form-group">
    <label>Trung tâm phân phối</label>
    @if(auth()->user()->hasRole('TrungTam'))
        <input type="text"
               class="form-control"
               value="{{ auth()->user()->distributionCenter->code ?? '' }} - {{ auth()->user()->distributionCenter->name ?? '' }}"
               readonly>
    @else
        <select name="distribution_center_id" class="form-control select2">
            <option value="">-- Dùng chung / chưa gán trung tâm --</option>
            @foreach(($centers ?? collect()) as $center)
                <option value="{{ $center->id }}"
                    {{ old('distribution_center_id', $customer->distribution_center_id ?? '') == $center->id ? 'selected' : '' }}>
                    {{ $center->code }} - {{ $center->name }}
                </option>
            @endforeach
        </select>
    @endif
    @error('distribution_center_id')
        <span class="text-danger small">{{ $message }}</span>
    @enderror
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Mã khách hàng</label>
            <input type="text"
                   name="customer_code"
                   class="form-control @error('customer_code') is-invalid @enderror"
                   value="{{ old('customer_code', $customer->customer_code ?? '') }}"
                   placeholder="VD: KH001">
            @error('customer_code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label>Tên khách hàng <span class="text-danger">*</span></label>
            <input type="text"
                   name="customer_name"
                   class="form-control @error('customer_name') is-invalid @enderror"
                   value="{{ old('customer_name', $customer->customer_name ?? '') }}"
                   required>
            @error('customer_name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Địa chỉ khách hàng</label>
    <textarea name="customer_address"
              class="form-control"
              rows="2">{{ old('customer_address', $customer->customer_address ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>Mã số thuế</label>
            <input type="text"
                   name="tax_code"
                   class="form-control"
                   value="{{ old('tax_code', $customer->tax_code ?? '') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Người liên hệ</label>
            <input type="text"
                   name="contact_person"
                   class="form-control"
                   value="{{ old('contact_person', $customer->contact_person ?? '') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Điện thoại</label>
            <input type="text"
                   name="phone"
                   class="form-control"
                   value="{{ old('phone', $customer->phone ?? '') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            <label>Email nhận phiếu</label>
            <input type="email"
                   name="email"
                   class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $customer->email ?? '') }}">
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<hr>

<div class="form-group">
    <label>Tên công trình</label>
    <input type="text"
           name="project_name"
           class="form-control"
           value="{{ old('project_name', $customer->project_name ?? '') }}">
</div>

<div class="form-group">
    <label>Địa điểm công trình</label>
    <textarea name="project_address"
              class="form-control"
              rows="2">{{ old('project_address', $customer->project_address ?? '') }}</textarea>
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox"
               name="is_active"
               value="1"
               class="custom-control-input"
               id="is_active"
               {{ old('is_active', $customer->is_active ?? true) ? 'checked' : '' }}>
        <label class="custom-control-label" for="is_active">
            Đang sử dụng
        </label>
    </div>
</div>

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ route('customers.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu dữ liệu
    </button>
</div>
