@csrf

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Nhóm sản phẩm <span class="text-danger">*</span></label>
            <select name="product_group_id" class="form-control select2" required>
                <option value="">-- Chọn nhóm sản phẩm --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}"
                        {{ old('product_group_id', $product->product_group_id ?? '') == $group->id ? 'selected' : '' }}>
                        {{ $group->code }} - {{ $group->name }}
                    </option>
                @endforeach
            </select>
            @error('product_group_id')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Mã sản phẩm <span class="text-danger">*</span></label>
            <input type="text"
                   name="product_code"
                   class="form-control @error('product_code') is-invalid @enderror"
                   value="{{ old('product_code', $product->product_code ?? '') }}"
                   required>
            @error('product_code')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Đơn vị tính</label>
            <input type="text"
                   name="unit"
                   class="form-control"
                   value="{{ old('unit', $product->unit ?? '') }}"
                   placeholder="VD: Cái, Mét, Bộ">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Tên sản phẩm <span class="text-danger">*</span></label>
    <input type="text"
           name="product_name"
           class="form-control @error('product_name') is-invalid @enderror"
           value="{{ old('product_name', $product->product_name ?? '') }}"
           required>
    @error('product_name')
        <span class="invalid-feedback">{{ $message }}</span>
    @enderror
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Kích thước danh nghĩa</label>
            <input type="text"
                   name="nominal_size"
                   class="form-control"
                   value="{{ old('nominal_size', $product->nominal_size ?? '') }}"
                   placeholder="VD: DN110, Ø110x5.3, 110 mm">
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Tiêu chuẩn sản phẩm</label>
            <select name="quality_standard_id" class="form-control select2">
                <option value="">-- Chọn tiêu chuẩn --</option>
                @foreach($standards as $standard)
                    <option value="{{ $standard->id }}"
                        {{ old('quality_standard_id', $product->quality_standard_id ?? '') == $standard->id ? 'selected' : '' }}>
                        {{ $standard->code }} - {{ $standard->name }}
                    </option>
                @endforeach
            </select>
            @error('quality_standard_id')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Mẫu phiếu</label>
            <input type="text"
                   name="certificate_template"
                   class="form-control"
                   value="{{ old('certificate_template', $product->certificate_template ?? '') }}"
                   placeholder="VD: PVC, HDPE, PPR">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Yêu cầu kỹ thuật</label>
    <textarea name="technical_requirements"
              class="form-control"
              rows="3"
              placeholder="Cho phép nhập chữ, số, ký hiệu, dấu Ø, /, -, ...">{{ old('technical_requirements', $product->technical_requirements ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Loại phiếu CNCL</label>
            <select name="certificate_type" class="form-control select2">
                <option value="">-- Chọn loại phiếu --</option>
                <option value="CNCL" {{ old('certificate_type', $product->certificate_type ?? '') == 'CNCL' ? 'selected' : '' }}>
                    Phiếu chứng nhận chất lượng
                </option>
            </select>
        </div>
    </div>

    <div class="col-md-6">
        <label>Trạng thái</label>
        <div class="custom-control custom-switch mt-2">
            <input type="checkbox"
                   name="is_active"
                   value="1"
                   class="custom-control-input"
                   id="is_active"
                   {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">Đang sử dụng</label>
        </div>
    </div>
</div>

<div class="form-group">
    <label>Ghi chú</label>
    <textarea name="note"
              class="form-control"
              rows="3">{{ old('note', $product->note ?? '') }}</textarea>
</div>

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ route('products.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu dữ liệu
    </button>
</div>