@extends('adminlte::page')

@section('title', 'Khách hàng - Công trình')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Khách hàng - Công trình</h1>
        <small class="text-muted">Quản lý khách hàng, công trình và email nhận phiếu CNCL</small>
    </div>

    <div class="btn-group mt-2 mt-md-0">
        @can('customer.import')
            <a href="{{ route('customers.template') }}" class="btn btn-outline-secondary" data-download>
                <i class="fas fa-download"></i> File mẫu
            </a>
            <button type="button" class="btn btn-outline-info" data-toggle="modal" data-target="#importModal">
                <i class="fas fa-upload"></i> Import
            </button>
        @endcan

        @can('customer.export')
            <a href="{{ route('customers.export') }}" class="btn btn-outline-success" data-download>
                <i class="fas fa-file-excel"></i> Xuất Excel
            </a>
        @endcan

        @can('customer.create')
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
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

@if(session('customer_import_errors'))
    <div class="alert alert-warning alert-dismissible fade show">
        <div class="font-weight-bold mb-2"><i class="fas fa-exclamation-triangle"></i> Chi tiết lỗi import</div>
        <ul class="mb-0 pl-3">
            @foreach(array_slice(session('customer_import_errors'), 0, 20) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        @if(count(session('customer_import_errors')) > 20)
            <div class="small mt-2">Còn {{ count(session('customer_import_errors')) - 20 }} lỗi khác. Vui lòng kiểm tra lại file.</div>
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
            <div class="col-lg-7 col-md-6">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}"
                           placeholder="Mã, tên khách hàng, công trình, email, điện thoại, mã số thuế">
                </div>
            </div>

            <div class="col-lg-3 col-md-4">
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" class="form-control select2">
                        <option value="">Tất cả</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Đang sử dụng</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng sử dụng</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-2">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Tìm</button>
                    <a href="{{ route('customers.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-users"></i> Danh sách khách hàng - công trình</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $customers->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th style="width:120px">Mã KH</th>
                    @unless(auth()->user()->hasRole('TrungTam'))
                        <th style="width:170px">Trung tâm</th>
                    @endunless
                    <th>Khách hàng</th>
                    <th>Công trình</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th style="width:130px">Trạng thái</th>
                    <th style="width:130px" class="text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $customers->firstItem() + $loop->index }}</td>
                        <td><span class="badge badge-primary">{{ $customer->customer_code ?: '-' }}</span></td>
                        @unless(auth()->user()->hasRole('TrungTam'))
                            <td>{{ $customer->distributionCenter ? $customer->distributionCenter->code . ' - ' . $customer->distributionCenter->name : 'Dùng chung' }}</td>
                        @endunless
                        <td>
                            <strong>{{ $customer->customer_name }}</strong>
                            <div class="text-muted small">{{ $customer->customer_address }}</div>
                        </td>
                        <td>
                            {{ $customer->project_name ?: '-' }}
                            <div class="text-muted small">{{ $customer->project_address }}</div>
                        </td>
                        <td>{{ $customer->email ?: '-' }}</td>
                        <td>{{ $customer->phone ?: '-' }}</td>
                        <td>
                            @if($customer->is_active)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đang dùng</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-ban"></i> Ngừng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('customer.update')
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @endcan

                            @can('customer.delete')
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                      class="d-inline" onsubmit="return confirm('Xóa khách hàng này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" title="Xóa"><i class="fas fa-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasRole('TrungTam') ? 8 : 9 }}" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i><br>
                            Chưa có dữ liệu khách hàng - công trình.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} / {{ $customers->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">{{ $customers->links() }}</div>
            </div>
        </div>
    </div>
</div>

@can('customer.import')
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST"
              action="{{ route('customers.import') }}"
              enctype="multipart/form-data"
              class="modal-content"
              data-loading-lock
              data-loading-message="Đang kiểm tra file import khách hàng. Vui lòng chờ...">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-import"></i> Import khách hàng - công trình</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-info">
                    <div class="font-weight-bold mb-1">Quy tắc import</div>
                    <div>Trùng <strong>ma_khach_hang + trung tâm</strong> sẽ được cảnh báo trước. Chỉ khi xác nhận, hệ thống mới cập nhật dữ liệu cũ.</div>
                    <div>Trung tâm phân phối chỉ import vào trung tâm của mình. Admin có thể chọn một trung tâm hoặc nhập cột <code>ma_trung_tam</code> trong file.</div>
                </div>

                @unless(auth()->user()->hasRole('TrungTam'))
                    <div class="form-group">
                        <label>Import vào trung tâm</label>
                        <select name="import_distribution_center_id" class="form-control select2">
                            <option value="">Theo cột ma_trung_tam trong file</option>
                            @foreach($centers as $center)
                                <option value="{{ $center->id }}">{{ $center->code }} - {{ $center->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless

                <div class="form-group">
                    <label>Chọn file Excel</label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>

                <div class="small text-muted">
                    Các cột hỗ trợ: <code>ma_trung_tam</code>, <code>ma_khach_hang</code>, <code>ten_khach_hang</code>,
                    <code>dia_chi_khach_hang</code>, <code>ma_so_thue</code>, <code>nguoi_lien_he</code>,
                    <code>dien_thoai</code>, <code>email</code>, <code>ten_cong_trinh</code>,
                    <code>dia_diem_cong_trinh</code>, <code>dang_su_dung</code>.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-primary"><i class="fas fa-search"></i> Kiểm tra file</button>
            </div>
        </form>
    </div>
</div>

@if(session('customer_import_preview'))
    @php($preview = session('customer_import_preview'))
    <div class="modal fade" id="importPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST"
                  action="{{ route('customers.import') }}"
                  class="modal-content"
                  data-loading-lock
                  data-loading-message="Đang cập nhật dữ liệu khách hàng. Vui lòng chờ...">
                @csrf
                <input type="hidden" name="temp_path" value="{{ $preview['temp_path'] }}">
                <input type="hidden" name="confirm_update" value="1">
                <input type="hidden" name="import_distribution_center_id" value="{{ $preview['import_distribution_center_id'] }}">

                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Xác nhận cập nhật khách hàng trùng mã</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <p>
                        File import có <strong>{{ $preview['update_count'] }}</strong> khách hàng trùng mã trong cùng trung tâm và
                        <strong>{{ $preview['create_count'] }}</strong> khách hàng mới.
                    </p>
                    <p class="mb-2">Nếu tiếp tục, các khách hàng trùng dưới đây sẽ được cập nhật bằng dữ liệu trong file Excel.</p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Dòng</th>
                                    <th>Trung tâm</th>
                                    <th>Mã KH</th>
                                    <th>Tên hiện tại</th>
                                    <th>Tên trong file</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preview['duplicates'] as $duplicate)
                                    <tr>
                                        <td>{{ $duplicate['line'] }}</td>
                                        <td>{{ $duplicate['center'] }}</td>
                                        <td><strong>{{ $duplicate['customer_code'] }}</strong></td>
                                        <td>{{ $duplicate['customer_name'] }}</td>
                                        <td>{{ $duplicate['new_customer_name'] }}</td>
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
                    <a href="{{ route('customers.index') }}" class="btn btn-default">Hủy</a>
                    <button class="btn btn-warning">
                        <i class="fas fa-check"></i> Xác nhận cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
@endcan
@stop

@section('js')
@if(session('customer_import_preview'))
    <script>
        $(function () {
            $('#importPreviewModal').modal('show');
        });
    </script>
@endif
@stop
