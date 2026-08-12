@php
    if (isset($certificateRequest) && $certificateRequest) {
        $item = $certificateRequest->displayStatusMeta();
    } elseif (isset($qualityCertificate) && $qualityCertificate) {
        $item = $qualityCertificate->displayStatusMeta();
    } else {
        $item = \App\Models\CertificateRequest::statusMeta($status ?? null);
    }
@endphp

<span class="badge {{ $item['class'] }}">
    @if(!empty($item['icon']))
        <i class="{{ $item['icon'] }}"></i>
    @endif
    {{ $item['text'] }}
</span>
