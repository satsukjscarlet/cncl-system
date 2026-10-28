@extends('adminlte::page')
@section('title', $item->number . ' | Bổ sung bản ký tươi')
@section('css')
<link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20261026') }}">
<link rel="stylesheet" href="{{ asset('css/hard-copy-supplements.css?v=20261028-2') }}">
@stop
@section('content_header')
<div class="supplement-header"><div><h1>{{ $item->number }}</h1><small class="text-muted">Chi tiết yêu cầu bổ sung bản ký tươi</small></div>
<div class="supplement-links"><a href="{{ route('hard-copy-supplements.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Danh sách yêu cầu</a></div></div>
@stop
@section('content')
@php
    $user = auth()->user();
    $printCount = $item->print_logs_count;
    $canEdit = $validCertificate && $service->center($user, $item->certificate) && in_array($item->status, ['WAIT_DVKH', 'RETURNED']);
    $canApprove = $validCertificate && $service->dvkh($user) && $item->status === 'WAIT_DVKH';
    $canReturn = $validCertificate && $service->dvkh($user) && in_array($item->status, ['WAIT_DVKH', 'WAIT_PTN']) && !$printCount;
    $canPrint = $validCertificate && $service->ptn($user) && $item->status === 'WAIT_PTN';
    $canCancel = ($service->center($user, $item->certificate) && in_array($item->status, ['WAIT_DVKH', 'RETURNED']))
        || ($service->dvkh($user) && in_array($item->status, ['WAIT_DVKH', 'RETURNED', 'WAIT_PTN']));
    $latestReturn = $item->events->last(fn ($e) => $e->action === 'return');
    $latestCancel = $item->events->last(fn ($e) => $e->action === 'cancel');
    $eventLabels = ['created' => 'Gửi yêu cầu', 'resubmit' => 'Sửa / gửi lại', 'approve' => 'DVKH xác nhận', 'return' => 'DVKH trả lại', 'cancel' => 'Hủy yêu cầu', 'printed' => 'Mở file in', 'complete' => 'PTN hoàn tất'];
@endphp
<div class="supplement-detail">
@include('hard_copy_supplements.messages')
@if(!$validCertificate)<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Phiếu đã hủy/thu hồi, chưa đủ điều kiện hoặc đang xin cấp lại. Không được duyệt, in hoặc hoàn tất bổ sung.</div>@endif
@if($item->status === 'RETURNED')<div class="alert alert-warning"><strong>DVKH trả lại:</strong> {{ $latestReturn?->data['comment'] ?? 'Vui lòng kiểm tra lịch sử xử lý.' }}<div class="small mt-1">{{ $latestReturn?->user?->name }} · {{ $latestReturn?->created_at?->format('d/m/Y H:i') }}</div></div>@endif
@if($item->status === 'CANCELLED')<div class="alert alert-secondary"><strong>Yêu cầu đã hủy:</strong> {{ $latestCancel?->data['comment'] ?? '-' }}</div>@endif
@if($item->status === 'COMPLETED')<div class="alert alert-success"><strong>Đã hoàn tất {{ number_format($item->quantity) }} bản ký tươi</strong> · {{ $item->completer?->name }} · {{ $item->completed_at?->format('d/m/Y H:i') }}</div>@endif

<div class="supplement-overview">
    <div><span class="text-muted small">Trạng thái</span><div class="mt-1"><span class="badge {{ $item->status_meta['class'] }}">{{ $item->status_label }}</span></div></div>
    <div><span class="text-muted small">Số bản bổ sung</span><strong>{{ number_format($item->quantity) }} bản</strong></div>
    <div><span class="text-muted small">Ngày gửi yêu cầu</span><strong>{{ $item->submitted_at?->format('d/m/Y H:i') ?: '-' }}</strong></div>
    <div><span class="text-muted small">Mẫu mở gần nhất</span><strong>{{ $item->print_template ? ($item->print_template === 'batch' ? 'In bộ' : 'In đơn') : 'Chưa chọn mẫu' }}</strong></div>
</div>
<h2 class="supplement-process-title"><i class="fas fa-tasks mr-2 text-primary"></i>Tiến trình xử lý</h2>
<div class="supplement-stages" aria-label="Tiến trình bổ sung bản ký tươi">
    <div class="supplement-stage done"><strong><i class="fas fa-paper-plane mr-1"></i> Trung tâm gửi yêu cầu</strong><span class="small">{{ $item->requester?->name }}<br>{{ $item->submitted_at?->format('d/m/Y H:i') }}</span></div>
    <div class="supplement-stage {{ $item->approved_at ? 'done' : (in_array($item->status, ['RETURNED', 'CANCELLED']) ? 'stopped' : ($validCertificate ? 'current' : '')) }}"><strong><i class="fas fa-user-check mr-1"></i> DVKH xác nhận</strong><span class="small">{{ $item->approved_at ? $item->approver?->name : ($item->status === 'RETURNED' ? 'Đã trả lại Trung tâm' : ($item->status === 'CANCELLED' ? 'Đã dừng' : ($validCertificate ? 'Đang chờ xác nhận' : 'Tạm dừng xử lý'))) }}<br>{{ $item->approved_at?->format('d/m/Y H:i') }}</span></div>
    <div class="supplement-stage {{ $item->completed_at ? 'done' : ($item->status === 'CANCELLED' ? 'stopped' : ($item->status === 'WAIT_PTN' && $validCertificate ? 'current' : '')) }}"><strong><i class="fas fa-print mr-1"></i> PTN hoàn tất</strong><span class="small">{{ $item->completed_at ? $item->completer?->name : ($item->status === 'WAIT_PTN' ? ($validCertificate ? ($printCount ? 'Đã mở file in · chờ hoàn tất' : 'Đang chờ in / hoàn tất') : 'Tạm dừng xử lý') : ($item->status === 'CANCELLED' ? 'Đã dừng' : 'Chưa đến bước')) }}<br>{{ $item->completed_at?->format('d/m/Y H:i') }}</span></div>
</div>

<div class="row">
<div class="col-lg-7">
    <section class="supplement-section"><h2><i class="fas fa-file-alt text-primary mr-2"></i>Thông tin phiếu và khách hàng</h2><dl class="supplement-facts">
        <dt>Phiếu CNCL</dt><dd>@can('certificate.view')<a href="{{ route('quality-certificates.show', $item->certificate) }}">{{ $item->certificate->certificate_no }}</a>@else{{ $item->certificate->certificate_no }}@endcan</dd>
        <dt>Yêu cầu gốc</dt><dd>@can('request.view')<a href="{{ route('certificate-requests.show', $item->certificate->certificate_request_id) }}">{{ $item->certificate->request?->request_no }}</a>@else{{ $item->certificate->request?->request_no }}@endcan</dd>
        <dt>Trung tâm</dt><dd>{{ $item->certificate->request?->distributionCenter?->name ?: '-' }}</dd>
        <dt>Đơn vị bán hàng</dt><dd>{{ $item->certificate->request?->salesUnit?->name ?: '-' }}</dd>
        <dt>Khách hàng</dt><dd>{{ $item->certificate->request?->customer?->customer_name ?: '-' }}</dd>
        <dt>Công trình</dt><dd>{{ $item->certificate->request?->customer?->project_name ?: '-' }}</dd>
        <dt>Địa điểm</dt><dd>{{ $item->certificate->request?->customer?->project_address ?: '-' }}</dd>
    </dl></section>
    @if($canEdit || $canPrint) @include('hard_copy_supplements.request_summary') @endif
</div>
<div class="col-lg-5">
    <div class="supplement-workspace {{ $canEdit || $canPrint ? 'supplement-section' : '' }}">
    @if($canEdit)
        <h2><i class="fas fa-edit text-primary mr-2"></i>{{ $item->status === 'RETURNED' ? 'Điều chỉnh và gửi lại DVKH' : 'Điều chỉnh yêu cầu' }}</h2>
        <form id="resubmitSupplementForm" method="POST" action="{{ route('hard-copy-supplements.action', [$item, 'resubmit']) }}" class="cncl-form" data-loading-lock>
            @csrf @include('hard_copy_supplements.request_fields')
        </form>
    @elseif($canPrint)
        <h2><i class="fas fa-print text-primary mr-2"></i>In bản ký tươi</h2>
        <div class="mb-3"><strong>{{ number_format($item->quantity) }} bản</strong> <span class="text-muted">đã được DVKH xác nhận</span></div>
        <form method="POST" action="{{ route('hard-copy-supplements.print', $item) }}" target="_blank" data-download>
            @csrf <div class="form-group"><label for="supplement-print-template">Mẫu in</label><select id="supplement-print-template" name="print_template" class="form-control">
            @foreach(['single' => 'In đơn', 'batch' => 'In bộ'] as $key => $label)
                <option value="{{ $key }}" @selected($item->print_template === $key)>{{ $label }}</option>
            @endforeach
            </select></div><button class="btn btn-outline-primary"><i class="fas fa-print"></i> Mở file in</button>
        </form>
        @if($item->print_template)<div class="small text-muted mt-3">Mẫu mở gần nhất: <strong>{{ $item->print_template === 'batch' ? 'In bộ' : 'In đơn' }}</strong> · {{ $item->page_count }} trang/mẫu.</div>@endif
        <hr><h2>Hoàn tất bản ký tươi</h2>
        <form id="completeSupplementForm" method="POST" action="{{ route('hard-copy-supplements.action', [$item, 'complete']) }}" data-loading-lock onsubmit="return confirm('Xác nhận đã hoàn tất toàn bộ bản ký tươi theo yêu cầu?')">
            @csrf <div class="form-group"><label for="supplement-completion-note">Ghi chú hoàn tất</label><textarea id="supplement-completion-note" name="completion_note" maxlength="2000" class="form-control" rows="3">{{ old('completion_note') }}</textarea></div>
        </form>
    @else
        @include('hard_copy_supplements.request_summary')
        <section class="supplement-section"><h2><i class="fas fa-user-check text-primary mr-2"></i>Thông tin xử lý</h2>
        @if(!$validCertificate && !in_array($item->status, ['COMPLETED', 'CANCELLED']))<p class="text-warning">Tạm dừng xử lý do phiếu không còn đủ điều kiện.</p>
        @elseif($canApprove)<p><strong>{{ number_format($item->quantity) }} bản</strong> đang chờ DVKH xác nhận.</p>
        @elseif($item->status === 'WAIT_PTN')<p>DVKH đã xác nhận. PTN đang phụ trách in và hoàn tất bản ký tươi.</p>
        @elseif($item->status === 'RETURNED')<p>Trung tâm đang phụ trách điều chỉnh và gửi lại yêu cầu.</p>
        @elseif($item->status === 'WAIT_DVKH')<p>DVKH đang phụ trách kiểm tra yêu cầu.</p>
        @endif
        <dl class="supplement-facts"><dt>DVKH duyệt</dt><dd>{{ $item->approver?->name ?: '-' }}<div class="small text-muted">{{ $item->approved_at?->format('d/m/Y H:i') }}</div></dd><dt>PTN hoàn tất</dt><dd>{{ $item->completer?->name ?: '-' }}<div class="small text-muted">{{ $item->completed_at?->format('d/m/Y H:i') }}</div></dd></dl>
        </section>
    @endif
    </div>
</div>
</div>

<section class="supplement-section"><div class="d-flex flex-wrap justify-content-between align-items-center mb-3"><h2 class="mb-0"><i class="fas fa-history text-primary mr-2"></i>Lịch sử xử lý <span class="badge badge-light border">{{ $item->events->count() }}</span></h2><span class="small text-muted">Mới nhất trước</span></div>
<div class="supplement-history table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Thao tác</th><th>Nội dung ghi nhận</th></tr></thead><tbody>
@forelse($item->events->reverse() as $event)<tr>
    <td class="supplement-date">{{ $event->created_at->format('d/m/Y H:i') }}</td><td>{{ $event->user?->name }}</td><td>{{ $eventLabels[$event->action] ?? $event->action }}</td>
    <td><strong>{{ $event->data['quantity'] ?? '' }} bản</strong>
        @if($event->action === 'printed')<span> · {{ ($event->data['print_template'] ?? '') === 'batch' ? 'In bộ' : 'In đơn' }} · {{ $event->data['page_count'] ?? '-' }} trang/mẫu</span>
        @else<div class="supplement-note">{{ $event->data['comment'] ?? $event->data['completion_note'] ?? $event->data['reason'] ?? '' }}</div>@endif
    </td>
</tr>@empty<tr><td colspan="4" class="text-center text-muted py-3">Chưa có lịch sử xử lý.</td></tr>@endforelse
</tbody></table></div></section>

<x-workflow_action_bar :number="$item->number" :status="$item->status_meta" :backUrl="route('hard-copy-supplements.index')">
    @if($canEdit)<button type="submit" form="resubmitSupplementForm" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Lưu và gửi DVKH</button>@endif
    @if($canPrint)<button type="submit" form="completeSupplementForm" class="btn btn-success"><i class="fas fa-check"></i> Xác nhận hoàn tất bản ký tươi</button>@endif
    @if($canApprove)<form method="POST" action="{{ route('hard-copy-supplements.action', [$item, 'approve']) }}" data-loading-lock onsubmit="return confirm('Xác nhận số bản bổ sung và chuyển PTN?')">@csrf<button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Xác nhận chuyển PTN</button></form>@endif
    @if($canReturn)<button type="button" class="btn btn-warning" data-toggle="modal" data-target="#supplementReturnModal"><i class="fas fa-undo"></i> Trả lại Trung tâm</button>@endif
    @if($canCancel)<details class="workflow-secondary-actions"><summary class="btn btn-outline-secondary"><i class="fas fa-ellipsis-h"></i> Thao tác khác</summary><div class="workflow-secondary-actions-body"><button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#supplementCancelModal"><i class="fas fa-ban"></i> Hủy yêu cầu</button></div></details>@endif
</x-workflow_action_bar>

@if($canReturn)
<div class="modal fade supplement-modal" id="supplementReturnModal" tabindex="-1" aria-labelledby="supplementReturnTitle" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('hard-copy-supplements.action', [$item, 'return']) }}" class="modal-content" data-loading-lock>
    @csrf <div class="modal-header"><h5 class="modal-title" id="supplementReturnTitle">Trả lại Trung tâm</h5><button type="button" class="close" data-dismiss="modal" aria-label="Đóng">&times;</button></div>
    <div class="modal-body"><p>{{ $item->number }} · {{ number_format($item->quantity) }} bản</p><label for="supplement-return-comment">Lý do trả lại <span class="text-danger">*</span></label><textarea id="supplement-return-comment" name="comment" required maxlength="2000" class="form-control">{{ old('comment') }}</textarea></div>
    <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-warning"><i class="fas fa-undo"></i> Trả lại Trung tâm</button></div>
</form></div></div>
@endif
@if($canCancel)
<div class="modal fade supplement-modal" id="supplementCancelModal" tabindex="-1" aria-labelledby="supplementCancelTitle" aria-hidden="true"><div class="modal-dialog"><form method="POST" action="{{ route('hard-copy-supplements.action', [$item, 'cancel']) }}" class="modal-content" data-loading-lock onsubmit="return confirm('Hủy yêu cầu bổ sung bản ký tươi này?')">
    @csrf <div class="modal-header"><h5 class="modal-title" id="supplementCancelTitle">Hủy {{ $item->number }}</h5><button type="button" class="close" data-dismiss="modal" aria-label="Đóng">&times;</button></div>
    <div class="modal-body"><label for="supplement-cancel-comment">Lý do hủy <span class="text-danger">*</span></label><textarea id="supplement-cancel-comment" name="comment" required maxlength="2000" class="form-control mb-3">{{ old('comment') }}</textarea>
        @if($printCount && $validCertificate)<div class="form-check"><input class="form-check-input" type="checkbox" required name="confirm_stop_printing" id="confirm_stop_printing" value="1"><label class="form-check-label" for="confirm_stop_printing">Tôi đã phối hợp với PTN dừng xử lý bản giấy và xác nhận hủy yêu cầu đã mở file in.</label></div>@endif
    </div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button><button type="submit" class="btn btn-danger"><i class="fas fa-ban"></i> Hủy yêu cầu</button></div>
</form></div></div>
@endif
</div>
@stop
