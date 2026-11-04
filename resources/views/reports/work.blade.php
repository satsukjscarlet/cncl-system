@extends('adminlte::page')

@section('title', 'Báo cáo công việc')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css') }}">
    <style>
        .work-report { min-width: 0; }
        .work-report .card-title { float: none; font-size: 17px; }
        .work-filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; align-items: end; }
        .work-filter-grid > div { min-width: 0; }
        .work-filter-grid .select2-container { max-width: 100%; }
        .work-filter-actions { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: 8px; }
        .work-matrix { overflow: auto; max-height: 600px; position: relative; }
        .work-matrix table { min-width: 1480px; margin: 0; font-size: 13px; border-collapse: separate; border-spacing: 0; }
        .work-matrix th, .work-matrix td { min-width: 38px; text-align: center; vertical-align: middle; padding: 9px 5px; }
        .work-matrix thead th { position: sticky; top: 0; z-index: 2; background: #edf3f8; }
        .work-matrix .work-label { position: sticky; left: 0; z-index: 1; min-width: 190px; max-width: 240px; text-align: left; background: #fff; overflow-wrap: anywhere; }
        .work-matrix thead .work-label { z-index: 3; background: #edf3f8; }
        .work-matrix .is-sunday { background: #fff3cd; color: #854d0e; }
        .work-matrix .work-total { background: #e9f3ed; font-weight: 700; }
        .work-matrix tfoot th { background: #e9f3ed; }
        .work-matrix a { display: block; font-weight: 700; text-decoration: underline; }
        .work-weekday { display: block; font-size: 11px; font-weight: 400; margin-top: 3px; }
        .work-definition { border-left: 3px solid #17a2b8; padding: 12px 16px; background: #f0f8fa; }
        .work-definition summary { cursor: pointer; font-weight: 700; }
        .work-definition p { margin: 8px 0 0; }
        .work-detail-table { min-width: 1000px; }
        @media(max-width: 575.98px) { .work-filter-grid { grid-template-columns: 1fr; } .work-report-heading { gap: 12px; } }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center work-report-heading">
    <div>
        <h1 class="m-0">Báo cáo công việc</h1>
        <small class="text-muted">Tháng {{ $start->format('m/Y') }} · {{ $filters['group_by'] === 'center' ? 'Theo Trung tâm' : 'Theo tài khoản' }}</small>
        <div class="small font-weight-bold mt-1">{{ $scope['label'] }}</div>
    </div>
    @can('report.export')
        <a href="{{ route('reports.work.export', request()->except(['page', 'detail_day', 'detail_row', 'detail_kind'])) }}" class="btn btn-success">
            <i class="fas fa-file-excel mr-1"></i> Xuất Excel
        </a>
    @endcan
</div>
@stop

@section('content')
<div class="work-report">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div class="card card-primary card-outline">
        <div class="card-header bg-white"><h2 class="card-title"><i class="fas fa-filter mr-1"></i> Bộ lọc báo cáo</h2></div>
        <form method="GET" action="{{ route('reports.work') }}" class="card-body work-filter-grid">
            <div>
                <label for="work-month">Tháng</label>
                <select id="work-month" name="month" class="form-control">
                    @foreach(range(1, 12) as $month)
                        <option value="{{ $month }}" @selected((int) $filters['month'] === $month)>Tháng {{ $month }}</option>
                    @endforeach
                </select>
            </div>
            <div><label for="work-year">Năm</label><input id="work-year" type="number" name="year" min="2000" max="2100" value="{{ $filters['year'] }}" class="form-control" required></div>
            <div>
                <label for="work-department">Phòng ban phụ trách</label>
                @if(count($scope['departments']) === 1)
                    <input id="work-department" class="form-control" value="{{ \App\Services\WorkReportService::DEPARTMENTS[$scope['departments'][0]] }}" readonly>
                @else
                <select id="work-department" name="department" class="form-control select2">
                    <option value="">Tất cả phòng được xem</option>
                    @foreach(array_intersect_key(\App\Services\WorkReportService::DEPARTMENTS, array_flip($scope['departments'])) as $value => $label)
                        <option value="{{ $value }}" @selected($filters['department'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @endif
            </div>
            @if($scope['mode'] !== 'self')
            <div>
                <label for="work-user">Tài khoản thực hiện</label>
                <select id="work-user" name="user_id" class="form-control select2">
                    <option value="">Tất cả tài khoản và hệ thống</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected((int) $filters['user_id'] === $user->id)>{{ $user->name }} ({{ $user->username }}){{ $user->is_active ? '' : ' · Đã khóa' }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label for="work-kind">Nhóm công việc</label>
                <select id="work-kind" name="kind" class="form-control select2">
                    <option value="">Tất cả nhóm công việc</option>
                    @foreach($kindOptions as $value => $meta)
                        <option value="{{ $value }}" @selected($filters['kind'] === $value)>{{ $meta[0] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="work-center">Trung tâm của hồ sơ</label>
                <select id="work-center" name="distribution_center_id" class="form-control select2">
                    <option value="">Tất cả Trung tâm</option>
                    @foreach($centers as $center)
                        <option value="{{ $center->id }}" @selected((int) $filters['distribution_center_id'] === $center->id)>{{ $center->code }} - {{ $center->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="work-group">Hiển thị từng dòng</label>
                <select id="work-group" name="group_by" class="form-control">
                    <option value="center" @selected($filters['group_by'] === 'center')>Theo Trung tâm</option>
                    <option value="account" @selected($filters['group_by'] === 'account')>Theo tài khoản</option>
                </select>
            </div>
            <div class="work-filter-actions">
                <button class="btn btn-primary"><i class="fas fa-search mr-1"></i> Lọc báo cáo</button>
                <a href="{{ route('reports.work') }}" class="btn btn-outline-secondary" title="Xóa bộ lọc" aria-label="Xóa bộ lọc"><i class="fas fa-sync-alt"></i></a>
            </div>
        </form>
    </div>

    <details open class="work-definition mb-3">
        <summary>Định nghĩa số liệu</summary>
        <p>Mỗi ô là <strong>lượt hồ sơ theo loại công việc, tài khoản và ngày</strong>. Cùng hồ sơ, cùng loại công việc và tài khoản trong một ngày chỉ tính một lần. Khác ngày hoặc khác tài khoản được tính lượt riêng; tổng tháng không phải số phiếu duy nhất. Không cộng chung các nhóm công việc thành tổng số phiếu.</p>
        <p>Phòng ban là đơn vị phụ trách bước nghiệp vụ, không suy từ vai trò hiện tại. Không tính lưu nháp, xem PDF, mở file in hoặc kiểm tra kết quả ký là hoàn thành công việc. Chủ nhật có phát sinh vẫn được tính.</p>
        @if($scope['mode'] !== 'self')
            <p><strong>Ký số thành công</strong> là kết quả hệ thống, không quy cho người bấm kiểm tra; khi lọc một tài khoản, những kết quả chưa xác định người ký sẽ không xuất hiện. Dữ liệu cũ thiếu người xử lý hiển thị <strong>Chưa xác định / hệ thống</strong>.</p>
        @else
            <p>Chỉ tính công việc có tài khoản thực hiện là bạn trong phòng trực thuộc. Không tính kết quả ký số tự động hoặc dữ liệu cũ chưa xác định người thực hiện vào báo cáo cá nhân.</p>
        @endif
        <p>Công việc đã thực hiện vẫn giữ trong báo cáo khi hồ sơ về sau bị hủy, trả lại hoặc thay thế. Báo cáo không thay cho thống kê phiếu còn hiệu lực. Excel xuất toàn tháng theo bộ lọc chính, không giới hạn ô chi tiết đang chọn.</p>
    </details>
    @if($filters['department'] && $filters['kind'] && \App\Services\WorkReportService::KINDS[$filters['kind']][1] !== $filters['department'])
        <div class="alert alert-warning">Nhóm công việc không thuộc phòng ban đang chọn. Vui lòng chọn lại bộ lọc.</div>
    @endif
    @if($events->whereNull('actor_id')->count())
        <div class="text-muted small mb-3"><i class="fas fa-info-circle"></i> Có {{ number_format($events->whereNull('actor_id')->count()) }} lượt chưa xác định tài khoản hoặc là kết quả hệ thống; đã giữ riêng, không gán cho người tạo hồ sơ.</div>
    @endif

    @foreach($sections as $section)
        <section class="card work-section" data-kind="{{ $section['kind'] }}">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center">
                <h2 class="card-title mb-0">{{ $section['label'] }}</h2>
                <span class="badge badge-info">{{ number_format($section['total']) }} lượt</span>
            </div>
            <div class="work-matrix">
                <table class="table table-bordered table-hover">
                    <thead><tr>
                        <th class="work-label" scope="col">{{ $filters['group_by'] === 'center' ? 'Trung tâm' : 'Tài khoản' }}</th>
                        @foreach(range(1, $start->daysInMonth) as $day)
                            @php($date = $start->copy()->day($day))
                            <th scope="col" class="{{ $date->isSunday() ? 'is-sunday' : '' }}">{{ sprintf('%02d', $day) }}<span class="work-weekday">{{ $date->isSunday() ? 'CN' : 'T'.($date->dayOfWeek + 1) }}</span></th>
                        @endforeach
                        <th class="work-total" scope="col">Tổng</th>
                    </tr></thead>
                    <tbody>
                        @forelse($section['rows'] as $row)
                            <tr>
                                <th scope="row" class="work-label">{{ $row['label'] }}</th>
                                @foreach($row['days'] as $day => $count)
                                    <td class="{{ $start->copy()->day($day)->isSunday() ? 'is-sunday' : '' }}">
                                        @if($count)
                                            <a title="Xem {{ $count }} lượt ngày {{ $day }}" href="{{ route('reports.work', array_merge($filters, ['detail_day' => $day, 'detail_row' => $row['key'], 'detail_kind' => $section['kind']])) }}#work-details">{{ $count }}</a>
                                        @else <span class="text-muted">0</span> @endif
                                    </td>
                                @endforeach
                                <td class="work-total"><a href="{{ route('reports.work', array_merge(collect($filters)->except(['detail_day', 'detail_row', 'detail_kind'])->all(), ['detail_row' => $row['key'], 'detail_kind' => $section['kind']])) }}#work-details">{{ $row['total'] }}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $start->daysInMonth + 2 }}" class="text-muted py-4">Không có công việc khớp bộ lọc trong tháng này.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr><th class="work-label" scope="row">Tổng {{ $section['label'] }}</th>
                        @foreach($section['days'] as $count)<th>{{ number_format($count) }}</th>@endforeach
                        <th>{{ number_format($section['total']) }}</th>
                    </tr></tfoot>
                </table>
            </div>
        </section>
    @endforeach

    <section class="card" id="work-details">
        <div class="card-header bg-white">
            <h2 class="card-title mb-0">Chi tiết công việc <span class="badge badge-info">{{ number_format($details->total()) }} lượt</span></h2>
            @if(!empty($filters['detail_day']) || !empty($filters['detail_row']) || !empty($filters['detail_kind']))
                <div class="mt-2 small">
                    {{ !empty($filters['detail_day']) ? 'Ngày '.$filters['detail_day'].' · ' : '' }}{{ \App\Services\WorkReportService::KINDS[$filters['detail_kind'] ?? ''][0] ?? '' }}
                    <a class="ml-2" href="{{ route('reports.work', collect($filters)->except(['detail_day', 'detail_row', 'detail_kind'])->all()) }}#work-details">Bỏ lọc ô chi tiết</a>
                </div>
            @endif
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0 work-detail-table">
                <thead class="thead-light"><tr><th>Thời điểm</th><th>Công việc</th><th>Người thực hiện</th><th>Trung tâm</th><th>Hồ sơ</th><th>Số yêu cầu</th><th>Nguồn dữ liệu</th></tr></thead>
                <tbody>
                    @forelse($details as $event)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($event['occurred_at'])->format('d/m/Y H:i') }}</td><td>{{ \App\Services\WorkReportService::KINDS[$event['kind']][0] }}</td><td>{{ $event['actor_label'] }}</td><td>{{ $event['center_label'] }}</td><td>{{ $event['object_no'] }}</td><td>{{ $event['request_no'] ?? '-' }}</td><td>{{ $event['source'] }}</td></tr>
                    @empty <tr><td colspan="7" class="text-center text-muted py-4">Không có công việc khớp bộ lọc.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center">
            <small class="text-muted">{{ $details->firstItem() ?? 0 }}–{{ $details->lastItem() ?? 0 }} / {{ number_format($details->total()) }} lượt</small>
            {{ $details->fragment('work-details')->links() }}
        </div>
    </section>
</div>
@stop
