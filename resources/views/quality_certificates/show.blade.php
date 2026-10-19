@extends('adminlte::page')

@section('title', 'Chi tiết Phiếu CNCL')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Chi tiết Phiếu CNCL</h1>
        <small class="text-muted">{{ $qualityCertificate->certificate_no }}</small>
    </div>


</div>
@stop

@section('content')
@if (session('success'))
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

@include('quality_certificates.partials.workflow_steps', ['steps' => $certificateWorkflowSteps])

@php
    $smartCaPendingTtlMinutes = max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
    $smartCaRequestedAt = $qualityCertificate->smartca_requested_at ?: $qualityCertificate->updated_at;
    $smartCaExpiresAt = $smartCaRequestedAt ? $smartCaRequestedAt->copy()->addMinutes($smartCaPendingTtlMinutes) : null;
    $smartCaPendingExpired = $qualityCertificate->smartca_status === 'PENDING'
        && $smartCaExpiresAt
        && $smartCaExpiresAt->lte(now());
    $smartCaCanResend = $qualityCertificate->smartca_status === 'EXPIRED' || $smartCaPendingExpired;
    $statusMeta = $qualityCertificate->displayStatusMeta();
    $canApproveForSigning = $qualityCertificate->canApproveForSigningQueue();
    $canSendSignature = $qualityCertificate->canSendSignatureRequest();
    $canEmergencyPrintHardCopy = $qualityCertificate->canEmergencyPrintHardCopy();
    $emergencyPrintLogs = $qualityCertificate->printLogs->where('print_mode', 'emergency');
@endphp

@if($emergencyPrintLogs->isNotEmpty())
    @php
        $latestEmergencyPrintLog = $emergencyPrintLogs->sortByDesc('created_at')->first();
    @endphp
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        Phiếu này đã từng được in ký tươi khẩn cấp
        lúc {{ optional($latestEmergencyPrintLog->created_at)->format('d/m/Y H:i') }}
        bởi {{ $latestEmergencyPrintLog->user->name ?? '-' }}.
        Lý do: {{ $latestEmergencyPrintLog->reason }}
    </div>
@endif

@if($qualityCertificate->status === 'REJECTED')
    <div class="alert alert-warning">
        <strong><i class="fas fa-undo"></i> Phiếu đã được trả lại</strong>
        <div>{{ $qualityCertificate->rejected_reason ?: '-' }}</div>
        <small>{{ optional($qualityCertificate->rejected_at)->format('d/m/Y H:i') }} {{ $qualityCertificate->rejectedBy->name ?? '' }}</small>
    </div>
@elseif($qualityCertificate->status === 'REVOKED')
    <div class="alert alert-danger">
        <strong><i class="fas fa-ban"></i> Phiếu đã thu hồi, không còn hiệu lực</strong>
        <div>{{ $qualityCertificate->revoked_reason ?: '-' }}</div>
    </div>
@elseif(!$qualityCertificate->signed_at && ($smartCaCanResend || $qualityCertificate->smartca_status === 'PENDING'))
    <div class="alert {{ $smartCaCanResend ? 'alert-warning' : 'alert-info' }}">
        <strong>{{ $smartCaCanResend ? 'Yêu cầu ký đã quá hạn xác nhận' : 'Đang chờ xác nhận trên VNPT SmartCA' }}</strong>
        <div>Gửi ký: {{ optional($qualityCertificate->smartca_requested_at)->format('d/m/Y H:i') ?: '-' }}; Hạn xác nhận: {{ optional($smartCaExpiresAt)->format('d/m/Y H:i') ?: '-' }}</div>
    </div>
@endif
<div class="workflow-detail-summary">
    <span><strong>Trung tâm:</strong> {{ $qualityCertificate->request->distributionCenter->name ?? '-' }}</span>
    <span><strong>Người tạo yêu cầu:</strong> {{ $qualityCertificate->request->requester_name ?? '-' }}</span>
    <span><strong>Ký tươi:</strong> {{ $qualityCertificate->request?->require_hard_copy ? $qualityCertificate->request->hard_copy_quantity . ' bản' : 'Không' }}</span>
    <span><strong>Sản phẩm:</strong> {{ $qualityCertificate->details->count() }} dòng</span>
</div>
<div class="row">
    <div class="col-lg-6 workflow-info">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Thông tin phiếu</h3>
            </div>

            <div class="card-body">
                <p><strong>Số phiếu:</strong> {{ $qualityCertificate->certificate_no }}</p>
                <p>
                    <strong>Số yêu cầu:</strong>
                    @if($qualityCertificate->request)
                        <a href="{{ route('certificate-requests.show', $qualityCertificate->request) }}">
                            {{ $qualityCertificate->request->request_no }}
                        </a>
                    @else
                        -
                    @endif
                </p>
                <p>
                    <strong>Yêu cầu ký tươi:</strong>
                    @if($qualityCertificate->request?->require_hard_copy)
                        <span class="badge badge-warning">
                            <i class="fas fa-print"></i> Có - {{ $qualityCertificate->request->hard_copy_quantity }} bản
                        </span>
                    @else
                        <span class="text-muted">Không</span>
                    @endif
                </p>
                <p><strong>Người lập:</strong> {{ $qualityCertificate->creator->name ?? '-' }}</p>
                <p><strong>Đơn vị bán hàng:</strong> {{ $qualityCertificate->request?->salesUnit ? $qualityCertificate->request->salesUnit->code . ' - ' . $qualityCertificate->request->salesUnit->name : '-' }}</p>
                <p><strong>Ngày lập:</strong> {{ optional($qualityCertificate->created_at)->format('d/m/Y H:i') }}</p>
                <p>
                    <strong>Trạng thái:</strong>
                    <span class="badge {{ $statusMeta['class'] }}">
                        <i class="{{ $statusMeta['icon'] }}"></i> {{ $statusMeta['text'] }}
                    </span>
                </p>
                @if($qualityCertificate->replacesCertificate)
                    <p>
                        <strong>Cấp lại cho phiếu:</strong>
                        <a href="{{ route('quality-certificates.show', $qualityCertificate->replacesCertificate) }}">
                            {{ $qualityCertificate->replacesCertificate->certificate_no }}
                        </a>
                    </p>
                @endif
                @if(($qualityCertificate->request?->reissueCertificates?->count() ?? 0) > 1)
                    <p>
                        <strong>Cấp lại cho các phiếu:</strong><br>
                        @foreach($qualityCertificate->request->reissueCertificates as $oldCertificate)
                            <a href="{{ route('quality-certificates.show', $oldCertificate) }}" class="badge badge-light">
                                {{ $oldCertificate->certificate_no }}
                            </a>
                        @endforeach
                    </p>
                @endif
                @if($qualityCertificate->replacedByCertificate)
                    <p>
                        <strong>Được thay thế bởi:</strong>
                        <a href="{{ route('quality-certificates.show', $qualityCertificate->replacedByCertificate) }}">
                            {{ $qualityCertificate->replacedByCertificate->certificate_no }}
                        </a>
                    </p>
                @endif
                @if($qualityCertificate->status === 'REVOKED')
                    <p><strong>Người hủy:</strong> {{ $qualityCertificate->revokedBy->name ?? '-' }}</p>
                    <p><strong>Ngày hủy:</strong> {{ $qualityCertificate->revoked_at ? $qualityCertificate->revoked_at->format('d/m/Y H:i') : '-' }}</p>
                    <p><strong>Lý do hủy:</strong> {{ $qualityCertificate->revoked_reason ?: '-' }}</p>
                @endif
                @if($qualityCertificate->status === 'REJECTED')
                    <p><strong>Người trả lại:</strong> {{ $qualityCertificate->rejectedBy->name ?? '-' }}</p>
                    <p><strong>Ngày trả lại:</strong> {{ $qualityCertificate->rejected_at ? $qualityCertificate->rejected_at->format('d/m/Y H:i') : '-' }}</p>
                    <p><strong>Trả về bước:</strong> {{ $qualityCertificate->rejected_to === 'DVKH' ? 'DVKH xác nhận lại' : 'PTN xử lý lại' }}</p>
                    <p><strong>Lý do trả lại:</strong> {{ $qualityCertificate->rejected_reason ?: '-' }}</p>
                @endif
                <p><strong>Người ký:</strong> {{ $qualityCertificate->signed_by ?: '-' }}</p>
                <p><strong>Ngày ký:</strong> {{ $qualityCertificate->signed_at ? $qualityCertificate->signed_at->format('d/m/Y H:i') : '-' }}</p>
                <p>
                    <strong>SmartCA:</strong>
                    @if ($smartCaCanResend)
                        <span class="badge badge-danger">Đã hết hạn</span>
                    @elseif ($qualityCertificate->smartca_status === 'PENDING')
                        <span class="badge badge-primary">Đang chờ xác nhận</span>
                    @elseif ($qualityCertificate->smartca_status === 'SIGNED')
                        <span class="badge badge-success">Đã ký SmartCA</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </p>
                @if ($qualityCertificate->smartca_status === 'PENDING' || $qualityCertificate->smartca_status === 'EXPIRED')
                    <p><strong>Gửi ký lúc:</strong> {{ $qualityCertificate->smartca_requested_at ? $qualityCertificate->smartca_requested_at->format('d/m/Y H:i') : '-' }}</p>
                    <p><strong>Hạn xác nhận:</strong> {{ $smartCaExpiresAt ? $smartCaExpiresAt->format('d/m/Y H:i') : '-' }}</p>
                @endif
                @role('Admin')
                    @if ($qualityCertificate->smartca_transaction_id)
                        <details class="mb-3"><summary>Thông tin kỹ thuật SmartCA</summary>
                            <p class="mt-2"><strong>Mã giao dịch:</strong> <code>{{ $qualityCertificate->smartca_transaction_id }}</code></p>
                        </details>
                    @endif
                @endrole
                <p><strong>Số lần in ký tươi:</strong> {{ $qualityCertificate->print_count }}</p>
            </div>


        </div>

        @if($qualityCertificate->replacesCertificate || $qualityCertificate->replacedByCertificate || $qualityCertificate->status === 'REVOKED')
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exchange-alt"></i> Lịch sử cấp lại / thu hồi</h3>
                </div>

                <div class="card-body">
                    @if($qualityCertificate->replacesCertificate)
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase font-weight-bold">Phiếu hiện tại cấp lại cho</div>
                            <a class="font-weight-bold" href="{{ route('quality-certificates.show', $qualityCertificate->replacesCertificate) }}">
                                {{ $qualityCertificate->replacesCertificate->certificate_no }}
                            </a>
                            <div class="mt-1">
                                @if($qualityCertificate->replacesCertificate->status === 'REVOKED')
                                    <span class="badge badge-danger"><i class="fas fa-ban"></i> Phiếu cũ đã hủy / thu hồi</span>
                                @else
                                    <span class="badge badge-secondary">{{ $qualityCertificate->replacesCertificate->status }}</span>
                                @endif
                            </div>
                            @if($qualityCertificate->replacesCertificate->revoked_reason)
                                <div class="small mt-2">
                                    <strong>Lý do hủy:</strong> {{ $qualityCertificate->replacesCertificate->revoked_reason }}
                                </div>
                            @endif
                        </div>
                    @endif

                    @if(($qualityCertificate->request?->reissueCertificates?->count() ?? 0) > 1)
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase font-weight-bold">Phiếu hiện tại gom cấp lại cho</div>
                            @foreach($qualityCertificate->request->reissueCertificates as $oldCertificate)
                                <div class="mb-1">
                                    <a class="font-weight-bold" href="{{ route('quality-certificates.show', $oldCertificate) }}">
                                        {{ $oldCertificate->certificate_no }}
                                    </a>
                                    @if($oldCertificate->status === 'REVOKED')
                                        <span class="badge badge-danger"><i class="fas fa-ban"></i> Đã hủy / thu hồi</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $oldCertificate->status }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($qualityCertificate->replacedByCertificate)
                        <div class="mb-3">
                            <div class="text-muted small text-uppercase font-weight-bold">Phiếu thay thế</div>
                            <a class="font-weight-bold" href="{{ route('quality-certificates.show', $qualityCertificate->replacedByCertificate) }}">
                                {{ $qualityCertificate->replacedByCertificate->certificate_no }}
                            </a>
                            <div class="mt-1">
                                <span class="badge badge-info"><i class="fas fa-redo"></i> Phiếu cấp lại</span>
                                @if($qualityCertificate->replacedByCertificate->signed_at)
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Đã ký / phát hành</span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($qualityCertificate->status === 'REVOKED')
                        <div class="border-top pt-3">
                            <div class="text-muted small text-uppercase font-weight-bold">Thông tin hủy phiếu cũ</div>
                            <div><strong>Người hủy:</strong> {{ $qualityCertificate->revokedBy->name ?? '-' }}</div>
                            <div><strong>Ngày hủy:</strong> {{ $qualityCertificate->revoked_at ? $qualityCertificate->revoked_at->format('d/m/Y H:i') : '-' }}</div>
                            <div><strong>Lý do:</strong> {{ $qualityCertificate->revoked_reason ?: '-' }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-6 workflow-info">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">Khách hàng / Công trình</h3>
            </div>

            <div class="card-body">
                <p><strong>Khách hàng:</strong> {{ $qualityCertificate->request->customer->customer_name ?? '-' }}</p>
                <p><strong>Địa chỉ KH:</strong> {{ $qualityCertificate->request->customer->customer_address ?? '-' }}</p>
                <p><strong>Email:</strong> {{ $qualityCertificate->request->customer->email ?? '-' }}</p>
                <p><strong>Công trình:</strong> {{ $qualityCertificate->request->customer->project_name ?? '-' }}</p>
                <p><strong>Địa điểm công trình:</strong> {{ $qualityCertificate->request->customer->project_address ?? '-' }}</p>
                <p><strong>Trung tâm:</strong> {{ $qualityCertificate->request->distributionCenter->name ?? '-' }}</p>
                <p><strong>Đơn vị bán hàng:</strong> {{ $qualityCertificate->request?->salesUnit ? $qualityCertificate->request->salesUnit->code . ' - ' . $qualityCertificate->request->salesUnit->name : '-' }}</p>
                <p><strong>Ngày xuất hàng:</strong> {{ $qualityCertificate->request->delivery_date ? $qualityCertificate->request->delivery_date->format('d/m/Y') : '-' }}</p>
                <p><strong>Số hóa đơn:</strong> {{ $qualityCertificate->request->invoice_no ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-header bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap:8px">
            <h3 class="card-title"><i class="fas fa-box"></i> Danh sách sản phẩm trên phiếu <small id="certificateProductsCount">{{ $qualityCertificate->details->count() }} dòng</small></h3>
            <input type="search" class="form-control form-control-sm workflow-product-search" data-product-search="certificateProducts" aria-label="Tìm mã hoặc tên sản phẩm" placeholder="Tìm mã hoặc tên sản phẩm">
        </div>
    </div>

    <div class="card-body table-responsive p-0 workflow-detail-table">
        <table id="certificateProducts" class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Tên sản phẩm</th>
                    <th>ĐVT</th>
                    <th style="width:120px">Số lượng</th>
                    <th style="width:160px">Kích thước danh nghĩa</th>
                    <th>Yêu cầu kỹ thuật</th>
                    <th style="width:180px">Tiêu chuẩn sản phẩm</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($qualityCertificate->details as $detail)
                    <tr data-product-text="{{ ($detail->product->product_code ?? '') . ' ' . ($detail->product->product_name ?? '') }}">
                        <td>{{ $loop->iteration }}</td>
                        <td class="workflow-product-name">
                            <strong>{{ $detail->product->product_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $detail->product->product_code ?? '' }}</div>
                        </td>
                        <td>{{ $detail->product->unit ?? '-' }}</td>
                        <td>{{ rtrim(rtrim(number_format($detail->quantity, 2, '.', ''), '0'), '.') }}</td>
                        <td>{{ $detail->nominal_size ?: '-' }}</td>
                        <td>{{ $detail->technical_requirements ?: '-' }}</td>
                        <td>{{ $detail->quality_standard ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<details class="mb-3">
    <summary class="mb-2" style="cursor:pointer">Lịch sử xử lý phiếu</summary>
    @include('quality_certificates.partials.history_timeline', ['logs' => $certificateHistoryLogs])
</details>

<details class="card">
    <summary class="card-header bg-white" style="cursor:pointer"><i class="fas fa-history"></i> Lịch sử in ký tươi ({{ $qualityCertificate->printLogs->count() }})</summary>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:80px">Lần in</th>
                    <th style="width:110px">Mẫu in</th>
                    <th style="width:120px">Loại in</th>
                    <th>Người in</th>
                    <th>Lý do</th>
                    <th>Thời gian</th>
                </tr>
            </thead>

            <tbody>
                @forelse($qualityCertificate->printLogs as $log)
                    <tr>
                        <td>{{ $log->print_no }}</td>
                        <td>
                            @if(($log->print_template ?? 'single') === 'batch')
                                <span class="badge badge-info">In bộ</span>
                            @else
                                <span class="badge badge-warning">In đơn</span>
                            @endif
                        </td>
                        <td>
                            @if(($log->print_mode ?? 'normal') === 'emergency')
                                <span class="badge badge-danger">Khẩn cấp</span>
                            @else
                                <span class="badge badge-success">Sau ký số</span>
                            @endif
                        </td>
                        <td>{{ $log->user->name ?? '-' }}</td>
                        <td>{{ $log->reason }}</td>
                        <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            Chưa có lịch sử in ký tươi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</details>

@can('certificate.print')
<div class="modal fade" id="printHardCopyModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('quality-certificates.print-hard-copy', $qualityCertificate) }}"
              target="_blank" class="modal-content">
            @csrf
            <input type="hidden" name="print_template" id="printHardCopyTemplate" value="single">
            <input type="hidden" name="print_mode" id="printHardCopyMode" value="normal">

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-print"></i> <span id="printHardCopyTitle">In đơn</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning">
                    Mỗi lần in phiếu ký tươi phải nhập lý do và sẽ được lưu lịch sử.
                    <br>
                    Phiếu này đã in: <strong>{{ $qualityCertificate->print_count }}</strong> lần.
                    <div class="mt-2" id="printHardCopyDescription">
                        In theo mẫu ký tươi hiện tại, giữ vùng ghi chú/chữ ký cố định trên tất cả các trang.
                    </div>
                    <div class="mt-2 d-none" id="printHardCopyEmergencyWarning">
                        <strong>Lưu ý:</strong> Phiếu này chưa được ký số điện tử. In ký tươi khẩn cấp chỉ dùng khi SmartCA lỗi/quá hạn hoặc khách hàng cần gấp. Hệ thống sẽ lưu lịch sử thao tác này.
                    </div>
                </div>

                <div class="form-group">
                    <label>Lý do in <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required
                              placeholder="Ví dụ: Khách hàng yêu cầu bản ký tươi"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-warning" id="printHardCopySubmit"><i class="fas fa-print"></i> In đơn</button>
            </div>
        </form>
    </div>
</div>
@endcan

@can('ptn.process')
@if($canReturnRejectedCertificateRequestToDvkh)
<div class="modal fade" id="returnRejectedToDvkhModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST"
              action="{{ route('ptn.requests.return-to-dvkh', $qualityCertificate->request) }}"
              class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-undo"></i> Trả lại DVKH</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning">
                    Phiếu này đã được Trưởng PTN trả về PTN xử lý lại. Nếu nội dung cần DVKH xác nhận lại, hãy nhập rõ lý do để chuyển yêu cầu về bước DVKH.
                </div>

                <div class="form-group">
                    <label>Lý do trả lại DVKH <span class="text-danger">*</span></label>
                    <textarea name="reason"
                              class="form-control @error('reason') is-invalid @enderror"
                              rows="4"
                              required
                              placeholder="Ví dụ: Thông tin khách hàng/công trình cần DVKH xác nhận lại...">{{ old('reason') }}</textarea>
                    @error('reason')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-warning">
                    <i class="fas fa-paper-plane"></i> Trả lại DVKH
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@can('certificate.reject')
@if(!$qualityCertificate->signed_at && $qualityCertificate->status !== 'REJECTED' && ($qualityCertificate->smartca_status !== 'PENDING' || $smartCaPendingExpired))
<div class="modal fade" id="rejectSignatureModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST"
              action="{{ route('quality-certificates.reject-signature', $qualityCertificate) }}"
              class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-undo"></i> Từ chối ký / trả lại phiếu</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning">
                    Phiếu này sẽ bị đánh dấu đã trả lại và không dùng để ký số. Hệ thống sẽ mở lại yêu cầu gốc theo bước anh chọn bên dưới.
                </div>

                <div class="form-group">
                    <label>Trả về bước <span class="text-danger">*</span></label>
                    <select name="reject_to" class="form-control" required>
                        <option value="PTN">PTN xử lý lại</option>
                        <option value="DVKH">DVKH xác nhận lại</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lý do trả lại <span class="text-danger">*</span></label>
                    <textarea name="rejected_reason"
                              class="form-control"
                              rows="4"
                              required
                              placeholder="Nhập lý do để PTN/DVKH biết cần sửa gì"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-danger">
                    <i class="fas fa-paper-plane"></i> Xác nhận trả lại
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@can('request.create')
@if($qualityCertificate->canRequestReissue())
<div class="modal fade" id="reissueModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST"
              action="{{ route('quality-certificates.request-reissue', $qualityCertificate) }}"
              class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-redo"></i> Yêu cầu cấp lại phiếu</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <p>Phiếu: <strong>{{ $qualityCertificate->certificate_no }}</strong></p>
                <div class="alert alert-warning">
                    Yêu cầu này sẽ được gửi sang DVKH. Khi DVKH xác nhận, phiếu cũ sẽ bị hủy/thu hồi và quy trình cấp phiếu mới bắt đầu.
                </div>

                <div class="form-group">
                    <label>Lý do cấp lại <span class="text-danger">*</span></label>
                    <textarea name="reissue_reason"
                              class="form-control"
                              rows="4"
                              required
                              placeholder="Nhập lý do cần cấp lại phiếu"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-danger">
                    <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

@component('components.workflow_action_bar', [
    'number' => $qualityCertificate->certificate_no,
    'status' => $statusMeta,
    'backUrl' => route('quality-certificates.index'),
])
@include('quality_certificates.partials.detail_actions')
@endcomponent

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-print-hard-copy').forEach(function (button) {
            button.addEventListener('click', function () {
                var template = button.getAttribute('data-template') || 'single';
                var mode = button.getAttribute('data-mode') || 'normal';
                var label = button.getAttribute('data-label') || 'In đơn';
                var description = button.getAttribute('data-description') || '';
                var submitClass = mode === 'emergency'
                    ? (template === 'batch' ? 'btn btn-outline-info' : 'btn btn-outline-warning')
                    : (template === 'batch' ? 'btn btn-info' : 'btn btn-warning');

                var templateInput = document.getElementById('printHardCopyTemplate');
                var modeInput = document.getElementById('printHardCopyMode');
                var title = document.getElementById('printHardCopyTitle');
                var descriptionBox = document.getElementById('printHardCopyDescription');
                var emergencyWarning = document.getElementById('printHardCopyEmergencyWarning');
                var submit = document.getElementById('printHardCopySubmit');

                if (templateInput) {
                    templateInput.value = template;
                }

                if (modeInput) {
                    modeInput.value = mode;
                }

                if (title) {
                    title.textContent = label;
                }

                if (descriptionBox) {
                    descriptionBox.textContent = description;
                }

                if (emergencyWarning) {
                    emergencyWarning.classList.toggle('d-none', mode !== 'emergency');
                }

                if (submit) {
                    submit.className = submitClass;
                    submit.innerHTML = '<i class="fas fa-print"></i> ' + label;
                    submit.onclick = function () {
                        if (mode !== 'emergency') {
                            return true;
                        }

                        return confirm('Phiếu chưa ký số điện tử. Xác nhận in ký tươi khẩn cấp và lưu lịch sử thao tác này?');
                    };
                }
            });
        });
    });
</script>
@stop
