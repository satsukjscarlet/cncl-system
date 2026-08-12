@extends('adminlte::page')

@section('title', 'Yêu cầu cấp phiếu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
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
            <div class="filter-toolbar">
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
                            <option value="SIGN_READY" {{ request('status') == 'SIGN_READY' ? 'selected' : '' }}>Chờ Trưởng PTN ký</option>
                            <option value="SIGN_PENDING" {{ request('status') == 'SIGN_PENDING' ? 'selected' : '' }}>Đang chờ ký số</option>
                            <option value="SIGN_EXPIRED" {{ request('status') == 'SIGN_EXPIRED' ? 'selected' : '' }}>Quá hạn ký số</option>
                            <option value="">Tất cả trạng thái</option>
                            <option value="DRAFT" {{ request('status') == 'DRAFT' ? 'selected' : '' }}>Nháp</option>
                            <option value="WAIT_DVKH" {{ request('status') == 'WAIT_DVKH' ? 'selected' : '' }}>Chờ DVKH</option>
                            <option value="WAIT_PTN" {{ request('status') == 'WAIT_PTN' ? 'selected' : '' }}>Chờ PTN</option>
                            <option value="PTN_PROCESSING" {{ request('status') == 'PTN_PROCESSING' ? 'selected' : '' }}>PTN xử lý</option>
                            <option value="SIGNED" {{ request('status') == 'SIGNED' ? 'selected' : '' }}>Đã ký số</option>
                            <option value="COMPLETED" {{ request('status') == 'COMPLETED' ? 'selected' : '' }}>Hoàn tất</option>
                            <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Hủy/Trả lại</option>
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

                    @if(request()->hasAny(['keyword', 'distribution_center_id', 'status']))
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
            <div class="text-muted small mt-1">Hiển thị các yêu cầu cấp phiếu theo điều kiện lọc hiện tại.</div>
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

                            @if(in_array($item->status, ['DRAFT', 'WAIT_DVKH']))
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
                            Chưa có yêu cầu cấp phiếu.
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
