@php($statRows = collect($rows))
<div class="table-responsive"><table class="table table-bordered table-hover mb-0">
    <thead class="thead-light"><tr><th>{{ $groupLabel }}</th><th class="text-right">Yêu cầu cần ký tươi</th><th class="text-right">Tổng số bản in</th><th class="text-right">Trang In đơn</th><th class="text-right">Tờ In đơn dự kiến</th><th class="text-right">Trang In bộ</th><th class="text-right">Tờ In bộ dự kiến</th></tr></thead>
    <tbody>
    @forelse($statRows as $row)
    <tr><td>
        @if($groupType === 'month'){{ $row['month_label'] }}
        @elseif($groupType === 'sales')<strong>{{ $row['sales_unit']?->code ?: 'Chưa gắn' }}</strong> - {{ $row['sales_unit']?->name ?: 'Chưa gắn đơn vị bán hàng' }}<div class="small text-muted">{{ $row['center']?->name }}</div>
        @else<strong>{{ $row['center']?->code }}</strong> - {{ $row['center']?->name }}@endif
        </td>
        @foreach(['request_count', 'total_copy_count', 'single_page_count', 'single_sheet_count', 'batch_page_count', 'batch_sheet_count'] as $key)
        <td class="hard-copy-number">{{ number_format($row[$key]) }}</td>
        @endforeach
    </tr>
    @empty<tr><td colspan="7" class="text-center text-muted py-4">Không có dữ liệu phù hợp.</td></tr>@endforelse
    </tbody>
    @if($statRows->isNotEmpty())<tfoot><tr><th>Tổng</th>
        @foreach(['request_count', 'total_copy_count', 'single_page_count', 'single_sheet_count', 'batch_page_count', 'batch_sheet_count'] as $key)<th class="hard-copy-number">{{ number_format(collect($totalRows ?? $rows)->sum($key)) }}</th>@endforeach
    </tr></tfoot>@endif
</table></div>
