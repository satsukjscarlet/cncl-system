@extends('adminlte::page')

@section('title', 'DVKH kiểm tra yêu cầu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .filter-toolbar.has-duplicate-filter {
            grid-template-columns: 116px minmax(260px, 1.45fr) minmax(190px, .9fr) minmax(170px, .75fr) minmax(170px, .75fr) auto;
        }

        @media (max-width: 1399.98px) {
            .filter-toolbar.has-duplicate-filter {
                grid-template-columns: 100px minmax(260px, 1.35fr) minmax(190px, .9fr) minmax(170px, .75fr) minmax(170px, .75fr);
            }

            .filter-toolbar.has-duplicate-filter .filter-actions {
                grid-column: 2 / -1;
                justify-content: flex-start;
            }
        }

        @media (max-width: 991.98px) {
            .filter-toolbar.has-duplicate-filter {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .filter-toolbar.has-duplicate-filter {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">DVKH kiểm tra yêu cầu</h1>
        <small class="text-muted">Kiểm tra, xác nhận hoặc trả lại yêu cầu cấp phiếu CNCL</small>
    </div>
</div>
@stop

@section('content')

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

<div class="card request-filter-card">
    <div class="card-body">
        <form method="GET">
            <div class="filter-toolbar has-duplicate-filter">
                <div class="filter-title">
                    <span class="filter-icon"><i class="fas fa-filter"></i></span>
                    <span>Bộ lọc</span>
                </div>

                <div class="filter-field filter-keyword">
                    <label for="keyword">Từ khóa</label>
                    <div class="form-group mb-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input id="keyword"
                                   type="text"
                                   name="keyword"
                                   class="form-control"
                                   value="{{ request('keyword') }}"
                                   placeholder="Số yêu cầu, hóa đơn, khách hàng, công trình">
                        </div>
                    </div>
                </div>

                <div class="filter-field">
                    <label for="distribution_center_id">Trung tâm</label>
                    <div class="form-group mb-0">
                        <select id="distribution_center_id" name="distribution_center_id" class="form-control select2">
                            <option value="">Tất cả trung tâm</option>
                            @foreach($centers as $center)
                                <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                    {{ $center->code }} - {{ $center->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="filter-field">
                    <label for="status">Trạng thái</label>
                    <div class="form-group mb-0">
                        <select id="status" name="status" class="form-control select2">
                            <option value="">Tất cả trạng thái</option>
                            <option value="WAIT_DVKH" {{ request('status') == 'WAIT_DVKH' ? 'selected' : '' }}>Chờ DVKH</option>
                            <option value="WAIT_PTN" {{ request('status') == 'WAIT_PTN' ? 'selected' : '' }}>Đã chuyển PTN</option>
                            <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Đã trả lại</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field">
                    <label for="duplicate_invoice">Hóa đơn trùng</label>
                    <div class="form-group mb-0">
                        <select id="duplicate_invoice" name="duplicate_invoice" class="form-control select2">
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('duplicate_invoice') === '1' ? 'selected' : '' }}>Có trùng</option>
                            <option value="0" {{ request('duplicate_invoice') === '0' ? 'selected' : '' }}>Không trùng</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Lọc
                    </button>

                    <a href="{{ route('dvkh.requests.index') }}" class="btn btn-outline-secondary" title="Làm mới">
                        <i class="fas fa-sync"></i>
                    </a>

                    @if(request()->hasAny(['keyword', 'distribution_center_id', 'status', 'duplicate_invoice']))
                        <a href="{{ route('dvkh.requests.index') }}" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i> Xóa lọc
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card request-list-card">
    <div class="card-header bg-white">
        <div>
            <h3 class="card-title mb-0">
                <i class="fas fa-check-circle"></i> Danh sách yêu cầu DVKH
            </h3>
            <div class="text-muted small mt-1">Các yêu cầu đang chờ DVKH kiểm tra theo điều kiện lọc hiện tại.</div>
        </div>

        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $requests->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0 request-table">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Số yêu cầu</th>
                    <th>Trung tâm</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Ngày xuất hàng</th>
                    <th>Số hóa đơn</th>
                    <th>Ký tươi</th>
                    <th>Trạng thái</th>
                    <th style="width:190px" class="text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>{{ $requests->firstItem() + $loop->index }}</td>

                        <td>
                            <strong>{{ $item->request_no }}</strong>
                            <div class="text-muted small">
                                {{ optional($item->created_at)->format('d/m/Y H:i') }}
                            </div>
                            @include('certificate_requests.partials.request_type_badge', ['certificateRequest' => $item])
                            @include('certificate_requests.partials.urgent_badge', ['urgentRequest' => $item])
                        </td>

                        <td>{{ $item->distributionCenter->name ?? '-' }}</td>

                        <td>
                            <strong>{{ $item->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">
                                {{ $item->customer->project_name ?? '' }}
                            </div>
                        </td>

                        <td>{{ $item->delivery_date ? $item->delivery_date->format('d/m/Y') : '-' }}</td>

                        <td>
                            {{ $item->invoice_no ?: '-' }}
                            @if(($item->invoice_duplicate_count ?? 0) > 0)
                                <div class="mt-1">
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Trùng {{ $item->invoice_duplicate_count }} yêu cầu
                                    </span>
                                </div>
                            @endif
                        </td>

                        <td>
                            @if($item->require_hard_copy)
                                <span class="badge badge-warning">{{ $item->hard_copy_quantity }} bản</span>
                            @else
                                <span class="badge badge-light">Không</span>
                            @endif
                        </td>

                        <td>
                            @include('certificate_requests.partials.status_badge', ['certificateRequest' => $item])
                        </td>

                        <td class="text-center">
                            <a href="{{ route('dvkh.requests.show', $item) }}"
                               class="btn btn-sm btn-info"
                               title="Xem">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if($item->status === 'WAIT_DVKH')
                                @php
                                    $approveConfirm = ($item->invoice_duplicate_count ?? 0) > 0
                                        ? 'Số hóa đơn của yêu cầu này đang trùng với ' . $item->invoice_duplicate_count . ' yêu cầu khác. Bạn vẫn muốn xác nhận và chuyển sang PTN?'
                                        : 'Xác nhận yêu cầu này và chuyển sang PTN?';
                                @endphp
                                <form action="{{ route('dvkh.requests.approve', $item) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm({!! json_encode($approveConfirm, JSON_UNESCAPED_UNICODE) !!})">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Xác nhận">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>

                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        title="Trả lại"
                                        data-toggle="modal"
                                        data-target="#rejectModal{{ $item->id }}">
                                    <i class="fas fa-times"></i>
                                </button>

                                <div class="modal fade" id="rejectModal{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST"
                                              action="{{ route('dvkh.requests.reject', $item) }}"
                                              class="modal-content">
                                            @csrf

                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-times-circle"></i> Trả lại yêu cầu
                                                </h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>

                                            <div class="modal-body text-left">
                                                <p>
                                                    Yêu cầu:
                                                    <strong>{{ $item->request_no }}</strong>
                                                </p>

                                                <div class="form-group">
                                                    <label>Lý do trả lại <span class="text-danger">*</span></label>
                                                    <textarea name="reason"
                                                              class="form-control"
                                                              rows="4"
                                                              required></textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <button type="button"
                                                        class="btn btn-default"
                                                        data-dismiss="modal">
                                                    Đóng
                                                </button>

                                                <button class="btn btn-danger">
                                                    <i class="fas fa-times"></i> Trả lại
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <br>
                            Không có yêu cầu cần DVKH xử lý.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }}
                / {{ $requests->total() }} bản ghi
            </div>

            <div class="col-md-6">
                <div class="float-md-right">
                    {{ $requests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@stop
