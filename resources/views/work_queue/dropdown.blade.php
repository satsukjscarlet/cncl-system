@forelse($items as $item)
    <a href="{{ $item['url'] }}" class="dropdown-item" data-loading-message="Đang mở danh sách việc cần làm...">
        <div class="d-flex align-items-center">
            <span class="badge badge-{{ $item['color'] }} mr-2" style="min-width:34px">
                {{ $item['count'] }}
            </span>
            <i class="{{ $item['icon'] }} text-{{ $item['color'] }} mr-2"></i>
            <span class="text-sm text-truncate" title="{{ $item['label'] }}">{{ $item['label'] }}</span>
        </div>
    </a>
    <div class="dropdown-divider m-0"></div>
@empty
    <div class="dropdown-item text-center text-muted py-3">
        <i class="fas fa-check-circle d-block mb-1"></i>
        Không có công việc đang chờ.
    </div>
@endforelse
