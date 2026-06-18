<div class="topbar d-flex justify-content-between align-items-center">
    <div>
        <strong>{{ $title ?? 'Dashboard' }}</strong>
    </div>

    <div class="d-flex align-items-center gap-3">
        <span>
            {{ auth()->user()->name ?? '' }}
            @if(auth()->check())
                <small class="text-muted">
                    ({{ auth()->user()->getRoleNames()->first() }})
                </small>
            @endif
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-sm btn-outline-danger">
                Đăng xuất
            </button>
        </form>
    </div>
</div>