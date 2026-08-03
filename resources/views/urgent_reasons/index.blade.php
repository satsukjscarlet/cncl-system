@extends('adminlte::page')

@section('title', 'Lý do yêu cầu gấp')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Lý do yêu cầu gấp</h1>
        <small class="text-muted">Quản lý danh mục lý do dùng khi mở yêu cầu cung cấp gấp</small>
    </div>

    @can('urgent_reason.create')
        <a href="{{ route('urgent-reasons.create') }}" class="btn btn-primary mt-2 mt-md-0">
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
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc dữ liệu</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-7 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           value="{{ request('keyword') }}"
                           placeholder="Mã, tên hoặc mô tả lý do">
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
                    <a href="{{ route('urgent-reasons.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-bolt"></i> Danh sách lý do gấp</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $urgentReasons->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">STT</th>
                    <th style="width:220px">Mã lý do</th>
                    <th>Tên lý do</th>
                    <th>Mô tả</th>
                    <th style="width:140px">Trạng thái</th>
                    <th style="width:140px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($urgentReasons as $urgentReason)
                    <tr>
                        <td>{{ $urgentReasons->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-danger">{{ $urgentReason->code }}</span></td>
                        <td><strong>{{ $urgentReason->name }}</strong></td>
                        <td class="text-muted">{{ $urgentReason->description ?: '-' }}</td>
                        <td>
                            @if($urgentReason->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đang dùng</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('urgent_reason.update')
                                <a href="{{ route('urgent-reasons.edit', $urgentReason) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan

                            @can('urgent_reason.delete')
                                <form action="{{ route('urgent-reasons.destroy', $urgentReason) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Xóa lý do gấp này?')">
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
                            Chưa có dữ liệu lý do yêu cầu gấp.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $urgentReasons->firstItem() ?? 0 }} - {{ $urgentReasons->lastItem() ?? 0 }} / {{ $urgentReasons->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $urgentReasons->links() }}</div>
            </div>
        </div>
    </div>
</div>
@stop
