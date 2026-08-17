@forelse($notifications as $notification)
    <a href="{{ route('notifications.open', $notification) }}"
       class="dropdown-item {{ $notification->read_at ? '' : 'bg-light' }}"
       data-loading-message="Đang mở thông báo...">
        <div class="d-flex align-items-start">
            <i class="fas {{ $notification->read_at ? 'fa-bell text-muted' : 'fa-bell text-warning' }} mr-2 mt-1"></i>
            <div class="flex-grow-1" style="min-width:0">
                <div class="text-sm font-weight-bold text-truncate" title="{{ $notification->title }}">
                    {{ $notification->title }}
                </div>
                <div class="text-xs text-muted text-truncate" title="{{ $notification->message }}">
                    {{ $notification->message }}
                </div>
                <div class="text-xs text-muted">{{ $notification->created_at->diffForHumans() }}</div>
            </div>
        </div>
    </a>
    <div class="dropdown-divider m-0"></div>
@empty
    <div class="dropdown-item text-center text-muted py-3">
        <i class="fas fa-bell-slash d-block mb-1"></i>
        Chưa có thông báo.
    </div>
@endforelse

@if($unreadCount > 0)
    <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="px-2 py-2">
        @csrf
        <button class="btn btn-xs btn-outline-primary btn-block">
            <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
        </button>
    </form>
@endif
