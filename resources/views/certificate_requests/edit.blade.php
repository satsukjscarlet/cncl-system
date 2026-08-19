@extends('adminlte::page')

@section('title', 'Cập nhật yêu cầu cấp phiếu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260818-1') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật yêu cầu cấp phiếu</h1>
        <small class="text-muted">{{ $certificateRequest->request_no }}</small>
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

@if(($certificateRequest->request_type ?? 'NORMAL') === 'REISSUE')
    <div class="alert alert-warning">
        <div class="font-weight-bold mb-1">
            <i class="fas fa-redo"></i> Đây là yêu cầu cấp lại phiếu CNCL
        </div>
        <div>
            Phiếu cũ:
            @if($certificateRequest->reissueOfCertificate)
                <a href="{{ route('quality-certificates.show', $certificateRequest->reissueOfCertificate) }}" target="_blank">
                    {{ $certificateRequest->reissueOfCertificate->certificate_no }}
                </a>
            @else
                -
            @endif
        </div>
        @if($certificateRequest->reissueCertificates->count() > 1)
            <div class="mt-1">
                Các phiếu cũ được gom:
                @foreach($certificateRequest->reissueCertificates as $oldCertificate)
                    <a href="{{ route('quality-certificates.show', $oldCertificate) }}" target="_blank" class="badge badge-light">
                        {{ $oldCertificate->certificate_no }}
                    </a>
                @endforeach
            </div>
        @endif
        <div>Lý do cấp lại: {{ $certificateRequest->reissue_reason ?: '-' }}</div>
        <div class="small mt-2">
            Bạn có thể chỉnh lại khách hàng, công trình, ngày xuất hàng, số hóa đơn, ghi chú và danh sách sản phẩm trước khi DVKH xác nhận.
            Khi DVKH xác nhận, phiếu cũ sẽ được hủy / thu hồi và yêu cầu này sẽ chuyển sang PTN lập phiếu mới.
        </div>
    </div>
@endif

<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit"></i> Thông tin yêu cầu
        </h3>
    </div>

    <form method="POST" action="{{ route('certificate-requests.update', $certificateRequest) }}" class="cncl-form certificate-request-form">
        @method('PUT')

        <div class="card-body">
            @include('certificate_requests._form')
        </div>
    </form>
</div>
@stop
