<section class="supplement-section"><h2><i class="fas fa-copy text-primary mr-2"></i>Nội dung bổ sung</h2><dl class="supplement-facts">
    <dt>Số bản bổ sung</dt><dd><strong>{{ number_format($item->quantity) }} bản</strong></dd>
    <dt>Người gửi</dt><dd>{{ $item->requester?->name }}</dd>
    <dt>Lý do</dt><dd class="supplement-note">{{ $item->reason }}</dd>
    <dt>Nhận bản giấy</dt><dd class="supplement-note">{{ $item->note ?: '-' }}</dd>
    <dt>Mẫu mở gần nhất</dt><dd>{{ $item->print_template ? ($item->print_template === 'batch' ? 'In bộ' : 'In đơn') : 'Chưa chọn mẫu' }}</dd>
    <dt>Số trang/mẫu</dt><dd>{{ $item->page_count ?: '-' }} @if($item->page_count)<span class="text-muted">· {{ number_format($item->quantity * $item->page_count) }} tờ dự kiến</span>@endif</dd>
    <dt>Lượt mở file</dt><dd>{{ $printCount }}</dd>
    @if($item->completion_note)<dt>Ghi chú hoàn tất</dt><dd class="supplement-note">{{ $item->completion_note }}</dd>@endif
</dl><p class="small text-muted mb-0">Số tờ dự kiến tính theo mẫu mở gần nhất, không cộng hai mẫu. Lượt mở file không phải số bản thực tế đã in.</p></section>
