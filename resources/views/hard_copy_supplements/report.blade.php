@extends('adminlte::page')
@section('title', 'Báo cáo bổ sung bản ký tươi')
@section('css')<link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20261026') }}"><link rel="stylesheet" href="{{ asset('css/hard-copy-supplements.css?v=20261026-2') }}">@stop
@section('content_header')<h1>Báo cáo bổ sung bản ký tươi</h1>@stop
@section('content')
@include('hard_copy_supplements.messages')
<div class="mb-3"><a href="{{ route('reports.hard-copy') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Nhu cầu ký tươi ban đầu</a>
@can('report.export')<a href="{{ route('reports.hard-copy-supplements.export', request()->query()) }}" class="btn btn-success" data-download><i class="fas fa-file-excel"></i> Xuất Excel</a>@endcan</div>
@include('hard_copy_supplements.filters')
<dl class="row border-top border-bottom py-3">
@foreach(['requests' => 'Lượt yêu cầu bổ sung', 'certificates' => 'Phiếu CNCL không trùng', 'original' => 'Bản ban đầu của các phiếu trong kết quả', 'approved' => 'Bản bổ sung đã duyệt', 'completed' => 'Bản bổ sung đã hoàn tất', 'total' => 'Tổng nhu cầu được duyệt', 'single_sheets' => 'Tờ In đơn bổ sung dự kiến', 'batch_sheets' => 'Tờ In bộ bổ sung dự kiến', 'unassigned' => 'Bản bổ sung chưa chọn mẫu in'] as $key => $label)
<div class="col-md-4 mb-3"><dt>{{ $label }}</dt><dd class="h4">{{ number_format($summary[$key]) }}</dd></div>
@endforeach</dl>
<details class="mb-3"><summary>Định nghĩa số liệu</summary><p class="mt-2">Bổ sung đã duyệt gồm Chờ PTN và Đã hoàn tất, chỉ cộng phiếu đang phát hành hợp lệ. Bản hoàn tất nằm trong bản đã duyệt, không cộng lần nữa. Bản ban đầu chỉ tính một lần cho mỗi phiếu xuất hiện trong kết quả, không phải số bản phát sinh trong kỳ. Tổng nhu cầu = bản ban đầu + bổ sung đã duyệt trong kết quả. Phiếu ban đầu không yêu cầu ký tươi vẫn xuất hiện nếu có yêu cầu bổ sung.</p><p>Số tờ bổ sung dự kiến = số bản × số trang của mẫu mở gần nhất, tách In đơn/In bộ, không cộng hai phương án cho cùng yêu cầu. Bộ lọc mẫu in cũng căn cứ mẫu mở gần nhất. Có thể mở cả hai mẫu; từng lượt được lưu trong lịch sử. Bản chưa mở file chưa tính số tờ. Lượt mở file không phải số bản thực in; hoàn tất không đồng nghĩa đã bàn giao.</p></details>
<h5>Thống kê theo Trung tâm</h5><div class="table-responsive mb-3"><table class="table table-bordered"><thead><tr><th>Trung tâm</th><th>Lượt yêu cầu</th><th>Phiếu CNCL</th><th>Bản ban đầu</th><th>Bổ sung đã duyệt</th><th>Bổ sung hoàn tất</th><th>Tổng được duyệt</th><th>Tờ In đơn dự kiến</th><th>Tờ In bộ dự kiến</th></tr></thead><tbody>
@forelse($groups as $row)<tr><td>{{ $row['center'] }}</td>@foreach(['requests', 'certificates', 'original', 'approved', 'completed', 'total', 'single_sheets', 'batch_sheets'] as $key)<td>{{ number_format($row[$key]) }}</td>@endforeach</tr>@empty<tr><td colspan="9">Không có dữ liệu phù hợp.</td></tr>@endforelse
</tbody></table></div>
<h5>Thống kê theo tháng của loại ngày đã lọc</h5>
<div class="table-responsive mb-3"><table class="table table-bordered"><thead><tr><th>Tháng</th><th>Lượt yêu cầu</th><th>Phiếu CNCL trong tháng</th><th>Bổ sung đã duyệt</th><th>Bổ sung hoàn tất</th><th>Tờ In đơn dự kiến</th><th>Tờ In bộ dự kiến</th></tr></thead><tbody>
@forelse($monthly as $row)<tr><td>{{ $row['month'] }}</td>@foreach(['requests', 'certificates', 'approved', 'completed', 'single_sheets', 'batch_sheets'] as $key)<td>{{ number_format($row[$key]) }}</td>@endforeach</tr>@empty<tr><td colspan="7">Không có dữ liệu phù hợp.</td></tr>@endforelse
</tbody></table></div>
<h5>Chi tiết các lần bổ sung</h5>@include('hard_copy_supplements.table')
@stop
