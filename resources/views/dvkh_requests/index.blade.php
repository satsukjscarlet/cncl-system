@extends('adminlte::page')

@section('title', 'DVKH kiểm tra yêu cầu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .dvkh-metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .dvkh-metric {
            border: 1px solid #dde3ea;
            border-left: 4px solid #17a2b8;
            background: #fff;
            border-radius: 6px;
            padding: 12px 14px;
            min-height: 78px;
            color: inherit;
        }

        .dvkh-metric:hover {
            color: inherit;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .dvkh-metric strong {
            display: block;
            font-size: 24px;
            line-height: 1;
        }

        .dvkh-metric span {
            color: #5b6675;
            font-size: 13px;
        }

        .dvkh-metric.warning { border-left-color: #ffc107; }
        .dvkh-metric.danger { border-left-color: #dc3545; }

        .filter-toolbar.dvkh-filter-toolbar {
            align-items: end;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .filter-toolbar.dvkh-filter-toolbar .filter-title {
            grid-column: 1 / -1;
            min-height: auto;
            padding-bottom: 0;
        }

        .filter-toolbar.dvkh-filter-toolbar .filter-keyword {
            grid-column: span 2;
        }

        .filter-toolbar.dvkh-filter-toolbar .filter-actions {
            grid-column: 1 / -1;
            justify-content: flex-end;
        }

        .dvkh-row-warning { background: #fff8e1; }
        .dvkh-row-overdue { background: #fff1f1; }

        .dvkh-actions {
            display: inline-flex;
            gap: 4px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .dvkh-actions .btn {
            min-width: 34px;
        }

        .dvkh-table {
            min-width: 1120px;
        }

        .request-list-card .card-header {
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 1399.98px) {
            .filter-toolbar.dvkh-filter-toolbar {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 991.98px) {
            .filter-toolbar.dvkh-filter-toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-toolbar.dvkh-filter-toolbar .filter-keyword {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 575.98px) {
            .filter-toolbar.dvkh-filter-toolbar {
                grid-template-columns: 1fr;
            }

            .filter-toolbar.dvkh-filter-toolbar .filter-actions {
                justify-content: flex-start;
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

<div class="dvkh-metric-grid">
    <a class="dvkh-metric" href="{{ route('dvkh.requests.index', ['status' => 'WAIT_DVKH']) }}">
        <strong>{{ $metrics['waiting'] ?? 0 }}</strong>
        <span>Chờ DVKH xử lý</span>
    </a>
    <a class="dvkh-metric danger" href="{{ route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'urgent' => 1]) }}">
        <strong>{{ $metrics['urgent'] ?? 0 }}</strong>
        <span>Yêu cầu gấp</span>
    </a>
    <a class="dvkh-metric warning" href="{{ route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'duplicate_invoice' => 1]) }}">
        <strong>{{ $metrics['duplicate'] ?? 0 }}</strong>
        <span>Hóa đơn trùng</span>
    </a>
    <a class="dvkh-metric warning" href="{{ route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'sla' => 'warning']) }}">
        <strong>{{ $metrics['warning'] ?? 0 }}</strong>
        <span>Gần quá hạn SLA</span>
    </a>
    <a class="dvkh-metric danger" href="{{ route('dvkh.requests.index', ['status' => 'WAIT_DVKH', 'sla' => 'overdue']) }}">
        <strong>{{ $metrics['overdue'] ?? 0 }}</strong>
        <span>Quá hạn SLA</span>
    </a>
    <a class="dvkh-metric" href="{{ route('dvkh.requests.index', ['status' => 'WAIT_PTN']) }}">
        <strong>{{ $metrics['transferred_today'] ?? 0 }}</strong>
        <span>Đã chuyển PTN hôm nay</span>
    </a>
</div>

<div class="card request-filter-card">
    <div class="card-body">
        <form method="GET">
            <div class="filter-toolbar dvkh-filter-toolbar">
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
                            <input id="keyword" type="text" name="keyword" class="form-control"
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
                            <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Tất cả trạng thái</option>
                            <option value="WAIT_DVKH" {{ $statusFilter == 'WAIT_DVKH' ? 'selected' : '' }}>Chờ DVKH</option>
                            <option value="WAIT_PTN" {{ $statusFilter == 'WAIT_PTN' ? 'selected' : '' }}>Đã chuyển PTN</option>
                            <option value="CANCELLED" {{ $statusFilter == 'CANCELLED' ? 'selected' : '' }}>Đã trả lại</option>
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

                <div class="filter-field">
                    <label for="urgent">Yêu cầu gấp</label>
                    <div class="form-group mb-0">
                        <select id="urgent" name="urgent" class="form-control select2">
                            <option value="">Tất cả</option>
                            <option value="1" {{ request('urgent') === '1' ? 'selected' : '' }}>Chỉ yêu cầu gấp</option>
                            <option value="0" {{ request('urgent') === '0' ? 'selected' : '' }}>Không gấp</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field">
                    <label for="sla">SLA</label>
                    <div class="form-group mb-0">
                        <select id="sla" name="sla" class="form-control select2">
                            <option value="">Tất cả</option>
                            <option value="normal" {{ request('sla') === 'normal' ? 'selected' : '' }}>Bình thường</option>
                            <option value="warning" {{ request('sla') === 'warning' ? 'selected' : '' }}>Gần quá hạn</option>
                            <option value="overdue" {{ request('sla') === 'overdue' ? 'selected' : '' }}>Quá hạn</option>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Lọc</button>
                    <a href="{{ route('dvkh.requests.index') }}" class="btn btn-outline-secondary" title="Làm mới">
                        <i class="fas fa-sync"></i>
                    </a>
                    @if(request()->hasAny(['keyword', 'distribution_center_id', 'status', 'duplicate_invoice', 'urgent', 'sla']))
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
            <div class="text-muted small mt-1">Mặc định chỉ hiển thị yêu cầu đang chờ DVKH xử lý. Có thể mở rộng bằng bộ lọc trạng thái.</div>
        </div>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $requests->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0 request-table dvkh-table">
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
                    <th style="width:132px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $item)
                    @php
                        $rowClass = $item->sla_level === 'overdue' ? 'dvkh-row-overdue' : ($item->sla_level === 'warning' ? 'dvkh-row-warning' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>{{ $requests->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $item->request_no }}</strong>
                            <div class="text-muted small">{{ optional($item->created_at)->format('d/m/Y H:i') }}</div>
                            @include('certificate_requests.partials.request_type_badge', ['certificateRequest' => $item])
                            @include('certificate_requests.partials.urgent_badge', ['urgentRequest' => $item])
                            @if($item->sla_level === 'overdue')
                                <div class="mt-1"><span class="badge badge-danger"><i class="fas fa-clock"></i> Quá SLA</span></div>
                            @elseif($item->sla_level === 'warning')
                                <div class="mt-1"><span class="badge badge-warning"><i class="fas fa-clock"></i> Gần quá SLA</span></div>
                            @endif
                        </td>
                        <td>{{ $item->distributionCenter->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $item->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $item->customer->project_name ?? '' }}</div>
                        </td>
                        <td>{{ $item->delivery_date ? $item->delivery_date->format('d/m/Y') : '-' }}</td>
                        <td>
                            {{ $item->invoice_no ?: '-' }}
                            @if(($item->invoice_duplicate_count ?? 0) > 0)
                                <div class="mt-1">
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Trùng {{ $item->invoice_duplicate_count }} yêu cầu
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
                        <td>@include('certificate_requests.partials.status_badge', ['certificateRequest' => $item])</td>
                        <td class="text-center">
                            <div class="dvkh-actions">
                                <a href="{{ route('dvkh.requests.show', $item) }}" class="btn btn-sm btn-info" title="Xem">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if($item->status === 'WAIT_DVKH')
                                    @php
                                        $approveConfirm = ($item->invoice_duplicate_count ?? 0) > 0
                                            ? 'Số hóa đơn của yêu cầu này đang trùng với ' . $item->invoice_duplicate_count . ' yêu cầu khác. Bạn vẫn muốn xác nhận và chuyển sang PTN?'
                                            : 'Xác nhận yêu cầu này và chuyển sang PTN?';
                                    @endphp
                                    <form action="{{ route('dvkh.requests.approve', $item) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm({!! json_encode($approveConfirm, JSON_UNESCAPED_UNICODE) !!})">
                                        @csrf
                                        <button class="btn btn-sm btn-success" title="Xác nhận">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <button type="button" class="btn btn-sm btn-danger" title="Trả lại" data-toggle="modal" data-target="#rejectModal{{ $item->id }}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>

                            @if($item->status === 'WAIT_DVKH')
                                <div class="modal fade" id="rejectModal{{ $item->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('dvkh.requests.reject', $item) }}" class="modal-content">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="fas fa-times-circle"></i> Trả lại yêu cầu</h5>
                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body text-left">
                                                <p>Yêu cầu: <strong>{{ $item->request_no }}</strong></p>
                                                <div class="form-group">
                                                    <label>Lý do trả lại <span class="text-danger">*</span></label>
                                                    <textarea name="reason" class="form-control" rows="4" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                                <button class="btn btn-danger"><i class="fas fa-times"></i> Trả lại</button>
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
                            <i class="fas fa-database fa-2x mb-2"></i><br>
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
                Hiển thị {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} / {{ $requests->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $requests->links() }}</div>
            </div>
        </div>
    </div>
</div>

@stop
