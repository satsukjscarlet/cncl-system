@if($urgentRequest->is_urgent)
    <span class="badge badge-danger" title="{{ $urgentRequest->urgentReason->name ?? 'Yêu cầu gấp' }}">
        <i class="fas fa-bolt"></i> Gấp
    </span>
    @if($urgentRequest->urgentReason)
        <div class="text-danger small mt-1">{{ $urgentRequest->urgentReason->name }}</div>
    @endif
@endif
