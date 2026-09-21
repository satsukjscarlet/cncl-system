@extends('adminlte::page')

@section('title', 'PTN lập phiếu CNCL')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0">PTN lập phiếu CNCL</h1>
        <small class="text-muted">{{ $certificateRequest->request_no }}</small>
    </div>

    <a href="{{ route('ptn.requests.index') }}" class="btn btn-default">
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

@include('quality_certificates.partials.workflow_steps', ['steps' => $requestWorkflowSteps])

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
                    @if($certificateRequest->reissueCertificates->count() > 1)
                        <p>
                            <strong>Các phiếu cũ được gom:</strong><br>
                            @foreach($certificateRequest->reissueCertificates as $oldCertificate)
                                <a href="{{ route('quality-certificates.show', $oldCertificate) }}" target="_blank" class="badge badge-light">
                                    {{ $oldCertificate->certificate_no }}
                                </a>
                            @endforeach
                        </p>
                    @endif
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
                <p>
                    <strong>Cam kết nhận/lấy hàng:</strong>
                    @if($certificateRequest->customer_commitment_confirmed)
                        <span class="badge badge-success"><i class="fas fa-check"></i> Đã xác nhận</span>
                    @else
                        <span class="badge badge-secondary">Chưa xác nhận</span>
                    @endif
                </p>
                <p><strong>Người tạo:</strong> {{ $certificateRequest->creator->name ?? '-' }}</p>
                <p><strong>Ngày tạo:</strong> {{ optional($certificateRequest->created_at)->format('d/m/Y H:i') }}</p>
            </div>

            @can('ptn.process')
                <div class="card-footer">
                    @if($certificateRequest->qualityCertificate)
                        <a href="{{ route('quality-certificates.show', $certificateRequest->qualityCertificate) }}" class="btn btn-success">
                            <i class="fas fa-file-signature"></i> Xem phiếu đã lập
                        </a>
                    @elseif(in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING']))
                        <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                            <form action="{{ route('ptn.requests.receive-and-create-certificate', $certificateRequest) }}" method="POST"
                                  class="m-0"
                                  data-loading-lock
                                  data-loading-message="Đang lập phiếu CNCL từ yêu cầu, vui lòng chờ..."
                                  onsubmit="if (!confirm('Lập phiếu CNCL từ yêu cầu này?')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                @csrf
                                <button class="btn btn-primary">
                                    <i class="fas fa-file-signature"></i> Lập phiếu CNCL
                                </button>
                            </form>

                            @if($canReturnToDvkh)
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#returnToDvkhModal">
                                    <i class="fas fa-undo"></i> Trả lại DVKH
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
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

@include('quality_certificates.partials.history_timeline', [
    'logs' => $requestHistoryLogs,
    'title' => 'Lịch sử xử lý yêu cầu',
])

@can('ptn.process')
    @if($canReturnToDvkh)
        <div class="modal fade" id="returnToDvkhModal" tabindex="-1" role="dialog" aria-labelledby="returnToDvkhModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <form method="POST" action="{{ route('ptn.requests.return-to-dvkh', $certificateRequest) }}" class="modal-content cncl-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="returnToDvkhModalLabel">
                            <i class="fas fa-undo"></i> Trả lại DVKH
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted">
                            Yêu cầu sẽ quay về màn DVKH kiểm tra. Vui lòng nhập rõ nội dung cần DVKH xử lý lại.
                        </p>
                        <div class="form-group">
                            <label>Lý do trả lại <span class="text-danger">*</span></label>
                            <textarea name="reason"
                                      class="form-control @error('reason') is-invalid @enderror"
                                      rows="4"
                                      required
                                      placeholder="Ví dụ: Thiếu thông tin công trình, cần xác nhận lại tiêu chuẩn sản phẩm...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            Hủy
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-paper-plane"></i> Trả lại DVKH
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endcan
@stop

@section('js')
@if($errors->has('reason'))
    <script>
        $(function () {
            $('#returnToDvkhModal').modal('show');
        });
    </script>
@endif
@stop
