@extends('adminlte::page')

@section('title', 'Đơn vị tính')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Đơn vị tính</h1>
        <small class="text-muted">Quản lý danh mục đơn vị tính dùng khi tạo sản phẩm</small>
    </div>

    @can('product_unit.create')
        <a href="{{ route('product-units.create') }}" class="btn btn-primary mt-2 mt-md-0">
            <i class="fas fa-plus"></i> Thêm mới
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
                        placeholder="Mã hoặc tên đơn vị tính">
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
                    <a href="{{ route('product-units.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-ruler-combined"></i> Danh sách đơn vị tính</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $units->total() }}</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">STT</th>
                    <th style="width:160px">@include('partials.sort_link', ['column' => 'code', 'label' => 'Mã ĐVT'])</th>
                    <th>@include('partials.sort_link', ['column' => 'name', 'label' => 'Tên đơn vị tính'])</th>
                    <th>Ghi chú</th>
                    <th style="width:120px" class="text-right">Sản phẩm</th>
                    <th style="width:140px">@include('partials.sort_link', ['column' => 'is_active', 'label' => 'Trạng thái'])</th>
                    <th style="width:170px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $unit)
                    <tr>
                        <td>{{ $units->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-primary">{{ $unit->code }}</span></td>
                        <td><strong>{{ $unit->name }}</strong></td>
                        <td class="text-muted">{{ $unit->description ?: '-' }}</td>
                        <td class="text-right">{{ number_format($unit->products_total_count) }}</td>
                        <td>
                            @if($unit->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đang dùng</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('product_unit.update')
                                <a href="{{ route('product-units.edit', $unit) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            @can('product_unit.delete')
                                @if($unit->is_active)
                                    <form action="{{ route('product-units.destroy', $unit) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Ngừng sử dụng đơn vị tính này? Các sản phẩm cũ vẫn giữ dữ liệu đã chọn.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Ngừng sử dụng"><i class="fas fa-ban"></i></button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-secondary" title="Đã ngừng sử dụng" disabled><i class="fas fa-ban"></i></button>
                                @endif
                            @endcan
                            @if(auth()->user()->hasRole('Admin') && (int) $unit->products_total_count === 0)
                                <form action="{{ route('product-units.force-destroy', $unit) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Xóa hẳn đơn vị tính này khỏi CSDL? Chỉ nên dùng khi đơn vị tính chưa có sản phẩm liên kết.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Xóa hẳn"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có dữ liệu đơn vị tính.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $units->firstItem() ?? 0 }} - {{ $units->lastItem() ?? 0 }} / {{ $units->total() }} bản ghi
            </div>
            <div class="col-md-6"><div class="float-md-right">{{ $units->links() }}</div></div>
        </div>
    </div>
</div>
@stop
