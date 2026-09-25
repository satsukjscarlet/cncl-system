<div class="topbar d-flex justify-content-between align-items-center">
    <div>
        <strong>{{ $title ?? 'Dashboard' }}</strong>
    </div>

    <div class="d-flex align-items-center" style="gap: 12px;">
        <span>
            {{ auth()->user()->name ?? '' }}
            @if(auth()->check())
                <small class="text-muted">
                    ({{ auth()->user()->getRoleNames()->first() }})
                </small>
            @endif
        </span>

        <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-user-circle"></i> Tài khoản của tôi
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mb-0">
            @csrf
            <button class="btn btn-sm btn-outline-danger">
                Đăng xuất
            </button>
        </form>
    </div>
</div>
