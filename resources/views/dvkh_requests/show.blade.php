@extends('adminlte::page')

@section('title', 'DVKH kiểm tra yêu cầu')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0">DVKH kiểm tra yêu cầu</h1>
        <small class="text-muted">{{ $certificateRequest->request_no }}</small>
    </div>

    <a href="{{ route('dvkh.requests.index') }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

@if($invoiceDuplicates->isNotEmpty())
    <div class="alert alert-warning">
        <div class="font-weight-bold mb-2">
            <i class="fas fa-exclamation-triangle"></i>
            Cảnh báo: Số hóa đơn {{ $certificateRequest->invoice_no }} đã tồn tại trên hệ thống.
        </div>
        <div class="mb-2">
            DVKH vui lòng kiểm tra trước khi xác nhận yêu cầu.
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white mb-0">
                <thead>
                    <tr>
                        <th>Số yêu cầu</th>
                        <th>Khách hàng / Công trình</th>
                        <th>Trung tâm</th>
                        <th>Trạng thái</th>
                        <th>Phiếu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoiceDuplicates as $duplicate)
                        <tr>
                            <td>
                                <a href="{{ route('certificate-requests.show', $duplicate) }}" target="_blank">
                                    {{ $duplicate->request_no }}
                                </a>
                                <div class="text-muted small">{{ optional($duplicate->created_at)->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>
                                <strong>{{ $duplicate->customer->customer_name ?? '-' }}</strong>
                                <div class="text-muted small">{{ $duplicate->customer->project_name ?? '' }}</div>
                            </td>
                            <td>{{ $duplicate->distributionCenter->name ?? '-' }}</td>
                            <td>@include('certificate_requests.partials.status_badge', ['certificateRequest' => $duplicate])</td>
                            <td>{{ $duplicate->qualityCertificate->certificate_no ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Thông tin yêu cầu</h3>
            </div>

            <div class="card-body">
                <p><strong>Số yêu cầu:</strong> {{ $certificateRequest->request_no }}</p>
                <p>
                    <strong>Loại yêu cầu:</strong>
                    @if($certificateRequest->request_type === 'REISSUE')
                        <span class="badge badge-danger"><i class="fas fa-redo"></i> Cấp lại</span>
                    @else
                        <span class="badge badge-light">Cấp mới</span>
                    @endif
                </p>
                @if($certificateRequest->request_type === 'REISSUE')
                    <p><strong>Phiếu cũ:</strong> {{ $certificateRequest->reissueOfCertificate->certificate_no ?? '-' }}</p>
                    <p><strong>Lý do cấp lại:</strong> {{ $certificateRequest->reissue_reason ?: '-' }}</p>
                @endif
                <p>
                    <strong>Trạng thái:</strong>
                    @include('certificate_requests.partials.status_badge', ['certificateRequest' => $certificateRequest])
                </p>
                <p><strong>Trung tâm:</strong> {{ $certificateRequest->distributionCenter->name ?? '-' }}</p>
                <p><strong>Ngày xuất hàng:</strong> {{ $certificateRequest->delivery_date ? $certificateRequest->delivery_date->format('d/m/Y') : '-' }}</p>
                <p><strong>Số hóa đơn:</strong> {{ $certificateRequest->invoice_no ?: '-' }}</p>
                <p>
                    <strong>Ký tươi:</strong>
                    @if($certificateRequest->require_hard_copy)
                        Có - {{ $certificateRequest->hard_copy_quantity }} bản
                    @else
                        Không
                    @endif
                </p>
                <p>
                    <strong>Yêu cầu gấp:</strong>
                    @if($certificateRequest->is_urgent)
                        <span class="badge badge-danger"><i class="fas fa-bolt"></i> Có</span>
                        <br>
                        <span class="text-danger small">{{ $certificateRequest->urgentReason->name ?? 'Chưa chọn lý do' }}</span>
                    @else
                        Không
                    @endif
                </p>
                <p><strong>Tên người tạo yêu cầu:</strong> {{ $certificateRequest->requester_name ?: '-' }}</p>
                <p><strong>Người tạo:</strong> {{ $certificateRequest->creator->name ?? '-' }}</p>
                <p><strong>Ngày tạo:</strong> {{ optional($certificateRequest->created_at)->format('d/m/Y H:i') }}</p>
            </div>

            @can('dvkh.process')
                @if($certificateRequest->status === 'WAIT_DVKH')
                    <div class="card-footer">
                        <form action="{{ route('dvkh.requests.approve', $certificateRequest) }}" method="POST"
                              class="d-inline" onsubmit="return confirm('Xác nhận yêu cầu này và chuyển sang PTN?')">
                            @csrf
                            <button class="btn btn-success">
                                <i class="fas fa-check"></i> Xác nhận chuyển PTN
                            </button>
                        </form>

                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal">
                            <i class="fas fa-times"></i> Trả lại
                        </button>
                    </div>
                @endif
            @endcan
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">Khách hàng / Công trình</h3>
            </div>

            <div class="card-body">
                <p><strong>Khách hàng:</strong> {{ $certificateRequest->customer->customer_name ?? '-' }}</p>
                <p><strong>Địa chỉ KH:</strong> {{ $certificateRequest->customer->customer_address ?? '-' }}</p>
                <p><strong>Email:</strong> {{ $certificateRequest->customer->email ?? '-' }}</p>
                <p><strong>Công trình:</strong> {{ $certificateRequest->customer->project_name ?? '-' }}</p>
                <p><strong>Địa điểm công trình:</strong> {{ $certificateRequest->customer->project_address ?? '-' }}</p>
                <p><strong>Ghi chú:</strong> {{ $certificateRequest->note ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-box"></i> Danh sách sản phẩm</h3>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Kích thước</th>
                    <th>Yêu cầu kỹ thuật</th>
                    <th>Tiêu chuẩn</th>
                    <th style="width:120px">Số lượng</th>
                </tr>
            </thead>

            <tbody>
                @foreach($certificateRequest->details as $detail)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $detail->product->product_code ?? '-' }}</td>
                        <td>{{ $detail->product->product_name ?? '-' }}</td>
                        <td>{{ $detail->product->nominal_size ?? '-' }}</td>
                        <td>{{ $detail->product->technical_requirements ?? '-' }}</td>
                        <td>{{ $detail->product->qualityStandard->code ?? '-' }}</td>
                        <td>{{ $detail->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@can('dvkh.process')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('dvkh.requests.reject', $certificateRequest) }}" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-times-circle"></i> Trả lại yêu cầu</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <p>Yêu cầu: <strong>{{ $certificateRequest->request_no }}</strong></p>

                <div class="form-group">
                    <label>Lý do trả lại <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-danger"><i class="fas fa-times"></i> Trả lại</button>
            </div>
        </form>
    </div>
</div>
@endcan
@stop
