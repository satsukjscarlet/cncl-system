@extends('adminlte::page')

@section('title', 'Phiếu CNCL')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .certificate-page-title {
            color: #0f172a;
            font-weight: 800;
            letter-spacing: 0;
        }

        .certificate-filter-card .card-body {
            padding: 16px 18px 14px;
        }

        .certificate-filter-grid {
            align-items: end;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(280px, 1.45fr) repeat(3, minmax(170px, .85fr)) minmax(210px, 1fr) auto;
        }

        .certificate-filter-grid.is-center-user {
            grid-template-columns: minmax(300px, 1.6fr) repeat(2, minmax(180px, .85fr)) minmax(220px, 1fr) auto;
        }

        .certificate-filter-field label {
            color: #111827;
            display: block;
            font-size: .86rem;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .certificate-filter-field .form-control,
        .certificate-filter-field .select2-container--default .select2-selection--single {
            min-height: 40px;
        }

        .certificate-filter-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            white-space: nowrap;
        }

        .certificate-filter-actions .btn {
            align-items: center;
            display: inline-flex;
            font-weight: 700;
            justify-content: center;
            min-height: 40px;
        }

        .certificate-filter-actions .btn-primary {
            min-width: 92px;
        }

        .certificate-active-filters {
            border-top: 1px solid #edf1f5;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
            padding-top: 12px;
        }

        .certificate-filter-chip {
            align-items: center;
            background: #eef6ff;
            border: 1px solid #d7eaff;
            border-radius: 999px;
            color: #0b5cad;
            display: inline-flex;
            font-size: 12px;
            font-weight: 700;
            gap: 6px;
            min-height: 28px;
            padding: 4px 10px;
        }

        .certificate-list-card .card-header {
            align-items: center;
            display: flex;
            gap: 14px;
            justify-content: space-between;
        }

        .certificate-list-card .card-tools {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .certificate-table {
            color: #111827;
        }

        .certificate-table th {
            background: #f8fafc;
            border-bottom-width: 1px;
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .certificate-table tbody td {
            vertical-align: top;
        }

        .certificate-no {
            color: #0f3760;
            font-weight: 800;
        }

        .certificate-subline {
            color: #697586;
            font-size: 12px;
            margin-top: 3px;
        }

        .certificate-customer {
            color: #111827;
            font-weight: 700;
            line-height: 1.35;
        }

        .certificate-project {
            color: #64748b;
            font-size: 12px;
            line-height: 1.35;
            margin-top: 4px;
        }

        .certificate-action-group {
            display: inline-flex;
            gap: 6px;
        }

        .certificate-empty-state {
            color: #64748b;
            padding: 34px 12px;
            text-align: center;
        }

        @media (max-width: 1399.98px) {
            .certificate-filter-grid,
            .certificate-filter-grid.is-center-user {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .certificate-filter-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 991.98px) {
            .certificate-filter-grid,
            .certificate-filter-grid.is-center-user {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .certificate-filter-grid,
            .certificate-filter-grid.is-center-user {
                grid-template-columns: 1fr;
            }

            .certificate-filter-actions .btn {
                flex: 1 1 auto;
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0 certificate-page-title">Danh sách Phiếu CNCL</h1>
        <small class="text-muted">Quản lý phiếu chứng nhận chất lượng đã được PTN lập</small>
    </div>

    <div class="mt-2 mt-sm-0">
        <span class="badge badge-info px-3 py-2">
            Tổng số: {{ number_format($certificates->total()) }}
        </span>
    </div>
</div>
@stop

@section('content')
@php
    $selectedStatus = request('status', 'ALL') ?: 'ALL';
    $statusOptions = [
        'ALL' => 'Tất cả trạng thái',
        'SIGN_READY' => 'Chờ duyệt / chờ gửi ký',
        'SMARTCA_PENDING' => 'Đang chờ ký số',
        'UNSIGNED' => 'Chưa ký',
        'SIGNED' => 'Đã ký/phát hành',
        'SMARTCA_EXPIRED' => 'Yêu cầu ký hết hạn',
        'REVOKED' => 'Đã hủy/thu hồi',
        'REJECTED' => 'Đã trả lại',
    ];
    $selectedCenter = !$isCenterUser && request('distribution_center_id')
        ? $centers->firstWhere('id', (int) request('distribution_center_id'))
        : null;
    $hasActiveFilters = filled(request('keyword'))
        || filled(request('distribution_center_id'))
        || filled(request('date_from'))
        || filled(request('date_to'))
        || $selectedStatus !== 'ALL';
@endphp

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card card-primary card-outline certificate-filter-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Bộ lọc dữ liệu
        </h3>
    </div>

    <div class="card-body">
        <form method="GET" class="certificate-filter-grid {{ $isCenterUser ? 'is-center-user' : '' }}">
            <div class="certificate-filter-field">
                <label>Từ khóa</label>
                <input type="text"
                       name="keyword"
                       class="form-control"
                       value="{{ request('keyword') }}"
                       placeholder="Số phiếu, số yêu cầu, khách hàng, công trình, hóa đơn">
            </div>

            @unless($isCenterUser)
                <div class="certificate-filter-field">
                    <label>Trung tâm</label>
                    <select name="distribution_center_id" class="form-control select2">
                        <option value="">Tất cả trung tâm</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                {{ $center->code }} - {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endunless

            <div class="certificate-filter-field">
                <label>Từ ngày lập phiếu</label>
                <input type="date"
                       name="date_from"
                       class="form-control"
                       value="{{ request('date_from') }}">
            </div>

            <div class="certificate-filter-field">
                <label>Đến ngày lập phiếu</label>
                <input type="date"
                       name="date_to"
                       class="form-control"
                       value="{{ request('date_to') }}">
            </div>

            <div class="certificate-filter-field">
                <label>Trạng thái ký</label>
                <select name="status" class="form-control select2">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $selectedStatus === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="certificate-filter-actions">
                <button class="btn btn-primary">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="{{ route('quality-certificates.index') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>

        @if($hasActiveFilters)
            <div class="certificate-active-filters">
                @if(filled(request('keyword')))
                    <span class="certificate-filter-chip">
                        <i class="fas fa-search"></i> {{ request('keyword') }}
                    </span>
                @endif

                @if($selectedCenter)
                    <span class="certificate-filter-chip">
                        <i class="fas fa-building"></i> {{ $selectedCenter->code }} - {{ $selectedCenter->name }}
                    </span>
                @endif

                @if(filled(request('date_from')))
                    <span class="certificate-filter-chip">
                        <i class="fas fa-calendar-alt"></i> Từ {{ \Carbon\Carbon::parse(request('date_from'))->format('d/m/Y') }}
                    </span>
                @endif

                @if(filled(request('date_to')))
                    <span class="certificate-filter-chip">
                        <i class="fas fa-calendar-check"></i> Đến {{ \Carbon\Carbon::parse(request('date_to'))->format('d/m/Y') }}
                    </span>
                @endif

                @if($selectedStatus !== 'ALL')
                    <span class="certificate-filter-chip">
                        <i class="fas fa-tag"></i> {{ $statusOptions[$selectedStatus] ?? $selectedStatus }}
                    </span>
                @endif
            </div>
        @endif
    </div>
</div>

<div class="card certificate-list-card">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0">
            <i class="fas fa-file-signature"></i> Danh sách phiếu
        </h3>

        <div class="card-tools">
            @can('request.create')
                <button type="button"
                        class="btn btn-sm btn-danger"
                        data-toggle="modal"
                        data-target="#bulkReissueModal">
                    <i class="fas fa-object-group"></i>
                    Gom cấp lại
                    <span id="bulkReissueSelectedCount" class="badge badge-light ml-1 d-none">0</span>
                </button>
            @endcan
            <span class="badge badge-info px-2 py-1">
                {{ number_format($certificates->total()) }} bản ghi
            </span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0 certificate-table">
            <thead>
                <tr>
                    @can('request.create')
                        <th style="width:38px" class="text-center">
                            <input type="checkbox" id="selectAllReissueCertificates">
                        </th>
                    @endcan
                    <th style="width:60px">STT</th>
                    <th>@include('partials.sort_link', ['column' => 'certificate_no', 'label' => 'Số phiếu'])</th>
                    <th>Số yêu cầu</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Trung tâm</th>
                    <th>Người lập</th>
                    <th>@include('partials.sort_link', ['column' => 'signed_at', 'label' => 'Ngày ký'])</th>
                    <th>@include('partials.sort_link', ['column' => 'status', 'label' => 'Trạng thái'])</th>
                    <th style="width:170px" class="text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse($certificates as $certificate)
                    @php
                        $statusMeta = $certificate->displayStatusMeta();
                    @endphp
                    <tr>
                        @can('request.create')
                            <td class="text-center">
                                @if($certificate->canRequestReissue())
                                    <input type="checkbox"
                                           class="bulk-reissue-checkbox"
                                           name="certificate_ids[]"
                                           value="{{ $certificate->id }}"
                                           form="bulkReissueForm">
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endcan
                        <td>{{ $certificates->firstItem() + $loop->index }}</td>

                        <td>
                            <div class="certificate-no">{{ $certificate->certificate_no }}</div>
                            <div class="certificate-subline">
                                Lập: {{ optional($certificate->created_at)->format('d/m/Y H:i') }}
                            </div>

                            @if($certificate->replacesCertificate)
                                <div class="mt-2">
                                    <span class="badge badge-info">
                                        <i class="fas fa-redo"></i> Phiếu cấp lại
                                    </span>
                                    <a class="small d-block mt-1" href="{{ route('quality-certificates.show', $certificate->replacesCertificate) }}">
                                        Cấp lại cho: {{ $certificate->replacesCertificate->certificate_no }}
                                    </a>
                                </div>
                            @endif

                            @if($certificate->replacedByCertificate)
                                <div class="mt-2">
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exchange-alt"></i> Đã có phiếu thay thế
                                    </span>
                                    <a class="small d-block mt-1" href="{{ route('quality-certificates.show', $certificate->replacedByCertificate) }}">
                                        Phiếu mới: {{ $certificate->replacedByCertificate->certificate_no }}
                                    </a>
                                </div>
                            @endif
                        </td>

                        <td>{{ $certificate->request->request_no ?? '-' }}</td>

                        <td>
                            <div class="certificate-customer">
                                {{ $certificate->request->customer->customer_name ?? '-' }}
                            </div>
                            <div class="certificate-project">
                                {{ $certificate->request->customer->project_name ?? '' }}
                            </div>
                        </td>

                        <td>{{ $certificate->request->distributionCenter->name ?? '-' }}</td>

                        <td>{{ $certificate->creator->name ?? '-' }}</td>

                        <td>{{ $certificate->signed_at ? $certificate->signed_at->format('d/m/Y H:i') : '-' }}</td>

                        <td>
                            <span class="badge {{ $statusMeta['class'] }}">
                                <i class="{{ $statusMeta['icon'] }}"></i> {{ $statusMeta['text'] }}
                            </span>
                        </td>

                        <td class="text-center">
                            <div class="certificate-action-group">
                                <a href="{{ route('quality-certificates.show', $certificate) }}"
                                   class="btn btn-sm btn-info"
                                   title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('request.create')
                                    @if($certificate->canRequestReissue())
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                title="Yêu cầu cấp lại"
                                                data-toggle="modal"
                                                data-target="#reissueModal{{ $certificate->id }}">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                    @endif
                                @endcan
                            </div>

                            @can('request.create')
                                @if($certificate->canRequestReissue())
                                    <div class="modal fade" id="reissueModal{{ $certificate->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST"
                                                  action="{{ route('quality-certificates.request-reissue', $certificate) }}"
                                                  class="modal-content">
                                                @csrf

                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-redo"></i> Yêu cầu cấp lại phiếu
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>

                                                <div class="modal-body text-left">
                                                    <p>Phiếu: <strong>{{ $certificate->certificate_no }}</strong></p>
                                                    <div class="alert alert-warning">
                                                        Yêu cầu này sẽ được gửi sang DVKH. Khi DVKH xác nhận, phiếu cũ sẽ bị hủy/thu hồi và quy trình cấp phiếu mới bắt đầu.
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Lý do cấp lại <span class="text-danger">*</span></label>
                                                        <textarea name="reissue_reason"
                                                                  class="form-control"
                                                                  rows="4"
                                                                  required></textarea>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                                    <button class="btn btn-danger">
                                                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="@can('request.create') 10 @else 9 @endcan">
                            <div class="certificate-empty-state">
                                <i class="fas fa-database fa-2x mb-2"></i>
                                <div>Chưa có phiếu CNCL phù hợp với bộ lọc.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $certificates->firstItem() ?? 0 }} - {{ $certificates->lastItem() ?? 0 }}
                / {{ $certificates->total() }} bản ghi
            </div>

            <div class="col-md-6">
                <div class="float-md-right">
                    {{ $certificates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@can('request.create')
    <div class="modal fade" id="bulkReissueModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="bulkReissueForm"
                  method="POST"
                  action="{{ route('quality-certificates.bulk-request-reissue') }}"
                  class="modal-content">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-object-group"></i> Gom nhiều phiếu cũ để cấp lại
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning">
                        Hệ thống sẽ tạo một yêu cầu cấp lại mới từ các phiếu đã chọn. Sau khi tạo, anh có thể sửa lại khách hàng, hóa đơn và danh sách sản phẩm trước khi DVKH xác nhận.
                    </div>

                    <div class="form-group mb-0">
                        <label>Lý do cấp lại <span class="text-danger">*</span></label>
                        <textarea name="reissue_reason"
                                  class="form-control"
                                  rows="4"
                                  required></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                    <button class="btn btn-danger">
                        <i class="fas fa-paper-plane"></i> Tạo yêu cầu cấp lại
                    </button>
                </div>
            </form>
        </div>
    </div>
@endcan

@stop

@section('js')
<script>
    $(function () {
        var $selectAll = $('#selectAllReissueCertificates');
        var $checkboxes = $('.bulk-reissue-checkbox');
        var $selectedCount = $('#bulkReissueSelectedCount');

        function updateBulkState() {
            var selected = $('.bulk-reissue-checkbox:checked').length;
            var total = $checkboxes.length;

            $selectedCount.toggleClass('d-none', selected === 0).text(selected);

            if ($selectAll.length) {
                $selectAll.prop('checked', total > 0 && selected === total);
                $selectAll.prop('indeterminate', selected > 0 && selected < total);
            }
        }

        $selectAll.on('change', function () {
            $checkboxes.prop('checked', this.checked);
            updateBulkState();
        });

        $checkboxes.on('change', updateBulkState);

        $('#bulkReissueForm').on('submit', function (event) {
            if ($('.bulk-reissue-checkbox:checked').length < 2) {
                event.preventDefault();
                alert('Vui lòng chọn ít nhất 2 phiếu đủ điều kiện để gom cấp lại.');
            }
        });

        updateBulkState();
    });
</script>
@stop
