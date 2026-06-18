@php
    $map = [
        'DRAFT' => ['class' => 'badge-secondary', 'text' => 'Nháp'],
        'WAIT_DVKH' => ['class' => 'badge-warning', 'text' => 'Chờ DVKH'],
        'WAIT_PTN' => ['class' => 'badge-info', 'text' => 'Chờ PTN'],
        'PTN_PROCESSING' => ['class' => 'badge-primary', 'text' => 'PTN xử lý'],
        'SIGNED' => ['class' => 'badge-success', 'text' => 'Đã ký số'],
        'COMPLETED' => ['class' => 'badge-success', 'text' => 'Hoàn tất'],
        'CANCELLED' => ['class' => 'badge-danger', 'text' => 'Hủy/Trả lại'],
    ];

    $item = $map[$status] ?? ['class' => 'badge-light', 'text' => $status];
@endphp

<span class="badge {{ $item['class'] }}">
    {{ $item['text'] }}
</span>