<style>
    .request-detail-actions {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 20px calc(12px + env(safe-area-inset-bottom, 0px));
        background: #fff;
        border-top: 1px solid #d9e1e9;
        box-shadow: 0 -3px 12px rgba(0, 0, 0, .08);
    }
    .request-detail-actions-summary { min-width: 0; overflow-wrap: anywhere; }
    .request-detail-actions-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
    .request-detail-actions-spacer { height: 180px; }
    @media (max-width: 767.98px) {
        .request-detail-actions { flex-direction: column; align-items: stretch; }
        .request-detail-actions-buttons > * { flex: 1 1 150px; white-space: normal; }
    }
    @media print {
        .request-detail-actions, .request-detail-actions-spacer { display: none; }
    }
</style>
<div class="request-detail-actions-spacer" id="requestDetailActionSpacer" aria-hidden="true"></div>
<div class="request-detail-actions" id="requestDetailActionBar" role="region" aria-label="Thao tác yêu cầu">
    <div class="request-detail-actions-summary">
        <div class="font-weight-bold">{{ $certificateRequest->request_no }}</div>
        @include('certificate_requests.partials.status_badge', ['certificateRequest' => $certificateRequest])
    </div>
    <div class="request-detail-actions-buttons">
        <a href="{{ route('certificate-requests.index') }}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
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
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bar = document.getElementById('requestDetailActionBar');
        const spacer = document.getElementById('requestDetailActionSpacer');
        const content = document.querySelector('.content-wrapper');
        if (!bar || !spacer || !content) return;

        function syncActionBar() {
            const bounds = content.getBoundingClientRect();
            bar.style.left = Math.max(0, bounds.left) + 'px';
            bar.style.right = Math.max(0, window.innerWidth - bounds.right) + 'px';
            spacer.style.height = (bar.getBoundingClientRect().height + 16) + 'px';
        }

        syncActionBar();
        window.addEventListener('resize', syncActionBar);
        if (window.ResizeObserver) {
            const observer = new ResizeObserver(syncActionBar);
            observer.observe(content);
            observer.observe(bar);
        }
        if (window.jQuery) {
            window.jQuery(document).on('shown.lte.pushmenu collapsed.lte.pushmenu', syncActionBar);
        }
    });
</script>
