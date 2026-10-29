@php
    $advancedApplied = request()->filled('distribution_center_id') || request()->filled('sales_unit_id') || request()->filled('print_template') || request()->filled('date_from') || request()->filled('date_to') || (request('date_basis') && request('date_basis') !== 'submitted_at');
    $advancedOpen = ($reportFilters ?? false) || $advancedApplied;
@endphp
<form method="GET" class="supplement-filter cncl-form">
    @if(request()->filled('quality_certificate_id'))
        <input type="hidden" name="quality_certificate_id" value="{{ request('quality_certificate_id') }}">
        <div class="alert alert-info py-2">Đang lọc yêu cầu bổ sung của phiếu CNCL #{{ request('quality_certificate_id') }}. <a href="{{ url()->current() }}">Xem tất cả phiếu</a></div>
    @endif
    @if(request()->filled('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">@endif
    <div class="row align-items-end">
        <div class="col-lg-6 col-md-6 form-group"><label for="supplement-keyword">Từ khóa</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span></div><input id="supplement-keyword" name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="Mã bổ sung, số phiếu, khách hàng, công trình, ĐVBH"></div></div>
        <div class="col-lg-4 col-md-4 form-group"><label for="supplement-status">Trạng thái</label><select id="supplement-status" name="status" class="form-control select2">
            <option value="">Tất cả trạng thái</option>@foreach(\App\Models\HardCopySupplement::LABELS as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach
        </select></div>
        <div class="col-lg-2 col-md-2 form-group supplement-filter-actions">
            <button class="btn btn-primary" title="Tìm kiếm" aria-label="Tìm kiếm"><i class="fas fa-search"></i></button>
            <a href="{{ url()->current() }}" class="btn btn-secondary" title="Xóa bộ lọc" aria-label="Xóa bộ lọc"><i class="fas fa-sync"></i></a>
        </div>
    </div>
    @if(request()->routeIs('hard-copy-supplements.index') && !auth()->user()->hasRole('TrungTam') && (auth()->user()->hasRole('Admin') || app(\App\Services\HardCopySupplementService::class)->dvkh(auth()->user()) || app(\App\Services\HardCopySupplementService::class)->ptn(auth()->user())))
    <div class="custom-control custom-checkbox mb-3">
        <input type="checkbox" name="work_queue" value="1" id="supplement-work-queue" class="custom-control-input" @checked(request()->boolean('work_queue'))>
        <label for="supplement-work-queue" class="custom-control-label">Chỉ việc chờ xử lý của tôi</label>
        @if(request()->boolean('work_queue'))<span class="badge badge-info ml-2">Đang áp dụng</span>@endif
    </div>
    @endif
    <details @if($advancedOpen) open @endif><summary><i class="fas fa-sliders-h mr-1" aria-hidden="true"></i> Bộ lọc nâng cao @if($advancedApplied)<span class="badge badge-info">Đang áp dụng</span>@endif</summary>
    <div class="row">
        @unless(auth()->user()->hasRole('TrungTam'))
        <div class="col-lg-4 col-md-6 form-group"><label for="supplement-center">Trung tâm</label><select id="supplement-center" name="distribution_center_id" class="form-control select2"><option value="">Tất cả trung tâm</option>
            @foreach($centers as $center)<option value="{{ $center->id }}" @selected(request('distribution_center_id') == $center->id)>{{ $center->name }}</option>@endforeach
        </select></div>
        @endunless
        <div class="col-lg-4 col-md-6 form-group"><label for="supplement-sales-unit">Đơn vị bán hàng</label><select id="supplement-sales-unit" name="sales_unit_id" class="form-control select2"><option value="">Tất cả đơn vị</option>
            @foreach($salesUnits as $unit)<option value="{{ $unit->id }}" @selected(request('sales_unit_id') == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>@endforeach
        </select></div>
        <div class="col-lg-4 col-md-6 form-group"><label for="supplement-template">Mẫu mở gần nhất</label><select id="supplement-template" name="print_template" class="form-control select2"><option value="">Tất cả mẫu</option><option value="single" @selected(request('print_template') === 'single')>In đơn</option><option value="batch" @selected(request('print_template') === 'batch')>In bộ</option></select></div>
        <div class="col-lg-4 col-md-6 form-group"><label for="supplement-date-basis">Lọc theo ngày</label><select id="supplement-date-basis" name="date_basis" class="form-control select2">
            @foreach(['submitted_at' => 'Ngày gửi bổ sung', 'approved_at' => 'Ngày DVKH duyệt', 'completed_at' => 'Ngày PTN hoàn tất', 'signed_at' => 'Ngày ký số phiếu'] as $key => $label)<option value="{{ $key }}" @selected((request('date_basis') ?: 'submitted_at') === $key)>{{ $label }}</option>@endforeach
        </select></div>
        <div class="col-lg-4 col-md-6 form-group"><label for="supplement-date-from">Từ ngày</label><input id="supplement-date-from" type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-lg-4 col-md-6 form-group"><label for="supplement-date-to">Đến ngày</label><input id="supplement-date-to" type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
    </div>
    </details>
</form>
