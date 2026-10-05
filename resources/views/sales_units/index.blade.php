@extends('adminlte::page')

@section('title', 'Đơn vị bán hàng')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Đơn vị bán hàng</h1>
        <small class="text-muted">Danh mục đơn vị bán hàng theo từng Trung tâm phân phối</small>
    </div>

    <div class="btn-group mt-2 mt-md-0">
        @can('sales_unit.import')
            <a href="{{ route('sales-units.template') }}" class="btn btn-outline-secondary" data-download>
                <i class="fas fa-download"></i> File mẫu
            </a>
            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload"></i> Import
            </button>
        @endcan

        @can('sales_unit.export')
            <a href="{{ route('sales-units.export') }}" class="btn btn-outline-success" data-download>
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        @endcan

        @can('sales_unit.create')
            <a href="{{ route('sales-units.create') }}" class="btn btn-primary">
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

@if(session('sales_unit_import_errors'))
    <div class="alert alert-warning alert-dismissible fade show">
        <div class="font-weight-bold mb-2"><i class="fas fa-exclamation-triangle"></i> Chi tiết lỗi import</div>
        <ul class="mb-0 pl-3">
            @foreach(array_slice(session('sales_unit_import_errors'), 0, 20) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        @if(count(session('sales_unit_import_errors')) > 20)
            <div class="small mt-2">Còn {{ count(session('sales_unit_import_errors')) - 20 }} lỗi khác. Vui lòng kiểm tra lại file.</div>
        @endif
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc dữ liệu</h3>
    </div>
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-5 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}"
                           placeholder="Mã Bravo, tên DVBH, MST, SĐT, đại diện">
                </div>
            </div>

            @unless(auth()->user()->hasRole('TrungTam'))
                <div class="col-lg-3 col-md-4">
                    <div class="form-group">
                        <label>Trung tâm</label>
                        <select name="distribution_center_id" class="form-control select2">
                            <option value="">Tất cả</option>
                            @foreach($centers as $center)
                                <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                    {{ $center->code }} - {{ $center->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endunless

            <div class="col-lg-2 col-md-4">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang dùng</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng dùng</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-2">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
                    <a href="{{ route('sales-units.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-store"></i> Danh sách đơn vị bán hàng</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $salesUnits->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th style="width:120px">Mã Bravo</th>
                    @unless(auth()->user()->hasRole('TrungTam'))
                        <th style="width:160px">Trung tâm</th>
                    @endunless
                    <th>Tên DVBH</th>
                    <th>Địa chỉ</th>
                    <th style="width:120px">SĐT</th>
                    <th style="width:120px">MST</th>
                    <th style="width:140px">Số TK</th>
                    <th style="width:150px">Đại diện</th>
                    <th style="width:110px">Trạng thái</th>
                    <th style="width:150px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salesUnits as $salesUnit)
                    <tr>
                        <td>{{ $salesUnits->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-primary">{{ $salesUnit->code }}</span></td>
                        @unless(auth()->user()->hasRole('TrungTam'))
                            <td>{{ $salesUnit->distributionCenter?->code }} - {{ $salesUnit->distributionCenter?->name }}</td>
                        @endunless
                        <td>
                            <strong>{{ $salesUnit->name }}</strong>
                            @if($salesUnit->note)
                                <div class="text-muted small">{{ $salesUnit->note }}</div>
                            @endif
                        </td>
                        <td>{{ $salesUnit->address ?: '-' }}</td>
                        <td>{{ $salesUnit->phone ?: '-' }}</td>
                        <td>{{ $salesUnit->tax_code ?: '-' }}</td>
                        <td>{{ $salesUnit->bank_account ?: '-' }}</td>
                        <td>{{ $salesUnit->representative ?: '-' }}</td>
                        <td>
                            @if($salesUnit->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đang dùng</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('sales_unit.update')
                                <a href="{{ route('sales-units.edit', $salesUnit) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan

                            @can('sales_unit.delete')
                                @if($salesUnit->is_active)
                                    <form action="{{ route('sales-units.destroy', $salesUnit) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Ngừng sử dụng đơn vị bán hàng này? Dữ liệu cũ vẫn được giữ để tra cứu.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" title="Ngừng sử dụng"><i class="fas fa-ban"></i></button>
                                    </form>
                                @else
                                    <button class="btn btn-sm btn-secondary" disabled title="Đã ngừng"><i class="fas fa-ban"></i></button>
                                @endif
                            @endcan

                            @if(auth()->user()->hasRole('Admin') && (int) $salesUnit->certificate_requests_total_count === 0)
                                <form action="{{ route('sales-units.force-destroy', $salesUnit) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Xóa hẳn đơn vị bán hàng này khỏi CSDL? Chỉ nên dùng khi chưa phát sinh yêu cầu.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Xóa hẳn"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole('TrungTam') ? 10 : 11 }}" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có dữ liệu đơn vị bán hàng.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $salesUnits->firstItem() ?? 0 }} - {{ $salesUnits->lastItem() ?? 0 }} / {{ $salesUnits->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $salesUnits->links() }}</div>
            </div>
        </div>
    </div>
</div>

@can('sales_unit.import')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST"
              action="{{ route('sales-units.import') }}"
              enctype="multipart/form-data"
              class="modal-content"
              data-loading-lock
              data-loading-message="Đang kiểm tra file import đơn vị bán hàng. Vui lòng chờ...">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import"></i> Import đơn vị bán hàng</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="font-weight-bold mb-1">Quy tắc import</div>
                    <div>Trùng <strong>ma_bravo + trung tâm</strong> sẽ được cảnh báo trước. Chỉ khi xác nhận, hệ thống mới cập nhật dữ liệu cũ.</div>
                    <div>Nhập cột <code>ma_trung_tam</code> trong file, hoặc chọn sẵn một trung tâm bên dưới.</div>
                </div>

                <div class="form-group">
                    <label>Import vào trung tâm</label>
                    <select name="import_distribution_center_id" class="form-control select2">
                        <option value="">Theo cột ma_trung_tam trong file</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->code }} - {{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label>Chọn file Excel</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>

                <div class="small text-muted">
                    Các cột hỗ trợ: <code>ma_trung_tam</code>, <code>ma_bravo</code>, <code>ten_dvbh</code>,
                    <code>dia_chi_cua_hang</code>, <code>so_dt_lien_lac</code>, <code>ma_so_thue</code>,
                    <code>so_tai_khoan</code>, <code>dai_dien_chuc_vu</code>, <code>ghi_chu</code>, <code>dang_su_dung</code>.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-primary"><i class="fas fa-search"></i> Kiểm tra file</button>
            </div>
        </form>
    </div>
</div>

@if(session('sales_unit_import_preview'))
    @php($preview = session('sales_unit_import_preview'))
    <div class="modal fade" id="importPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST"
                  action="{{ route('sales-units.import') }}"
                  class="modal-content"
                  data-loading-lock
                  data-loading-message="Đang cập nhật đơn vị bán hàng. Vui lòng chờ...">
                @csrf
                <input type="hidden" name="temp_path" value="{{ $preview['temp_path'] }}">
                <input type="hidden" name="temp_token" value="{{ $preview['temp_token'] }}">
                <input type="hidden" name="confirm_update" value="1">
                <input type="hidden" name="import_distribution_center_id" value="{{ $preview['import_distribution_center_id'] }}">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Xác nhận cập nhật DVBH trùng mã</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <p>
                        File import có <strong>{{ $preview['update_count'] }}</strong> đơn vị bán hàng trùng mã và
                        <strong>{{ $preview['create_count'] }}</strong> đơn vị bán hàng mới.
                    </p>
                    <p class="mb-2">Nếu tiếp tục, các đơn vị trùng dưới đây sẽ được cập nhật bằng dữ liệu trong file Excel.</p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Dòng</th>
                                    <th>Trung tâm</th>
                                    <th>Mã Bravo</th>
                                    <th>Tên hiện tại</th>
                                    <th>Tên trong file</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preview['duplicates'] as $duplicate)
                                    <tr>
                                        <td>{{ $duplicate['line'] }}</td>
                                        <td>{{ $duplicate['center'] }}</td>
                                        <td><strong>{{ $duplicate['code'] }}</strong></td>
                                        <td>{{ $duplicate['name'] }}</td>
                                        <td>{{ $duplicate['new_name'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($preview['total_duplicates'] > count($preview['duplicates']))
                        <div class="text-muted small">
                            Chỉ hiển thị 30 dòng đầu. Còn {{ $preview['total_duplicates'] - count($preview['duplicates']) }} dòng trùng khác.
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <a href="{{ route('sales-units.index') }}" class="btn btn-default">Hủy</a>
                    <button class="btn btn-warning"><i class="fas fa-check"></i> Xác nhận cập nhật</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endcan
@stop

@section('js')
@if(session('sales_unit_import_preview'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.jQuery) {
                jQuery('#importPreviewModal').modal('show');
            }
        });
    </script>
@endif
@stop
