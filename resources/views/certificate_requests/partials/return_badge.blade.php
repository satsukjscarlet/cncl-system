@php
    $returnTarget = method_exists($certificateRequest, 'effectiveLastReturnedTo')
        ? $certificateRequest->effectiveLastReturnedTo()
        : ($certificateRequest->last_returned_to ?? null);
    $returnSource = method_exists($certificateRequest, 'effectiveLastReturnedFrom')
        ? $certificateRequest->effectiveLastReturnedFrom()
        : ($certificateRequest->last_returned_from ?? null);

    $returnLabel = match ($returnTarget) {
        'TRUNG_TAM' => 'DVKH trả lại',
        'DVKH' => $returnSource === 'TRUONG_PTN' ? 'Trưởng PTN trả lại DVKH' : 'PTN trả lại DVKH',
        'PTN' => 'Trưởng PTN trả lại PTN',
        default => null,
    };
@endphp

@if($returnLabel)
    <div class="mt-1">
        <span class="badge badge-warning">
            <i class="fas fa-undo"></i> {{ $returnLabel }}
        </span>
    </div>
    @if($certificateRequest->last_return_reason)
        <div class="text-muted small mt-1" style="overflow-wrap:anywhere">
            {{ \Illuminate\Support\Str::limit($certificateRequest->last_return_reason, 90) }}
        </div>
    @endif
@endif
