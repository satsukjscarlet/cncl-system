@php
    $supplementService = app(\App\Services\HardCopySupplementService::class);
    $supplements = $certificate->hardCopySupplements()->latest('id')->limit(8)->get();
    $openSupplement = $certificate->hardCopySupplements()->whereNotNull('active_certificate_id')->first();
@endphp
@if($supplements->isNotEmpty() || $certificate->signed_at)
<details id="hard-copy-supplement-panel" class="mb-3 border p-3" @if($errors->any()) open @endif><summary class="font-weight-bold">Bổ sung bản ký tươi @if($openSupplement) — {{ $openSupplement->status_label }} @endif</summary>
    <div class="mt-3">
    @foreach($supplements as $supplement)<p><a href="{{ route('hard-copy-supplements.show', $supplement) }}">{{ $supplement->number }}</a> — {{ $supplement->quantity }} bản — {{ $supplement->status_label }}</p>@endforeach
    @if($openSupplement)<a class="btn btn-outline-primary" href="{{ route('hard-copy-supplements.show', $openSupplement) }}"><i class="fas fa-external-link-alt"></i> Xem yêu cầu đang xử lý</a>
    @elseif($supplementService->center(auth()->user(), $certificate) && $certificate->allowsHardCopySupplement())
    <form method="POST" action="{{ route('hard-copy-supplements.store', $certificate) }}" class="cncl-form" data-loading-lock>
        @csrf @include('hard_copy_supplements.request_fields')
        <button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi yêu cầu bổ sung đến DVKH</button>
    </form>
    @elseif(!$supplementService->center(auth()->user(), $certificate))
    <p class="text-muted">{{ $supplements->isEmpty() ? 'Chưa có yêu cầu bổ sung. ' : '' }}Chỉ tài khoản Trung tâm sở hữu phiếu có quyền tạo yêu cầu mới. DVKH xác nhận, PTN in và hoàn tất.</p>
    @else
    <p class="text-warning">Phiếu chưa đủ điều kiện bổ sung hoặc đang hủy/thu hồi, thay thế hay xin cấp lại.</p>
    @endif
    <a href="{{ route('hard-copy-supplements.index') }}" class="btn btn-link">Danh sách yêu cầu bổ sung</a>
    </div>
</details>
@endif
