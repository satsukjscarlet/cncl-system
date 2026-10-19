@component('components.workflow_action_bar', [
    'number' => $certificateRequest->request_no,
    'status' => $certificateRequest->displayStatusMeta(),
    'backUrl' => route('certificate-requests.index'),
    'barId' => 'requestDetailActionBar',
    'spacerId' => 'requestDetailActionSpacer',
    'warning' => ($invoiceDuplicates ?? collect())->isNotEmpty() ? 'Trùng số hóa đơn' : null,
])
        @if($certificateRequest->status === 'DRAFT')
            @can('request.update')
                <a href="{{ route('certificate-requests.edit', $certificateRequest) }}" class="btn btn-outline-primary">
                    <i class="fas fa-edit"></i> Sửa yêu cầu
                </a>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#submitDraftModal">
                    <i class="fas fa-paper-plane"></i> Gửi DVKH
                </button>
            @endcan
        @elseif(!$isCenterAccount && $certificateRequest->status === 'WAIT_DVKH' && auth()->user()->can('dvkh.process'))
            <a href="{{ route('dvkh.requests.show', $certificateRequest) }}" class="btn btn-primary">
                <i class="fas fa-user-check"></i> Mở màn hình DVKH xử lý
            </a>
        @elseif(!$isCenterAccount && in_array($certificateRequest->status, ['WAIT_PTN', 'PTN_PROCESSING'], true) && auth()->user()->can('ptn.process'))
            <a href="{{ route('ptn.requests.show', $certificateRequest) }}" class="btn btn-primary">
                <i class="fas fa-vials"></i> Mở màn hình PTN xử lý
            </a>
        @elseif($canOpenCertificate)
            <a href="{{ route('quality-certificates.show', $relatedCertificate) }}" class="btn btn-primary">
                <i class="fas fa-certificate"></i>
                @if($relatedCertificate->status === 'ISSUED' && $relatedCertificate->signed_at)
                    Xem phiếu đã phát hành
                @elseif(auth()->user()->can('certificate.sign') && !$relatedCertificate->signed_at)
                    Mở phiếu để duyệt/ký
                @else
                    Xem phiếu CNCL
                @endif
            </a>
        @endif

@endcomponent
