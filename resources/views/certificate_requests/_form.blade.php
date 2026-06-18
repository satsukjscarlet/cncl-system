@csrf

@php
    $customerMode = old('customer_mode', 'existing');
    $selectedCustomerId = old('customer_id', $certificateRequest->customer_id ?? '');
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Trung tâm phân phối <span class="text-danger">*</span></label>

            @if (auth()->user()->hasRole('TrungTam'))
                <input type="text" class="form-control"
                       value="{{ auth()->user()->distributionCenter->code ?? '' }} - {{ auth()->user()->distributionCenter->name ?? '' }}"
                       readonly>
                <input type="hidden" name="distribution_center_id" value="{{ auth()->user()->distribution_center_id }}">
            @else
                <select name="distribution_center_id" class="form-control select2" required>
                    <option value="">-- Chọn trung tâm --</option>
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}"
                            {{ old('distribution_center_id', $certificateRequest->distribution_center_id ?? '') == $center->id ? 'selected' : '' }}>
                            {{ $center->code }} - {{ $center->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            @error('distribution_center_id')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <label>Khách hàng / Công trình <span class="text-danger">*</span></label>
        <div class="btn-group btn-group-toggle d-flex mb-2" data-toggle="buttons">
            <label class="btn btn-outline-primary {{ $customerMode === 'existing' ? 'active' : '' }}">
                <input type="radio" name="customer_mode" value="existing" autocomplete="off"
                       {{ $customerMode === 'existing' ? 'checked' : '' }}>
                Chọn khách hàng có sẵn
            </label>
            <label class="btn btn-outline-primary {{ $customerMode === 'new' ? 'active' : '' }}">
                <input type="radio" name="customer_mode" value="new" autocomplete="off"
                       {{ $customerMode === 'new' ? 'checked' : '' }}>
                Nhập khách hàng mới
            </label>
        </div>
    </div>
</div>

<div id="existing-customer-box">
    <div class="form-group">
        <select name="customer_id" class="form-control customer-select select2">
            <option value="">-- Chọn khách hàng / công trình --</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" {{ $selectedCustomerId == $customer->id ? 'selected' : '' }}>
                    {{ $customer->customer_name }}
                    @if ($customer->project_name)
                        - {{ $customer->project_name }}
                    @endif
                    @if ($customer->email)
                        - {{ $customer->email }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('customer_id')
            <span class="text-danger small">{{ $message }}</span>
        @enderror
    </div>
</div>

<div id="new-customer-box" class="card card-light border">
    <div class="card-header bg-light">
        <h3 class="card-title"><i class="fas fa-user-plus"></i> Thông tin khách hàng mới</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Tên khách hàng <span class="text-danger">*</span></label>
                    <input type="text" name="new_customer_name" class="form-control"
                           value="{{ old('new_customer_name') }}">
                    @error('new_customer_name')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Tên công trình</label>
                    <input type="text" name="new_project_name" class="form-control"
                           value="{{ old('new_project_name') }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Địa chỉ khách hàng</label>
                    <input type="text" name="new_customer_address" class="form-control"
                           value="{{ old('new_customer_address') }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label>Địa điểm công trình</label>
                    <input type="text" name="new_project_address" class="form-control"
                           value="{{ old('new_project_address') }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Mã số thuế</label>
                    <input type="text" name="new_tax_code" class="form-control" value="{{ old('new_tax_code') }}">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Người liên hệ</label>
                    <input type="text" name="new_contact_person" class="form-control" value="{{ old('new_contact_person') }}">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="new_phone" class="form-control" value="{{ old('new_phone') }}">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label>Email nhận phiếu</label>
                    <input type="email" name="new_email" class="form-control" value="{{ old('new_email') }}">
                    @error('new_email')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Ngày xuất hàng</label>
            <input type="date" name="delivery_date" class="form-control"
                   value="{{ old('delivery_date', isset($certificateRequest) && $certificateRequest->delivery_date ? $certificateRequest->delivery_date->format('Y-m-d') : '') }}">
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>Số hóa đơn</label>
            <input type="text" name="invoice_no" class="form-control"
                   value="{{ old('invoice_no', $certificateRequest->invoice_no ?? '') }}">
        </div>
    </div>

    <div class="col-md-4">
        <label>Yêu cầu ký tươi</label>
        <div class="row">
            <div class="col-md-5">
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" name="require_hard_copy" value="1" class="custom-control-input"
                           id="require_hard_copy"
                           {{ old('require_hard_copy', $certificateRequest->require_hard_copy ?? false) ? 'checked' : '' }}>
                    <label class="custom-control-label" for="require_hard_copy">Có</label>
                </div>
            </div>

            <div class="col-md-7">
                <input type="number" name="hard_copy_quantity" min="0" class="form-control"
                       value="{{ old('hard_copy_quantity', $certificateRequest->hard_copy_quantity ?? 0) }}"
                       placeholder="Số bản">
            </div>
        </div>
    </div>
</div>

<div class="form-group">
    <label>Ghi chú</label>
    <textarea name="note" class="form-control" rows="2">{{ old('note', $certificateRequest->note ?? '') }}</textarea>
</div>

<hr>

<h5 class="mb-3">
    <i class="fas fa-box"></i> Danh sách sản phẩm đề nghị cấp phiếu
</h5>

<div class="table-responsive">
    <table class="table table-bordered" id="products-table">
        <thead class="thead-light">
            <tr>
                <th>Sản phẩm <span class="text-danger">*</span></th>
                <th style="width:180px">Số lượng <span class="text-danger">*</span></th>
                <th style="width:80px" class="text-center">Xóa</th>
            </tr>
        </thead>

        <tbody>
            @php
                $oldProducts = old('product_id');
                $oldQuantities = old('quantity');

                if (!$oldProducts && isset($certificateRequest)) {
                    $oldProducts = $certificateRequest->details->pluck('product_id')->toArray();
                    $oldQuantities = $certificateRequest->details->pluck('quantity')->toArray();
                }

                if (!$oldProducts) {
                    $oldProducts = [''];
                    $oldQuantities = [''];
                }
            @endphp

            @foreach ($oldProducts as $index => $oldProductId)
                <tr>
                    <td>
                        <select name="product_id[]" class="form-control product-select select2" required>
                            <option value="">-- Chọn sản phẩm --</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" {{ $oldProductId == $product->id ? 'selected' : '' }}>
                                    {{ $product->product_code }} - {{ $product->product_name }}
                                    @if ($product->nominal_size)
                                        - {{ $product->nominal_size }}
                                    @endif
                                    @if ($product->qualityStandard)
                                        - {{ $product->qualityStandard->code }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </td>

                    <td>
                        <input type="number" name="quantity[]" min="0.01" step="0.01" class="form-control"
                               value="{{ $oldQuantities[$index] ?? '' }}" required>
                    </td>

                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<button type="button" class="btn btn-outline-primary" id="add-row">
    <i class="fas fa-plus"></i> Thêm dòng sản phẩm
</button>

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ route('certificate-requests.index') }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="fas fa-save"></i> Lưu và gửi DVKH
    </button>
</div>

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#products-table tbody');
            const addRowBtn = document.getElementById('add-row');
            const existingBox = document.getElementById('existing-customer-box');
            const newBox = document.getElementById('new-customer-box');
            const customerModeInputs = document.querySelectorAll('input[name="customer_mode"]');

            function syncCustomerMode() {
                const mode = document.querySelector('input[name="customer_mode"]:checked').value;
                existingBox.style.display = mode === 'existing' ? '' : 'none';
                newBox.style.display = mode === 'new' ? '' : 'none';
            }

            customerModeInputs.forEach(function(input) {
                input.addEventListener('change', syncCustomerMode);
            });
            syncCustomerMode();

            addRowBtn.addEventListener('click', function() {
                const firstRow = tableBody.querySelector('tr');
                const firstSelect = firstRow.querySelector('select.product-select');

                if (window.jQuery && jQuery.fn.select2 && jQuery(firstSelect).hasClass('select2-hidden-accessible')) {
                    jQuery(firstSelect).select2('destroy');
                }

                firstRow.querySelectorAll('.select2-container').forEach(function(container) {
                    container.remove();
                });

                const newRow = firstRow.cloneNode(true);

                newRow.querySelectorAll('select, input').forEach(function(input) {
                    input.value = '';
                    input.removeAttribute('data-select2-id');
                    input.classList.remove('select2-hidden-accessible');
                    input.removeAttribute('aria-hidden');
                    input.removeAttribute('tabindex');
                });

                tableBody.appendChild(newRow);

                if (window.initSelect2) {
                    window.initSelect2(firstRow);
                    window.initSelect2(newRow);
                }
            });

            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.remove-row')) {
                    if (tableBody.querySelectorAll('tr').length <= 1) {
                        alert('Phải có ít nhất một dòng sản phẩm.');
                        return;
                    }

                    e.target.closest('tr').remove();
                }
            });
        });
    </script>
@stop
