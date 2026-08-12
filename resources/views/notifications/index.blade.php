@extends('adminlte::page')

@section('title', 'Thông báo')

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Thông báo</h1>
        <small class="text-muted">Theo dõi các thao tác liên quan đến yêu cầu và phiếu của tài khoản này</small>
    </div>

    <form method="POST" action="{{ route('notifications.mark-all-read') }}" class="mt-2 mt-md-0">
        @csrf
        <button class="btn btn-outline-primary">
            <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
        </button>
    </form>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card card-primary card-outline">
    <div class="card-header">
        <form method="GET" class="form-inline">
            <label class="mr-2">Trạng thái</label>
            <select name="status" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">Tất cả</option>
                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Chưa đọc</option>
                <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Đã đọc</option>
            </select>
            <a href="{{ route('notifications.index') }}" class="btn btn-secondary">
                <i class="fas fa-sync"></i>
            </a>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                <div class="list-group-item {{ $notification->read_at ? '' : 'bg-light' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pr-3">
                            <a href="{{ route('notifications.open', $notification) }}" class="font-weight-bold">
                                @unless($notification->read_at)
                                    <span class="badge badge-danger mr-1">Mới</span>
                                @endunless
                                {{ $notification->title }}
                            </a>
                            <div class="text-muted mt-1">{{ $notification->message }}</div>
                            <div class="text-muted small mt-1">
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>

                        @unless($notification->read_at)
                            <form method="POST" action="{{ route('notifications.mark-read', $notification) }}">
                                @csrf
                                <button class="btn btn-xs btn-outline-primary">
                                    Đã đọc
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">
                    <i class="fas fa-bell-slash fa-2x mb-2"></i>
                    <div>Chưa có thông báo.</div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="card-footer">
        {{ $notifications->links() }}
    </div>
</div>
@stop
