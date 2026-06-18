@extends('adminlte::page')

@section('title', 'Trung tâm phân phối')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Trung tâm phân phối</h1>
        <small class="text-muted">Quản lý trung tâm phát sinh yêu cầu cấp phiếu CNCL</small>
    </div>

    @can('distribution_center.create')
        <a href="{{ route('distribution-centers.create') }}" class="btn btn-primary mt-2 mt-md-0">
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
                        placeholder="Mã, tên, email, điện thoại hoặc người liên hệ">
                </div>
            </div>
            <div class="col-lg-3 col-md-4">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng hoạt động</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-2 col-md-2">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
                    <a href="{{ route('distribution-centers.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-building"></i> Danh sách trung tâm</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $centers->total() }}</span>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:70px">STT</th>
                    <th style="width:130px">Mã</th>
                    <th>Tên trung tâm</th>
                    <th>Email</th>
                    <th style="width:140px">Điện thoại</th>
                    <th>Người liên hệ</th>
                    <th style="width:150px">Trạng thái</th>
                    <th style="width:140px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($centers as $center)
                    <tr>
                        <td>{{ $centers->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-primary">{{ $center->code }}</span></td>
                        <td>
                            <strong>{{ $center->name }}</strong>
                            @if($center->address)
                                <div class="text-muted small">{{ $center->address }}</div>
                            @endif
                        </td>
                        <td>{{ $center->email ?: '-' }}</td>
                        <td>{{ $center->phone ?: '-' }}</td>
                        <td>{{ $center->contact_person ?: '-' }}</td>
                        <td>
                            @if($center->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Hoạt động</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('distribution_center.update')
                                <a href="{{ route('distribution-centers.edit', $center) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan
                            @can('distribution_center.delete')
                                <form action="{{ route('distribution-centers.destroy', $center) }}" method="POST"
                                    class="d-inline" onsubmit="return confirm('Xóa trung tâm này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có dữ liệu trung tâm phân phối.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $centers->firstItem() ?? 0 }} - {{ $centers->lastItem() ?? 0 }} / {{ $centers->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $centers->links() }}</div>
            </div>
        </div>
    </div>
</div>
@stop
