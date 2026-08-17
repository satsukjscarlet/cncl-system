@csrf

@php
    $customerMode = old('customer_mode', 'existing');
    $selectedCustomerId = old('customer_id', $certificateRequest->customer_id ?? '');
    $isUrgent = old('is_urgent', $certificateRequest->is_urgent ?? false);
    $selectedUrgentReasonId = old('urgent_reason_id', $certificateRequest->urgent_reason_id ?? '');
    $formBackUrl = $formBackUrl ?? route('certificate-requests.index');
    $formSubmitText = $formSubmitText ?? 'Lưu và gửi DVKH';
    $formSubmitIcon = $formSubmitIcon ?? 'fas fa-save';
    $selectedCustomers = $selectedCustomers ?? collect();
    $selectedProducts = $selectedProducts ?? collect();
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
        <select name="customer_id"
                class="form-control customer-select select2"
                data-ajax-url="{{ route('certificate-requests.customer-options') }}"
                data-ajax-include-center="1"
                data-minimum-input-length="1">
            <option value="">-- Chọn khách hàng / công trình --</option>
            @foreach ($selectedCustomers as $customer)
                <option value="{{ $customer->id }}" {{ $selectedCustomerId == $customer->id ? 'selected' : '' }}>
                    @if ($customer->customer_code)
                        {{ $customer->customer_code }} -
                    @endif
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
            <div class="col-md-4">
                <div class="form-group">
                    <label>Mã khách hàng</label>
                    <input type="text" name="new_customer_code" class="form-control"
                           value="{{ old('new_customer_code') }}"
                           placeholder="Có thể bỏ trống để hệ thống tự sinh mã">
                    @error('new_customer_code')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label>Tên khách hàng <span class="text-danger">*</span></label>
                    <input type="text" name="new_customer_name" class="form-control"
                           value="{{ old('new_customer_name') }}">
                    @error('new_customer_name')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="col-md-4">
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
            <input type="text"
                   name="invoice_no"
                   id="invoice_no"
                   class="form-control"
                   data-check-url="{{ route('certificate-requests.check-invoice') }}"
                   data-exclude-id="{{ $certificateRequest->id ?? '' }}"
                   value="{{ old('invoice_no', $certificateRequest->invoice_no ?? '') }}">
            <div id="invoice-duplicate-warning" class="alert alert-warning mt-2 mb-0 d-none">
                <div class="font-weight-bold">
                    <i class="fas fa-exclamation-triangle"></i>
                    Số hóa đơn này đã tồn tại trên hệ thống.
                </div>
                <div class="small mb-2">Vui lòng kiểm tra lại trước khi lưu yêu cầu.</div>
                <div id="invoice-duplicate-list" class="small"></div>
            </div>
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

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>Tên người tạo yêu cầu</label>
            <input type="text"
                   name="requester_name"
                   class="form-control"
                   value="{{ old('requester_name', $certificateRequest->requester_name ?? '') }}"
                   placeholder="Nhập tên người tạo yêu cầu">
            @error('requester_name')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <label>Yêu cầu cung cấp gấp</label>
        <div class="custom-control custom-switch mt-2">
            <input type="checkbox"
                   name="is_urgent"
                   value="1"
                   class="custom-control-input"
                   id="is_urgent"
                   {{ $isUrgent ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_urgent">Mở yêu cầu gấp</label>
        </div>
        @error('is_urgent')
            <span class="text-danger small">{{ $message }}</span>
        @enderror
    </div>

    <div class="col-md-4" id="urgent-reason-box">
        <div class="form-group">
            <label>Lý do yêu cầu gấp <span class="text-danger">*</span></label>
            <select name="urgent_reason_id" id="urgent_reason_id" class="form-control select2">
                <option value="">-- Chọn lý do yêu cầu gấp --</option>
                @foreach ($urgentReasons as $urgentReason)
                    <option value="{{ $urgentReason->id }}" {{ $selectedUrgentReasonId == $urgentReason->id ? 'selected' : '' }}>
                        {{ $urgentReason->name }}
                    </option>
                @endforeach
            </select>
            @error('urgent_reason_id')
                <span class="text-danger small">{{ $message }}</span>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <label>Ghi chú</label>
    <textarea name="note" class="form-control" rows="2">{{ old('note', $certificateRequest->note ?? '') }}</textarea>
</div>

<hr>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h5 class="mb-2 mb-md-0">
        <i class="fas fa-box"></i> Danh sách sản phẩm đề nghị cấp phiếu
    </h5>

    <div>
        <a href="{{ route('certificate-requests.products-template') }}"
           class="btn btn-sm btn-outline-secondary"
           data-no-loading
           data-download>
            <i class="fas fa-file-download"></i> Tải mẫu Excel
        </a>
        <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#importProductsModal">
            <i class="fas fa-file-import"></i> Import Excel
        </button>
        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#pasteProductsModal">
            <i class="fas fa-paste"></i> Dán từ Excel
        </button>
    </div>
</div>

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
                        <select name="product_id[]"
                                class="form-control product-select select2"
                                data-ajax-url="{{ route('certificate-requests.product-options') }}"
                                data-minimum-input-length="1"
                                required>
                            <option value="">-- Chọn sản phẩm --</option>
                            @if($oldProductId && $selectedProducts->has((int) $oldProductId))
                                @php
                                    $selectedProduct = $selectedProducts->get((int) $oldProductId);
                                    $selectedProductText = collect([
                                        $selectedProduct->product_code,
                                        $selectedProduct->product_name,
                                        $selectedProduct->nominal_size,
                                        $selectedProduct->qualityStandard?->code,
                                    ])->filter()->implode(' - ');
                                @endphp
                                <option value="{{ $selectedProduct->id }}" selected>{{ $selectedProductText }}</option>
                            @endif
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

<div class="modal fade" id="pasteProductsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paste"></i> Dán danh sách sản phẩm từ Excel
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    Copy 2 cột từ Excel rồi dán vào ô bên dưới: <strong>mã sản phẩm</strong> và <strong>số lượng</strong>.
                    Có thể giữ dòng tiêu đề. Nếu mã sản phẩm trùng, hệ thống sẽ tự cộng dồn số lượng.
                </div>

                <div id="paste-products-errors" class="alert alert-danger d-none"></div>

                <div class="form-group">
                    <label>Dữ liệu dán từ Excel <span class="text-danger">*</span></label>
                    <textarea id="paste_products_text"
                              class="form-control"
                              rows="10"
                              placeholder="ma_san_pham	so_luong&#10;PE2516100	44&#10;PE2516	22"></textarea>
                    <small class="form-text text-muted">
                        Mỗi dòng là một sản phẩm. Hệ thống hỗ trợ dữ liệu phân tách bằng tab, dấu phẩy, dấu chấm phẩy hoặc nhiều khoảng trắng.
                    </small>
                </div>

                <div class="form-group mb-0">
                    <label>Cách đưa vào danh sách</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="paste_mode_replace" name="paste_products_mode" value="replace" class="custom-control-input" checked>
                        <label class="custom-control-label" for="paste_mode_replace">Thay thế danh sách sản phẩm hiện tại</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="paste_mode_append" name="paste_products_mode" value="append" class="custom-control-input">
                        <label class="custom-control-label" for="paste_mode_append">Thêm vào danh sách hiện tại, mã trùng thì cộng số lượng</label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button type="button"
                        class="btn btn-primary"
                        id="paste-products-submit"
                        data-paste-url="{{ route('certificate-requests.paste-products') }}">
                    <i class="fas fa-check"></i> Đưa vào danh sách
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="importProductsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-import"></i> Import danh sách sản phẩm
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    File Excel cần có 2 cột: <strong>ma_san_pham</strong> và <strong>so_luong</strong>.
                    Mã sản phẩm trùng trong file sẽ được cộng dồn số lượng.
                </div>

                <div id="import-products-errors" class="alert alert-danger d-none"></div>

                <div class="form-group">
                    <label>File Excel <span class="text-danger">*</span></label>
                    <input type="file"
                           id="import_products_file"
                           class="form-control-file"
                           accept=".xlsx,.xls,.csv">
                </div>

                <div class="form-group mb-0">
                    <label>Cách nhập dữ liệu</label>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="import_mode_replace" name="import_products_mode" value="replace" class="custom-control-input" checked>
                        <label class="custom-control-label" for="import_mode_replace">Ghi đè danh sách sản phẩm hiện tại</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="import_mode_append" name="import_products_mode" value="append" class="custom-control-input">
                        <label class="custom-control-label" for="import_mode_append">Cộng thêm vào danh sách hiện tại</label>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button type="button"
                        class="btn btn-success"
                        id="import-products-submit"
                        data-import-url="{{ route('certificate-requests.import-products') }}">
                    <i class="fas fa-file-import"></i> Import
                </button>
            </div>
        </div>
    </div>
</div>

<hr>

<div class="d-flex justify-content-end">
    <a href="{{ $formBackUrl }}" class="btn btn-default mr-2">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>

    <button class="btn btn-primary">
        <i class="{{ $formSubmitIcon }}"></i> {{ $formSubmitText }}
    </button>
</div>

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#products-table tbody');
            const addRowBtn = document.getElementById('add-row');
            const existingBox = document.getElementById('existing-customer-box');
            const newBox = document.getElementById('new-customer-box');
            const distributionCenterSelect = document.querySelector('[name="distribution_center_id"]');
            const customerSelect = document.querySelector('select[name="customer_id"]');
            const customerModeInputs = document.querySelectorAll('input[name="customer_mode"]');
            const urgentSwitch = document.getElementById('is_urgent');
            const urgentReasonBox = document.getElementById('urgent-reason-box');
            const urgentReasonSelect = document.getElementById('urgent_reason_id');
            const invoiceInput = document.getElementById('invoice_no');
            const invoiceWarning = document.getElementById('invoice-duplicate-warning');
            const invoiceDuplicateList = document.getElementById('invoice-duplicate-list');
            const importProductsSubmit = document.getElementById('import-products-submit');
            const importProductsFile = document.getElementById('import_products_file');
            const importProductsErrors = document.getElementById('import-products-errors');
            const pasteProductsSubmit = document.getElementById('paste-products-submit');
            const pasteProductsText = document.getElementById('paste_products_text');
            const pasteProductsErrors = document.getElementById('paste-products-errors');
            const productRowTemplate = tableBody.querySelector('tr').cloneNode(true);
            let invoiceCheckTimer = null;

            function syncCustomerMode() {
                const mode = document.querySelector('input[name="customer_mode"]:checked').value;
                existingBox.style.display = mode === 'existing' ? '' : 'none';
                newBox.style.display = mode === 'new' ? '' : 'none';
            }

            customerModeInputs.forEach(function(input) {
                input.addEventListener('change', syncCustomerMode);
            });
            syncCustomerMode();

            if (distributionCenterSelect && customerSelect) {
                distributionCenterSelect.addEventListener('change', function() {
                    if (window.jQuery && jQuery.fn.select2) {
                        jQuery(customerSelect).val(null).trigger('change');
                    } else {
                        customerSelect.value = '';
                    }
                });
            }

            function syncUrgentReason() {
                const enabled = urgentSwitch && urgentSwitch.checked;
                urgentReasonBox.style.display = enabled ? '' : 'none';
                urgentReasonSelect.required = enabled;

                if (!enabled) {
                    urgentReasonSelect.value = '';
                    if (window.jQuery && jQuery.fn.select2 && jQuery(urgentReasonSelect).hasClass('select2-hidden-accessible')) {
                        jQuery(urgentReasonSelect).val('').trigger('change');
                    }
                }
            }

            if (urgentSwitch) {
                urgentSwitch.addEventListener('change', syncUrgentReason);
                syncUrgentReason();
            }

            function statusText(status) {
                const statuses = {
                    DRAFT: 'Nháp',
                    WAIT_DVKH: 'Chờ DVKH',
                    WAIT_PTN: 'Chờ PTN lập phiếu',
                    PTN_PROCESSING: 'Đã lập phiếu - Chờ ký',
                    SIGNED: 'Đã ký số',
                    COMPLETED: 'Hoàn tất',
                    CANCELLED: 'Hủy/Trả lại'
                };

                return statuses[status] || status || '-';
            }

            function renderInvoiceDuplicates(items) {
                if (!items.length) {
                    invoiceWarning.classList.add('d-none');
                    invoiceDuplicateList.innerHTML = '';
                    return;
                }

                invoiceDuplicateList.innerHTML = items.map(function(item) {
                    const certificate = item.certificate_no ? ' | Phiếu: ' + item.certificate_no : '';
                    const project = item.project_name ? ' - ' + item.project_name : '';

                    return '<div class="border-top pt-2 mt-2">' +
                        '<a href="' + item.url + '" target="_blank"><strong>' + item.request_no + '</strong></a>' +
                        ' | ' + statusText(item.status) + certificate +
                        '<br>' +
                        '<span>' + item.customer_name + project + '</span>' +
                        '<br>' +
                        '<span class="text-muted">' + item.distribution_center + ' | ' + item.created_at + '</span>' +
                    '</div>';
                }).join('');

                invoiceWarning.classList.remove('d-none');
            }

            function checkInvoiceDuplicate() {
                if (!invoiceInput || !invoiceInput.value.trim()) {
                    renderInvoiceDuplicates([]);
                    return;
                }

                const params = new URLSearchParams({
                    invoice_no: invoiceInput.value.trim()
                });

                if (invoiceInput.dataset.excludeId) {
                    params.append('exclude_id', invoiceInput.dataset.excludeId);
                }

                fetch(invoiceInput.dataset.checkUrl + '?' + params.toString(), {
                    headers: {
                        Accept: 'application/json'
                    }
                })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Không kiểm tra được số hóa đơn');
                        }

                        return response.json();
                    })
                    .then(function(data) {
                        renderInvoiceDuplicates(data.items || []);
                    })
                    .catch(function() {
                        renderInvoiceDuplicates([]);
                    });
            }

            if (invoiceInput) {
                invoiceInput.addEventListener('input', function() {
                    clearTimeout(invoiceCheckTimer);
                    invoiceCheckTimer = setTimeout(checkInvoiceDuplicate, 450);
                });
                invoiceInput.addEventListener('blur', checkInvoiceDuplicate);
                checkInvoiceDuplicate();
            }

            addRowBtn.addEventListener('click', function() {
                const firstRow = tableBody.querySelector('tr');
                const firstSelect = firstRow.querySelector('select.product-select');

                if (window.jQuery && jQuery.fn.select2 && jQuery(firstSelect).hasClass('select2-hidden-accessible') && jQuery(firstSelect).data('select2')) {
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

                    if (input.matches('select.product-select')) {
                        input.innerHTML = '<option value="">-- Chọn sản phẩm --</option>';
                    }
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

            function importDestroySelect2(select) {
                if (!select || !window.jQuery || !jQuery.fn.select2) {
                    return;
                }

                const $select = jQuery(select);
                if ($select.hasClass('select2-hidden-accessible') && $select.data('select2')) {
                    $select.select2('destroy');
                }
            }

            function importPrepareRow(row, item) {
                row.querySelectorAll('.select2-container').forEach(function(container) {
                    container.remove();
                });

                const select = row.querySelector('select.product-select');
                const quantityInput = row.querySelector('input[name="quantity[]"]');

                importDestroySelect2(select);
                select.removeAttribute('data-select2-id');
                select.classList.remove('select2-hidden-accessible');
                select.removeAttribute('aria-hidden');
                select.removeAttribute('tabindex');
                select.innerHTML = '<option value="">-- Chọn sản phẩm --</option>';

                if (item && item.product_id) {
                    const option = document.createElement('option');
                    option.value = item.product_id;
                    option.textContent = item.product_text || '';
                    option.selected = true;
                    select.appendChild(option);
                }

                quantityInput.value = item && item.quantity ? item.quantity : '';
            }

            function importAppendRow(item) {
                const row = productRowTemplate.cloneNode(true);
                importPrepareRow(row, item || {});
                tableBody.appendChild(row);

                if (window.initSelect2) {
                    window.initSelect2(row);
                }
            }

            function importShowErrors(errors) {
                if (!importProductsErrors) {
                    return;
                }

                if (!errors || !errors.length) {
                    importProductsErrors.classList.add('d-none');
                    importProductsErrors.innerHTML = '';
                    return;
                }

                importProductsErrors.innerHTML = '<ul class="mb-0">' + errors.map(function(error) {
                    return '<li>' + error + '</li>';
                }).join('') + '</ul>';
                importProductsErrors.classList.remove('d-none');
            }

            function pasteShowErrors(errors) {
                if (!pasteProductsErrors) {
                    return;
                }

                if (!errors || !errors.length) {
                    pasteProductsErrors.classList.add('d-none');
                    pasteProductsErrors.innerHTML = '';
                    return;
                }

                pasteProductsErrors.innerHTML = '<div class="font-weight-bold mb-1">Vui lòng kiểm tra lại dữ liệu:</div><ul class="mb-0">' + errors.map(function(error) {
                    return '<li>' + error + '</li>';
                }).join('') + '</ul>';
                pasteProductsErrors.classList.remove('d-none');
            }

            function importMergeRow(item) {
                const existingSelect = Array.from(tableBody.querySelectorAll('select.product-select'))
                    .find(function(select) {
                        return String(select.value) === String(item.product_id);
                    });

                if (!existingSelect) {
                    importAppendRow(item);
                    return;
                }

                const quantityInput = existingSelect.closest('tr').querySelector('input[name="quantity[]"]');
                quantityInput.value = parseFloat(quantityInput.value || '0') + parseFloat(item.quantity || '0');
            }

            if (pasteProductsSubmit) {
                pasteProductsSubmit.addEventListener('click', function() {
                    pasteShowErrors([]);

                    const text = (pasteProductsText.value || '').trim();

                    if (!text) {
                        pasteShowErrors(['Vui lòng dán danh sách mã sản phẩm và số lượng từ Excel.']);
                        return;
                    }

                    pasteProductsSubmit.disabled = true;
                    pasteProductsSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

                    fetch(pasteProductsSubmit.dataset.pasteUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            products_text: text
                        })
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                if (!response.ok) {
                                    throw data;
                                }

                                return data;
                            });
                        })
                        .then(function(data) {
                            const items = data.items || [];

                            if (!items.length) {
                                pasteShowErrors(['Không có sản phẩm hợp lệ để đưa vào danh sách.']);
                                return;
                            }

                            const mode = document.querySelector('input[name="paste_products_mode"]:checked').value;

                            if (mode === 'replace') {
                                tableBody.querySelectorAll('select.product-select').forEach(importDestroySelect2);
                                tableBody.innerHTML = '';
                                items.forEach(importAppendRow);
                            } else {
                                items.forEach(importMergeRow);
                            }

                            pasteProductsText.value = '';
                            jQuery('#pasteProductsModal').modal('hide');
                        })
                        .catch(function(error) {
                            pasteShowErrors(error.errors || [error.message || 'Không thể xử lý dữ liệu đã dán.']);
                        })
                        .finally(function() {
                            pasteProductsSubmit.disabled = false;
                            pasteProductsSubmit.innerHTML = '<i class="fas fa-check"></i> Đưa vào danh sách';
                        });
                });
            }

            if (importProductsSubmit) {
                importProductsSubmit.addEventListener('click', function() {
                    importShowErrors([]);

                    if (!importProductsFile.files.length) {
                        importShowErrors(['Vui lòng chọn file Excel cần import.']);
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', importProductsFile.files[0]);

                    importProductsSubmit.disabled = true;
                    importProductsSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang import...';

                    fetch(importProductsSubmit.dataset.importUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: formData
                    })
                        .then(function(response) {
                            return response.json().then(function(data) {
                                if (!response.ok) {
                                    throw data;
                                }

                                return data;
                            });
                        })
                        .then(function(data) {
                            const items = data.items || [];

                            if (!items.length) {
                                importShowErrors(['File không có sản phẩm hợp lệ để import.']);
                                return;
                            }

                            const mode = document.querySelector('input[name="import_products_mode"]:checked').value;

                            if (mode === 'replace') {
                                tableBody.querySelectorAll('select.product-select').forEach(importDestroySelect2);
                                tableBody.innerHTML = '';
                                items.forEach(importAppendRow);
                            } else {
                                items.forEach(importMergeRow);
                            }

                            importProductsFile.value = '';
                            jQuery('#importProductsModal').modal('hide');
                        })
                        .catch(function(error) {
                            importShowErrors(error.errors || [error.message || 'Không thể import file Excel.']);
                        })
                        .finally(function() {
                            importProductsSubmit.disabled = false;
                            importProductsSubmit.innerHTML = '<i class="fas fa-file-import"></i> Import';
                        });
                });
            }
        });
    </script>
@stop
