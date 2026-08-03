@if(($certificateRequest->request_type ?? 'NORMAL') === 'REISSUE')
    <span class="badge badge-danger">
        <i class="fas fa-redo"></i> Cấp lại
    </span>
    @if($certificateRequest->reissueOfCertificate)
        <div class="text-danger small mt-1">
            Phiếu cũ: {{ $certificateRequest->reissueOfCertificate->certificate_no }}
        </div>
    @endif
@endif
