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
                    @if ($qualityCertificate->status === 'REVOKED')
                        <span class="badge badge-danger">Đã hủy/thu hồi</span>
                    @elseif ($qualityCertificate->signed_at)
                        <span class="badge badge-success">Đã ký/phát hành</span>
                    @else
                        <span class="badge badge-warning">Chưa ký</span>
                    @endif
                </p>
                @if($qualityCertificate->replacesCertificate)
                    <p>
                        <strong>Cấp lại cho phiếu:</strong>
                        <a href="{{ route('quality-certificates.show', $qualityCertificate->replacesCertificate) }}">
                            {{ $qualityCertificate->replacesCertificate->certificate_no }}
                        </a>
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
                <p><strong>Người ký:</strong> {{ $qualityCertificate->signed_by ?: '-' }}</p>
                <p><strong>Ngày ký:</strong> {{ $qualityCertificate->signed_at ? $qualityCertificate->signed_at->format('d/m/Y H:i') : '-' }}</p>
                <p>
                    <strong>SmartCA:</strong>
                    @if ($qualityCertificate->smartca_status === 'PENDING')
                        <span class="badge badge-primary">Đang chờ xác nhận</span>
                    @elseif ($qualityCertificate->smartca_status === 'SIGNED')
                        <span class="badge badge-success">Đã ký SmartCA</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </p>
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
                                    <button class="btn btn-primary">
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
                            @if ($qualityCertificate->smartca_status === 'PENDING')
                                <form action="{{ route('quality-certificates.smartca-status', $qualityCertificate) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-primary">
                                        <i class="fas fa-sync"></i> Kiểm tra kết quả ký
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('quality-certificates.sign', $qualityCertificate) }}" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Gửi yêu cầu ký phiếu này sang VNPT SmartCA?')">
                                    @csrf
                                    <button class="btn btn-success">
                                        <i class="fas fa-file-signature"></i> Gửi yêu cầu ký SmartCA
                                    </button>
                                </form>
                            @endif
                        @endcan
                    @endif
                </div>
            </div>
        </div>
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
