@csrf

<input type="hidden" name="certificate_type" value="{{ old('certificate_type', $product->certificate_type ?? 'CNCL') }}">
<input type="hidden" name="certificate_template" value="{{ old('certificate_template', $product->certificate_template ?? 'default') }}">

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Nhóm sản phẩm <span class="text-danger">*</span></label>
            <select id="product_group_id" name="product_group_id" class="form-control select2" required>
                <option value="">-- Chọn nhóm sản phẩm --</option>
                @foreach($groups as $group)
                    <option value="{{ $group->id }}" data-default-standard="{{ $standards->contains(fn ($standard) => $standard->id == $group->default_quality_standard_id && $standard->is_active) ? $group->default_quality_standard_id : '' }}"
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
            <label>Đơn vị tính <span class="text-danger">*</span></label>
            <select name="unit_id" class="form-control select2 @error('unit_id') is-invalid @enderror" required>
                <option value="">-- Chọn đơn vị tính --</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}"
                        {{ old('unit_id', $product->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                        {{ $unit->code }} - {{ $unit->name }}
                    </option>
                @endforeach
            </select>
            @error('unit_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            <small class="text-muted">Đơn vị tính được quản lý trong danh mục riêng để dữ liệu đồng nhất.</small>
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
            <select id="quality_standard_id" name="quality_standard_id" class="form-control select2">
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

@push('js')
<script>
    $(function () {
        const group = $('#product_group_id');
        const standard = $('#quality_standard_id');
        const defaultForGroup = () => String(group.find(':selected').attr('data-default-standard') || '');
        let previousDefault = defaultForGroup();
        let manuallySelected = @json(isset($product) || session()->hasOldInput());
        let applyingDefault = false;

        function applyDefault(value) {
            applyingDefault = true;
            standard.val(value).trigger('change');
            applyingDefault = false;
        }

        standard.on('change.groupDefault', function () {
            if (!applyingDefault) manuallySelected = true;
        });
        group.on('change.groupDefault', function () {
            const current = String(standard.val() || '');
            const nextDefault = defaultForGroup();
            if (!manuallySelected || (current !== '' && current === previousDefault)) {
                applyDefault(nextDefault);
                manuallySelected = false;
            }
            previousDefault = nextDefault;
        });
        if (!manuallySelected && !standard.val()) applyDefault(previousDefault);
    });
</script>
@endpush

<div class="form-group">
    <label>Yêu cầu kỹ thuật</label>
    <textarea name="technical_requirements"
              class="form-control"
              rows="3"
              placeholder="Cho phép nhập chữ, số, ký hiệu, dấu Ø, /, -, ...">{{ old('technical_requirements', $product->technical_requirements ?? '') }}</textarea>
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
