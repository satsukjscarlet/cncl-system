@extends('adminlte::page')

@section('title', 'Yêu cầu cấp phiếu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260819-1') }}">
    <style>
        .request-status-tabs {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 2px 0 10px;
        }

        .request-status-tab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            border: 1px solid #d6dde8;
            background: #fff;
            border-radius: 8px;
            padding: 8px 10px;
            color: #344054;
            font-weight: 600;
        }

        .request-status-tab:hover {
            color: #0d6efd;
            text-decoration: none;
            border-color: #9ec5fe;
        }

        .request-status-tab.active {
            color: #fff;
            background: #0d6efd;
            border-color: #0d6efd;
        }

        .request-status-tab .count {
            min-width: 24px;
            padding: 2px 6px;
            border-radius: 999px;
            background: #eef2f7;
            color: #344054;
            text-align: center;
            font-size: 12px;
        }

        .request-status-tab.active .count {
            background: rgba(255, 255, 255, .2);
            color: #fff;
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Yêu cầu cấp phiếu CNCL</h1>
        <small class="text-muted">Quản lý đề nghị cấp Phiếu Chứng nhận Chất lượng</small>
    </div>

    @can('request.create')
        <a href="{{ route('certificate-requests.create') }}" class="btn btn-primary mt-2 mt-md-0">
            <i class="fas fa-plus"></i> Tạo yêu cầu
        </a>
    @endcan
</div>
@stop

@section('content')
@php
    $currentSort = $sort ?? request('sort', 'created_at');
    $currentDirection = $direction ?? request('direction', 'desc');
    $currentPerPage = $perPage ?? request('per_page', 15);
    $currentGroup = $currentGroup ?? request('status_group', 'processing');
    $isCenterUser = auth()->user()->hasRole('TrungTam');

    $sortUrl = function (string $column) use ($currentSort, $currentDirection) {
        $nextDirection = $currentSort === $column && $currentDirection === 'asc' ? 'desc' : 'asc';
        $query = request()->query();
        unset($query['page']);
        $query['sort'] = $column;
        $query['direction'] = $nextDirection;

        return route('certificate-requests.index', $query);
    };

    $sortIcon = function (string $column) use ($currentSort, $currentDirection) {
        if ($currentSort !== $column) {
            return '<i class="fas fa-sort text-muted"></i>';
        }

        return $currentDirection === 'asc'
            ? '<i class="fas fa-sort-up text-primary"></i>'
            : '<i class="fas fa-sort-down text-primary"></i>';
    };

    $tabUrl = function (string $group) {
        $query = request()->query();
        unset($query['page'], $query['status']);
        $query['status_group'] = $group;

        if ($group === 'processing') {
            unset($query['status_group']);
        }

        return route('certificate-requests.index', $query);
    };

    $tabs = [
        'processing' => ['label' => 'Đang xử lý', 'icon' => 'fas fa-tasks', 'count' => $tabCounts['processing'] ?? 0],
        'draft' => ['label' => 'Nháp', 'icon' => 'fas fa-edit', 'count' => $tabCounts['draft'] ?? 0],
        'wait_dvkh' => ['label' => 'Chờ DVKH', 'icon' => 'fas fa-user-check', 'count' => $tabCounts['wait_dvkh'] ?? 0],
        'wait_ptn' => ['label' => 'Chờ PTN', 'icon' => 'fas fa-vials', 'count' => $tabCounts['wait_ptn'] ?? 0],
        'sign_ready' => ['label' => 'Chờ ký', 'icon' => 'fas fa-pen-nib', 'count' => $tabCounts['sign_ready'] ?? 0],
        'sign_pending' => ['label' => 'Đang chờ app', 'icon' => 'fas fa-mobile-alt', 'count' => $tabCounts['sign_pending'] ?? 0],
        'sign_expired' => ['label' => 'Quá hạn ký', 'icon' => 'fas fa-hourglass-end', 'count' => $tabCounts['sign_expired'] ?? 0],
        'completed' => ['label' => 'Hoàn thành', 'icon' => 'fas fa-check-circle', 'count' => $tabCounts['completed'] ?? 0],
        'cancelled' => ['label' => 'Hủy / trả lại', 'icon' => 'fas fa-ban', 'count' => $tabCounts['cancelled'] ?? 0],
        'all' => ['label' => 'Tất cả', 'icon' => 'fas fa-layer-group', 'count' => $tabCounts['all'] ?? 0],
    ];

    $hasActiveFilter = request()->hasAny(['keyword', 'distribution_center_id', 'status', 'status_group', 'sort', 'direction', 'per_page']);
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

<div class="request-status-tabs">
    @foreach($tabs as $group => $tab)
        <a href="{{ $tabUrl($group) }}"
           class="request-status-tab {{ $currentGroup === $group && !request()->filled('status') ? 'active' : '' }}">
            <i class="{{ $tab['icon'] }}"></i>
            <span>{{ $tab['label'] }}</span>
            <span class="count">{{ number_format($tab['count']) }}</span>
        </a>
    @endforeach
</div>

<div class="card request-filter-card">
    <div class="card-body">
        <form method="GET">
            <input type="hidden" name="sort" value="{{ $currentSort }}">
            <input type="hidden" name="direction" value="{{ $currentDirection }}">
            @if($currentGroup && !request()->filled('status'))
                <input type="hidden" name="status_group" value="{{ $currentGroup }}">
            @endif

            <div class="filter-toolbar certificate-request-filter-toolbar">
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

                @unless($isCenterUser)
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
                @endunless

                <div class="filter-field">
                    <label for="status">Trạng thái chi tiết</label>
                    <div class="form-group mb-0">
                        <select id="status" name="status" class="form-control select2">
                            <option value="">Theo tab đang chọn</option>
                            <option value="SIGN_READY" {{ request('status') == 'SIGN_READY' ? 'selected' : '' }}>Chờ Trưởng PTN ký</option>
                            <option value="SIGN_PENDING" {{ request('status') == 'SIGN_PENDING' ? 'selected' : '' }}>Đang chờ ký số</option>
                            <option value="SIGN_EXPIRED" {{ request('status') == 'SIGN_EXPIRED' ? 'selected' : '' }}>Quá hạn ký số</option>
                            <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Nháp</option>
                            <option value="WAIT_DVKH" {{ request('status') == 'WAIT_DVKH' ? 'selected' : '' }}>Chờ DVKH</option>
                            <option value="WAIT_PTN" {{ request('status') == 'WAIT_PTN' ? 'selected' : '' }}>Chờ PTN lập phiếu</option>
                            <option value="PTN_PROCESSING" {{ request('status') == 'PTN_PROCESSING' ? 'selected' : '' }}>Đã lập phiếu - chờ ký</option>
                            <option value="SIGNED" {{ request('status') == 'SIGNED' ? 'selected' : '' }}>Đã ký số</option>
                            <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Hoàn tất</option>
                            <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Hủy / trả lại</option>
                        </select>
                    </div>
                </div>

                <div class="filter-field filter-per-page">
                    <label for="per_page">Hiển thị</label>
                    <div class="form-group mb-0">
                        <select id="per_page" name="per_page" class="form-control">
                            @foreach([15, 30, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) $currentPerPage === $size ? 'selected' : '' }}>{{ $size }} dòng</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Lọc
                    </button>

                    <a href="{{ route('certificate-requests.index') }}" class="btn btn-outline-secondary" title="Làm mới">
                        <i class="fas fa-sync"></i>
                    </a>

                    @if($hasActiveFilter)
                        <a href="{{ route('certificate-requests.index') }}" class="btn btn-outline-danger">
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
            <h3 class="card-title mb-0"><i class="fas fa-file-alt"></i> Danh sách yêu cầu</h3>
            <div class="text-muted small mt-1">
                Mặc định chỉ hiển thị yêu cầu đang xử lý. Bấm tab Hoàn thành hoặc Tất cả để tra cứu phiếu cũ.
            </div>
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
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('request_no') }}">
                            Số yêu cầu {!! $sortIcon('request_no') !!}
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('center') }}">
                            Trung tâm {!! $sortIcon('center') !!}
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('customer') }}">
                            Khách hàng / Công trình {!! $sortIcon('customer') !!}
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('delivery_date') }}">
                            Ngày xuất hàng {!! $sortIcon('delivery_date') !!}
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('invoice_no') }}">
                            Số hóa đơn {!! $sortIcon('invoice_no') !!}
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('hard_copy_quantity') }}">
                            Ký tươi {!! $sortIcon('hard_copy_quantity') !!}
                        </a>
                    </th>
                    <th>
                        <a class="sort-link" href="{{ $sortUrl('status') }}">
                            Trạng thái {!! $sortIcon('status') !!}
                        </a>
                    </th>
                    <th style="width:150px" class="text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse($requests as $item)
                    <tr>
                        <td>{{ $requests->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $item->request_no }}</strong>
                            <div class="text-muted small">{{ optional($item->created_at)->format('d/m/Y H:i') }}</div>
                            @include('certificate_requests.partials.request_type_badge', ['certificateRequest' => $item])
                            @include('certificate_requests.partials.urgent_badge', ['urgentRequest' => $item])
                        </td>
                        <td>{{ $item->distributionCenter->name ?? '-' }}</td>
                        <td>
                            <strong>{{ $item->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $item->customer->project_name ?? '' }}</div>
                        </td>
                        <td>{{ $item->delivery_date ? $item->delivery_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $item->invoice_no ?: '-' }}</td>
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
                            <a href="{{ route('certificate-requests.show', $item) }}" class="btn btn-sm btn-info" title="Xem">
                                <i class="fas fa-eye"></i>
                            </a>

                            @if($item->status === 'DRAFT')
                                @can('request.update')
                                    <a href="{{ route('certificate-requests.edit', $item) }}" class="btn btn-sm btn-warning" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan

                                @can('request.delete')
                                    <form action="{{ route('certificate-requests.destroy', $item) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Xóa yêu cầu này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                    </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có yêu cầu cấp phiếu phù hợp.
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
