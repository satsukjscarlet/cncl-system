@extends('adminlte::page')

@section('title', 'Chi tiết Phiếu CNCL')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Chi tiết Phiếu CNCL</h1>
        <small class="text-muted">{{ $qualityCertificate->certificate_no }}</small>
    </div>

    <a href="{{ route('quality-certificates.index') }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
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
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Thông tin phiếu</h3>
            </div>

            <div class="card-body">
                <p><strong>Số phiếu:</strong> {{ $qualityCertificate->certificate_no }}</p>
                <p><strong>Số yêu cầu:</strong> {{ $qualityCertificate->request->request_no ?? '-' }}</p>
                <p><strong>Người lập:</strong> {{ $qualityCertificate->creator->name ?? '-' }}</p>
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
                @if ($qualityCertificate->smartca_transaction_id)
                    <p><strong>Mã giao dịch:</strong> <code>{{ $qualityCertificate->smartca_transaction_id }}</code></p>
                @endif
                <p><strong>Số lần in ký tươi:</strong> {{ $qualityCertificate->print_count }}</p>
            </div>

            <div class="card-footer">
                <div class="d-flex flex-wrap align-items-center" style="gap:8px">
                    @can('certificate.view')
                        <a href="{{ route('quality-certificates.pdf', $qualityCertificate) }}" target="_blank" class="btn btn-info">
                            <i class="fas fa-file-pdf"></i> Xem PDF
                        </a>
                    @endcan

                    @if ($qualityCertificate->signed_at)
                        @can('certificate.email')
                            @if($qualityCertificate->status !== 'REVOKED')
                                <form action="{{ route('quality-certificates.resend-email', $qualityCertificate) }}" method="POST"
                                      class="d-inline" onsubmit="return confirm('Gửi lại email phiếu CNCL cho khách hàng?')">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-envelope"></i> Gửi lại email
                                    </button>
                                </form>
                            @endif
                        @endcan

                        <button class="btn btn-secondary" disabled>
                            <i class="fas fa-lock"></i> Phiếu đã khóa
                        </button>

                        @can('certificate.print')
                            @if($qualityCertificate->status !== 'REVOKED')
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#printHardCopyModal">
                                    <i class="fas fa-print"></i> In ký tươi
                                </button>
                            @endif
                        @endcan

                        @can('request.create')
                            @if($qualityCertificate->canRequestReissue())
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#reissueModal">
                                    <i class="fas fa-redo"></i> Yêu cầu cấp lại
                                </button>
                            @endif
                        @endcan
                    @else
                        @can('certificate.sign')
                            @if ($qualityCertificate->status === 'REJECTED')
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-undo"></i> Phiếu đã trả lại
                                </button>
                            @elseif (in_array($qualityCertificate->smartca_status, ['PENDING', 'EXPIRED'], true) && $qualityCertificate->smartca_transaction_id)
                                <form action="{{ route('quality-certificates.smartca-status', $qualityCertificate) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message'))"
                                      data-loading-lock
                                      data-loading-message="Đang kiểm tra kết quả ký VNPT SmartCA và gửi email nếu ký thành công. Vui lòng chờ...">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync"></i> Kiểm tra kết quả ký
                                    </button>
                                </form>
                                @if($smartCaCanResend)
                                    <form action="{{ route('quality-certificates.sign', $qualityCertificate) }}" method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('Gửi lại yêu cầu ký phiếu này sang VNPT SmartCA? Hệ thống sẽ kiểm tra giao dịch cũ trước khi gửi lại.')">
                                        @csrf
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-file-signature"></i> Gửi lại yêu cầu ký
                                        </button>
                                    </form>
                                @endif
                            @else
                                @if($canApproveForSigning)
                                    <form action="{{ route('quality-certificates.approve-for-signing', $qualityCertificate) }}"
                                          method="POST"
                                          class="d-inline"
                                          data-loading-lock
                                          data-loading-message="Đang đưa phiếu vào danh sách chờ gửi ký. Vui lòng chờ..."
                                          onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Đưa vào chờ gửi ký
                                        </button>
                                    </form>
                                @endif

                                <form action="{{ route('quality-certificates.sign', $qualityCertificate) }}" method="POST"
                                      class="d-inline"
                                      data-loading-lock
                                      data-loading-message="Đang gửi yêu cầu ký sang VNPT SmartCA. Vui lòng chờ..."
                                      onsubmit="if (!confirm('{{ $qualityCertificate->isAwaitingManagerApproval() ? 'Gửi ký trực tiếp phiếu này? Thao tác này đồng thời xác nhận Trưởng PTN đã duyệt nội dung.' : ($smartCaCanResend ? 'Gửi lại yêu cầu ký phiếu này sang VNPT SmartCA?' : 'Gửi yêu cầu ký phiếu này sang VNPT SmartCA?') }}')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                    @csrf
                                    <button type="submit" class="btn {{ $smartCaCanResend ? 'btn-warning' : 'btn-success' }}" {{ $canSendSignature ? '' : 'disabled' }}>
                                        <i class="fas fa-file-signature"></i> {{ $qualityCertificate->isAwaitingManagerApproval() ? 'Duyệt và gửi ký SmartCA' : ($smartCaCanResend ? 'Gửi lại yêu cầu ký' : 'Gửi yêu cầu ký SmartCA') }}
                                    </button>
                                </form>
                            @endif
                        @endcan

                        @can('certificate.reject')
                            @if($qualityCertificate->status !== 'REJECTED' && ($qualityCertificate->smartca_status !== 'PENDING' || $smartCaPendingExpired))
                                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectSignatureModal">
                                    <i class="fas fa-undo"></i> Từ chối ký
                                </button>
                            @endif
                        @endcan
                    @endif
                </div>
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

    <div class="col-md-8">
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
                <p><strong>Ngày xuất hàng:</strong> {{ $qualityCertificate->request->delivery_date ? $qualityCertificate->request->delivery_date->format('d/m/Y') : '-' }}</p>
                <p><strong>Số hóa đơn:</strong> {{ $qualityCertificate->request->invoice_no ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>


@role('Admin')
@if (!empty($qualityCertificate->smartca_response))
<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-code"></i> Du lieu API VNPT SmartCA</h3>
    </div>

    <div class="card-body">
        @foreach ($qualityCertificate->smartca_response as $apiName => $apiData)
            @php
                $apiData = is_array($apiData) ? $apiData : ['response' => $apiData];
                $hasStructuredApiLog = array_key_exists('request', $apiData) || array_key_exists('response', $apiData);
                $requestPayload = $hasStructuredApiLog ? ($apiData['request'] ?? []) : [];
                $responsePayload = $hasStructuredApiLog ? ($apiData['response'] ?? []) : $apiData;
            @endphp
            <div class="mb-4">
                <h5 class="mb-2">
                    <span class="badge badge-info">{{ strtoupper(str_replace('_', ' ', $apiName)) }}</span>
                </h5>

                <div class="mb-2">
                    <strong>Endpoint:</strong>
                    <code>{{ $apiData['endpoint'] ?? '-' }}</code>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <label>Request gui di</label>
                        <pre class="bg-light border rounded p-3 small" style="max-height:360px; overflow:auto; white-space:pre-wrap;">{{ json_encode($requestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div class="col-md-6">
                        <label>Response nhan ve</label>
                        <pre class="bg-light border rounded p-3 small" style="max-height:360px; overflow:auto; white-space:pre-wrap;">{{ json_encode($responsePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
@endrole

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-box"></i> Danh sách sản phẩm trên phiếu</h3>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Tên sản phẩm</th>
                    <th style="width:120px">Số lượng</th>
                    <th style="width:160px">Kích thước danh nghĩa</th>
                    <th>Yêu cầu kỹ thuật</th>
                    <th style="width:180px">Tiêu chuẩn sản phẩm</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($qualityCertificate->details as $detail)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <strong>{{ $detail->product->product_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $detail->product->product_code ?? '' }}</div>
                        </td>
                        <td>{{ $detail->quantity }}</td>
                        <td>{{ $detail->nominal_size ?: '-' }}</td>
                        <td>{{ $detail->technical_requirements ?: '-' }}</td>
                        <td>{{ $detail->quality_standard ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@include('quality_certificates.partials.history_timeline', ['logs' => $certificateHistoryLogs])

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-history"></i> Lịch sử in ký tươi</h3>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:80px">Lần in</th>
                    <th>Người in</th>
                    <th>Lý do</th>
                    <th>Thời gian</th>
                </tr>
            </thead>

            <tbody>
                @forelse($qualityCertificate->printLogs as $log)
                    <tr>
                        <td>{{ $log->print_no }}</td>
                        <td>{{ $log->user->name ?? '-' }}</td>
                        <td>{{ $log->reason }}</td>
                        <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">
                            Chưa có lịch sử in ký tươi.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@can('certificate.print')
<div class="modal fade" id="printHardCopyModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('quality-certificates.print-hard-copy', $qualityCertificate) }}"
              target="_blank" class="modal-content">
            @csrf

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-print"></i> In phiếu ký tươi</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body">
                <div class="alert alert-warning">
                    Mỗi lần in phiếu ký tươi phải nhập lý do và sẽ được lưu lịch sử.
                    <br>
                    Phiếu này đã in: <strong>{{ $qualityCertificate->print_count }}</strong> lần.
                </div>

                <div class="form-group">
                    <label>Lý do in <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="4" required
                              placeholder="Ví dụ: Khách hàng yêu cầu bản ký tươi"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                <button class="btn btn-warning"><i class="fas fa-print"></i> In phiếu</button>
            </div>
        </form>
    </div>
</div>
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
@stop
