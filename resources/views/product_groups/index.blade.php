@extends('adminlte::page')

@section('title', 'Nhóm sản phẩm')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Nhóm sản phẩm</h1>
        <small class="text-muted">Quản lý nhóm sản phẩm, tiêu chuẩn áp dụng và dữ liệu nền cấp phiếu CNCL</small>
    </div>

    <div class="btn-group mt-2 mt-md-0">
        @can('product_group.import')
            <a href="{{ route('product-groups.template') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download"></i> File mẫu
            </a>
            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload"></i> Import
            </button>
        @endcan

        @can('product_group.export')
            <a href="{{ route('product-groups.export') }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        @endcan

        @can('product_group.create')
            <a href="{{ route('product-groups.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm mới
            </a>
        @endcan
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

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-7 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}"
                        placeholder="Mã hoặc tên nhóm sản phẩm">
                </div>
            </div>
            <div class="col-lg-3 col-md-4">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang dùng</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-2">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
                    <a href="{{ route('product-groups.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-layer-group"></i> Danh sách nhóm sản phẩm</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $groups->total() }}</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">STT</th>
                    <th style="width:160px">Mã nhóm</th>
                    <th>Tên nhóm</th>
                    <th>Ghi chú</th>
                    <th style="width:140px">Trạng thái</th>
                    <th style="width:140px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td>{{ $groups->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-primary">{{ $group->code }}</span></td>
                        <td><strong>{{ $group->name }}</strong></td>
                        <td class="text-muted">{{ $group->description ?: '-' }}</td>
                        <td>
                            @if($group->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đang dùng</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('product_group.update')
                                <a href="{{ route('product-groups.edit', $group) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            @can('product_group.delete')
                                <form action="{{ route('product-groups.destroy', $group) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Xóa nhóm sản phẩm này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có dữ liệu nhóm sản phẩm.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $groups->firstItem() ?? 0 }} - {{ $groups->lastItem() ?? 0 }} / {{ $groups->total() }} bản ghi
            </div>
            <div class="col-md-6"><div class="float-md-right">{{ $groups->links() }}</div></div>
        </div>
    </div>
</div>

@can('product_group.import')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('product-groups.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import"></i> Import nhóm sản phẩm</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    File import cần đúng định dạng: <strong>ma_nhom_san_pham, ten_nhom_san_pham, tieu_chuan</strong>
                </div>
                <div class="form-group">
                    <label>Chọn file Excel</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-primary"><i class="fas fa-upload"></i> Import dữ liệu</button>
            </div>
        </form>
    </div>
</div>
@endcan
@stop
