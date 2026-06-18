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
                    @if ($qualityCertificate->signed_at)
                        <span class="badge badge-success">Đã ký/phát hành</span>
                    @else
                        <span class="badge badge-warning">Chưa ký</span>
                    @endif
                </p>
                <p><strong>Người ký:</strong> {{ $qualityCertificate->signed_by ?: '-' }}</p>
                <p><strong>Ngày ký:</strong> {{ $qualityCertificate->signed_at ? $qualityCertificate->signed_at->format('d/m/Y H:i') : '-' }}</p>
                <p><strong>Số lần in ký tươi:</strong> {{ $qualityCertificate->print_count }}</p>
            </div>

            <div class="card-footer">
                @can('certificate.view')
                    <a href="{{ route('quality-certificates.pdf', $qualityCertificate) }}" target="_blank" class="btn btn-info">
                        <i class="fas fa-file-pdf"></i> Xem PDF
                    </a>
                @endcan

                @if ($qualityCertificate->signed_at)
                    @can('certificate.email')
                        <form action="{{ route('quality-certificates.resend-email', $qualityCertificate) }}" method="POST"
                              class="d-inline" onsubmit="return confirm('Gửi lại email phiếu CNCL cho khách hàng?')">
                            @csrf
                            <button class="btn btn-primary">
                                <i class="fas fa-envelope"></i> Gửi lại email
                            </button>
                        </form>
                    @endcan
                @endif

                @if (!$qualityCertificate->signed_at)
                    @can('certificate.sign')
                        <form action="{{ route('quality-certificates.sign', $qualityCertificate) }}" method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Ký/phát hành phiếu này? Sau khi ký, phiếu sẽ bị khóa dữ liệu.')">
                            @csrf
                            <button class="btn btn-success">
                                <i class="fas fa-file-signature"></i> Ký / Phát hành
                            </button>
                        </form>
                    @endcan
                @else
                    <button class="btn btn-secondary" disabled>
                        <i class="fas fa-lock"></i> Phiếu đã khóa
                    </button>

                    @can('certificate.print')
                        <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#printHardCopyModal">
                            <i class="fas fa-print"></i> In ký tươi
                        </button>
                    @endcan
                @endif
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
@stop
