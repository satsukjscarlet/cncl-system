@component('components.workflow_action_bar', [
    'number' => $certificateRequest->request_no,
    'status' => $certificateRequest->displayStatusMeta(),
    'backUrl' => route('dvkh.requests.index'),
    'barId' => 'dvkhActionBar',
    'spacerId' => 'dvkhActionSpacer',
    'warning' => $invoiceDuplicates->isNotEmpty() ? 'Trùng số hóa đơn' : null,
])
    @can('dvkh.process')
        @if($certificateRequest->status === 'WAIT_DVKH')
            @php
                $approveConfirm = $invoiceDuplicates->isNotEmpty()
                    ? 'Số hóa đơn của yêu cầu này đang trùng với yêu cầu khác. Bạn vẫn muốn xác nhận và chuyển sang PTN?'
                    : 'Xác nhận yêu cầu này và chuyển sang PTN?';
                if (\App\Support\FutureDeliveryDate::isFuture($certificateRequest->delivery_date?->toDateString())) {
                    $approveConfirm = 'Ngày xuất hàng ' . $certificateRequest->delivery_date->format('d/m/Y') . ' đang sau ngày hiện tại. ' . $approveConfirm;
                }
            @endphp
            <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#dvkhRejectModal">
                <i class="fas fa-undo"></i> Trả lại Trung tâm
            </button>
            <form action="{{ route('dvkh.requests.approve', $certificateRequest) }}" method="POST"
                  data-loading-lock
                  data-loading-message="Đang xác nhận yêu cầu và chuyển sang PTN, vui lòng chờ..."
                  onsubmit="if (!confirm(@js($approveConfirm))) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                @csrf
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Xác nhận chuyển PTN</button>
            </form>
        @endif
    @endcan
@endcomponent
