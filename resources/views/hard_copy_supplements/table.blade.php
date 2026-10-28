<div class="table-responsive"><table class="table table-bordered table-hover supplement-list-table mb-0">
    <thead><tr>
    @foreach(['id' => 'Yêu cầu bổ sung', 'certificate_no' => 'Phiếu CNCL / Khách hàng', 'center' => 'Trung tâm / Đơn vị bán hàng', 'quantity' => 'Số bản', 'status' => 'Trạng thái / Mẫu in', 'submitted_at' => 'Ngày gửi', 'approved_at' => 'DVKH duyệt', 'completed_at' => 'PTN hoàn tất'] as $key => $label)
        <th>@if(($sortable ?? false) && $key !== 'center')@include('partials.sort_link', ['column' => $key, 'label' => $label, 'defaultSort' => 'id'])@else{{ $label }}@endif</th>
    @endforeach
    @if($sortable ?? false)<th class="text-center supplement-actions">Thao tác</th>@endif
    </tr></thead>
    <tbody>@forelse($items as $item)<tr>
        <td><a class="font-weight-bold" href="{{ route('hard-copy-supplements.show', $item) }}" title="Mở chi tiết yêu cầu">{{ $item->number }} <i class="fas fa-angle-right"></i></a><div class="small text-muted mt-1">{{ $item->requester?->name }}</div></td>
        <td class="supplement-customer">
            @can('certificate.view')<a href="{{ route('quality-certificates.show', $item->quality_certificate_id) }}">{{ $item->certificate?->certificate_no }}</a>@else{{ $item->certificate?->certificate_no }}@endcan
            <div class="mt-1">{{ $item->certificate?->request?->customer?->customer_name ?: '-' }}</div>
            <div class="small text-muted">{{ $item->certificate?->request?->customer?->project_name }}</div>
        </td>
        <td class="supplement-document">{{ $item->certificate?->request?->distributionCenter?->name }}<div class="small text-muted mt-1">{{ $item->certificate?->request?->salesUnit?->name ?: 'Chưa có đơn vị bán hàng' }}</div></td>
        <td class="text-right"><span class="supplement-quantity">{{ number_format($item->quantity) }}</span><div class="small text-muted">bản</div></td>
        <td class="supplement-state"><span class="badge {{ $item->status_meta['class'] }}">{{ $item->status_label }}</span>
            @unless($item->certificate_is_eligible)<div class="text-danger small mt-1"><i class="fas fa-exclamation-triangle"></i> Phiếu tạm dừng / không đủ điều kiện</div>@endunless
            <div class="small text-muted mt-2">{{ $item->print_template ? ($item->print_template === 'batch' ? 'In bộ' : 'In đơn') : 'Chưa chọn mẫu in' }}{{ $item->page_count ? ' · ' . $item->page_count . ' trang' : '' }}</div>
        </td>
        <td class="supplement-date">{{ $item->submitted_at?->format('d/m/Y') }}<div class="text-muted">{{ $item->submitted_at?->format('H:i') }}</div></td>
        <td class="supplement-date">{{ $item->approved_at?->format('d/m/Y') ?: '-' }}<div class="text-muted">{{ $item->approved_at?->format('H:i') }}</div></td>
        <td class="supplement-date">{{ $item->completed_at?->format('d/m/Y') ?: '-' }}<div class="text-muted">{{ $item->completed_at?->format('H:i') }}</div></td>
        @if($sortable ?? false)<td class="text-center supplement-actions"><a href="{{ route('hard-copy-supplements.show', $item) }}" class="btn btn-sm btn-outline-primary" title="Xem {{ $item->number }}" aria-label="Xem chi tiết {{ $item->number }}"><i class="fas fa-eye" aria-hidden="true"></i></a></td>@endif
    </tr>@empty<tr><td colspan="{{ ($sortable ?? false) ? 9 : 8 }}" class="text-center text-muted py-4"><i class="fas fa-inbox mr-2"></i>Không có yêu cầu phù hợp.</td></tr>@endforelse</tbody>
</table></div>
<div class="d-flex flex-wrap justify-content-between align-items-center mt-3" style="gap:12px">
    <span class="small text-muted">{{ $items->total() ? $items->firstItem() . '–' . $items->lastItem() : '0' }} / {{ number_format($items->total()) }} yêu cầu</span>{{ $items->links() }}
</div>
