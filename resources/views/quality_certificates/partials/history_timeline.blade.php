<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-stream"></i> Lịch sử xử lý phiếu</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tối đa {{ $logs->count() }} dòng gần nhất</span>
        </div>
    </div>

    <div class="card-body">
        @if($logs->isEmpty())
            <div class="text-center text-muted py-3">
                <i class="fas fa-history fa-2x mb-2"></i>
                <br>
                Chưa tìm thấy lịch sử thao tác liên quan đến phiếu này.
            </div>
        @else
            <div class="timeline mb-0">
                @foreach($logs as $log)
                    @php
                        $action = $log->properties['action'] ?? null;
                        $iconClass = match ($action) {
                            'smartca_request', 'smartca_request_resend' => 'fas fa-file-signature bg-primary',
                            'smartca_signed_and_send_email', 'smartca_signed_without_email' => 'fas fa-check bg-success',
                            'smartca_request_expired', 'smartca_status_failed', 'send_email_failed' => 'fas fa-exclamation-triangle bg-danger',
                            'request_reissue' => 'fas fa-redo bg-warning',
                            'approve' => 'fas fa-check-circle bg-success',
                            'reject', 'reject_signature' => 'fas fa-undo bg-secondary',
                            default => 'fas fa-circle bg-info',
                        };
                    @endphp

                    <div>
                        <i class="{{ $iconClass }}"></i>
                        <div class="timeline-item">
                            <span class="time">
                                <i class="far fa-clock"></i>
                                {{ optional($log->created_at)->format('d/m/Y H:i:s') }}
                            </span>

                            <h3 class="timeline-header">
                                <span class="badge badge-light">{{ $log->log_name ?: 'Hệ thống' }}</span>
                                @if($action)
                                    <span class="badge badge-secondary">{{ $action }}</span>
                                @endif
                            </h3>

                            <div class="timeline-body">
                                <div class="font-weight-bold">{{ $log->description }}</div>
                                <div class="text-muted small mt-1">
                                    Người thao tác:
                                    @if($log->causer)
                                        {{ $log->causer->name ?? '-' }}
                                        @if($log->causer->username)
                                            ({{ $log->causer->username }})
                                        @endif
                                    @else
                                        Hệ thống
                                    @endif
                                </div>
                            </div>

                            @can('log.view')
                                <div class="timeline-footer">
                                    <a href="{{ route('activity-logs.show', $log) }}" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-search"></i> Xem log chi tiết
                                    </a>
                                </div>
                            @endcan
                        </div>
                    </div>
                @endforeach

                <div>
                    <i class="far fa-clock bg-gray"></i>
                </div>
            </div>
        @endif
    </div>
</div>
