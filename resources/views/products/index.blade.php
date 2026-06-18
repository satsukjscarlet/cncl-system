@extends('adminlte::page')

@section('title', 'Sản phẩm')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Sản phẩm</h1>
        <small class="text-muted">Quản lý sản phẩm, quy cách và tiêu chuẩn phục vụ cấp phiếu CNCL</small>
    </div>

    <div class="btn-group mt-2 mt-md-0">
        @can('product.import')
            <a href="{{ route('products.template') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download"></i> File mẫu
            </a>
            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload"></i> Import
            </button>
        @endcan

        @can('product.export')
            <a href="{{ route('products.export') }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        @endcan

        @can('product.create')
            <a href="{{ route('products.create') }}" class="btn btn-primary">
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
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}"
                        placeholder="Mã, tên, kích thước, tiêu chuẩn...">
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Nhóm sản phẩm</label>
                    <select name="product_group_id" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('product_group_id') == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Tiêu chuẩn</label>
                    <select name="quality_standard_id" class="form-control select2">
                        <option value="">Tất cả</option>
                        @foreach($standards as $standard)
                            <option value="{{ $standard->id }}" {{ request('quality_standard_id') == $standard->id ? 'selected' : '' }}>
                                {{ $standard->code }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang dùng</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-1 col-md-12">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary" title="Tìm"><i class="fas fa-search"></i></button>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-box"></i> Danh sách sản phẩm</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $products->total() }}</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Nhóm</th>
                    <th style="width:130px">Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th style="width:120px">Kích thước</th>
                    <th>Tiêu chuẩn</th>
                    <th style="width:120px">Trạng thái</th>
                    <th style="width:140px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td>{{ $products->firstItem() + $loop->index }}</td>
                        <td>{{ $product->group->name ?? '-' }}</td>
                        <td><span class="badge badge-primary">{{ $product->product_code }}</span></td>
                        <td><strong>{{ $product->product_name }}</strong></td>
                        <td>{{ $product->nominal_size ?: '-' }}</td>
                        <td>{{ $product->qualityStandard->name ?? '-' }}</td>
                        <td>
                            @if($product->is_active)
                                <span class="badge badge-success">Đang dùng</span>
                            @else
                                <span class="badge badge-danger">Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('product.update')
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            @can('product.delete')
                                <form action="{{ route('products.destroy', $product) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Xóa sản phẩm này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có dữ liệu sản phẩm.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $products->firstItem() ?? 0 }} - {{ $products->lastItem() ?? 0 }} / {{ $products->total() }} bản ghi
            </div>
            <div class="col-md-6"><div class="float-md-right">{{ $products->links() }}</div></div>
        </div>
    </div>
</div>

@can('product.import')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('products.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import"></i> Import sản phẩm</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    File import cần đúng định dạng cột:
                    <strong>ma_nhom_san_pham, ten_nhom_san_pham, ma_san_pham, ten_san_pham, don_vi_tinh, kich_thuoc_danh_nghia, yeu_cau_ky_thuat, tieu_chuan_san_pham, mau_phieu, ghi_chu</strong>
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
