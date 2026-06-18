@extends('adminlte::page')

@section('title', 'Cấu hình SLA')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Cấu hình SLA</h1>
        <small class="text-muted">Quản lý thời gian xử lý DVKH, PTN và toàn trình</small>
    </div>

    <div class="btn-group mt-2 mt-md-0">
        @can('sla.import')
            <a href="{{ route('sla-configs.template') }}" class="btn btn-outline-secondary">
                <i class="fas fa-download"></i> File mẫu
            </a>
            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload"></i> Import
            </button>
        @endcan

        @can('sla.export')
            <a href="{{ route('sla-configs.export') }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        @endcan

        @can('sla.create')
            <a href="{{ route('sla-configs.create') }}" class="btn btn-primary">
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
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc dữ liệu</h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-4 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Mã, tên hoặc mô tả SLA"
                           value="{{ request('keyword') }}">
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Công đoạn</label>
                    <select name="process_step" class="form-control select2">
                        <option value="">Tất cả công đoạn</option>
                        @foreach($processSteps as $key => $value)
                            <option value="{{ $key }}" {{ request('process_step') == $key ? 'selected' : '' }}>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang sử dụng</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng sử dụng</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
                    <a href="{{ route('sla-configs.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-clock"></i> Danh sách cấu hình SLA</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $slaConfigs->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th style="width:140px">Mã SLA</th>
                    <th>Tên SLA</th>
                    <th style="width:180px">Công đoạn</th>
                    <th style="width:130px">Cảnh báo</th>
                    <th style="width:130px">Quá hạn</th>
                    <th style="width:140px">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slaConfigs as $item)
                    <tr>
                        <td>{{ $slaConfigs->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-primary">{{ $item->code }}</span></td>
                        <td>
                            <strong>{{ $item->name }}</strong>
                            @if($item->description)
                                <div class="text-muted small">{{ $item->description }}</div>
                            @endif
                        </td>
                        <td>{{ $item->process_step_name }}</td>
                        <td><span class="badge badge-warning">{{ $item->warning_minutes }} phút</span></td>
                        <td><span class="badge badge-danger">{{ $item->limit_minutes }} phút</span></td>
                        <td>
                            @if($item->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đang dùng</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('sla.update')
                                <a href="{{ route('sla-configs.edit', $item) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan

                            @can('sla.delete')
                                <form action="{{ route('sla-configs.destroy', $item) }}" method="POST"
                                      class="d-inline" onsubmit="return confirm('Xóa cấu hình SLA này?')">
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
                            Chưa có dữ liệu cấu hình SLA.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $slaConfigs->firstItem() ?? 0 }} - {{ $slaConfigs->lastItem() ?? 0 }} / {{ $slaConfigs->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $slaConfigs->links() }}</div>
            </div>
        </div>
    </div>
</div>

@can('sla.import')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('sla-configs.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import"></i> Import cấu hình SLA</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    File import cần đúng định dạng cột:
                    <br>
                    <strong>ma_sla, ten_sla, cong_doan, canh_bao_phut, qua_han_phut, mo_ta</strong>
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
