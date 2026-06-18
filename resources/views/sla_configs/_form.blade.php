<div class="row">

    <div class="col-md-4">
        <div class="form-group">
            <label>Mã SLA <span class="text-danger">*</span></label>

            <input type="text"
                   name="code"
                   class="form-control @error('code') is-invalid @enderror"
                   value="{{ old('code', $slaConfig->code ?? '') }}"
                   required>

            @error('code')
            <span class="invalid-feedback">
                {{ $message }}
            </span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="form-group">
            <label>Tên SLA <span class="text-danger">*</span></label>

            <input type="text"
                   name="name"
                   class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $slaConfig->name ?? '') }}"
                   required>

            @error('name')
            <span class="invalid-feedback">
                {{ $message }}
            </span>
            @enderror
        </div>
    </div>

</div>

<div class="row">

    <div class="col-md-4">
        <div class="form-group">
            <label>Công đoạn <span class="text-danger">*</span></label>

            <select name="process_step"
                    class="form-control select2"
                    required>

                @foreach($processSteps as $key => $value)
                    <option value="{{ $key }}"
                        {{ old('process_step', $slaConfig->process_step ?? '') == $key ? 'selected' : '' }}>
                        {{ $value }}
                    </option>
                @endforeach

            </select>
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Cảnh báo (phút)</label>

            <input type="number"
                   min="0"
                   name="warning_minutes"
                   class="form-control"
                   value="{{ old('warning_minutes', $slaConfig->warning_minutes ?? 0) }}">
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Quá hạn (phút)</label>

            <input type="number"
                   min="1"
                   name="limit_minutes"
                   class="form-control"
                   value="{{ old('limit_minutes', $slaConfig->limit_minutes ?? 0) }}">
        </div>
    </div>

</div>

<div class="form-group">
    <label>Mô tả</label>

    <textarea name="description"
              rows="4"
              class="form-control">{{ old('description', $slaConfig->description ?? '') }}</textarea>
</div>

<div class="form-group">
    <div class="custom-control custom-switch">
        <input type="checkbox"
               class="custom-control-input"
               id="is_active"
               name="is_active"
               value="1"
               {{ old('is_active', $slaConfig->is_active ?? true) ? 'checked' : '' }}>

        <label class="custom-control-label" for="is_active">
            Đang sử dụng
        </label>
    </div>
</div>