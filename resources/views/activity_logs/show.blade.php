@extends('adminlte::page')

@section('title', 'Chi tiết nhật ký')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Chi tiết nhật ký hệ thống</h1>
        <small class="text-muted">Log ID: {{ $activityLog->id }}</small>
    </div>

    <a href="{{ route('activity-logs.index') }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>
@stop

@section('content')

<div class="row">
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Thông tin chung
                </h3>
            </div>

            <div class="card-body">
                <p><strong>ID:</strong> {{ $activityLog->id }}</p>
                <p><strong>Module:</strong> {{ $activityLog->log_name ?: 'default' }}</p>
                <p><strong>Diễn giải:</strong> {{ $activityLog->description }}</p>
                <p><strong>Thời gian:</strong> {{ optional($activityLog->created_at)->format('d/m/Y H:i:s') }}</p>

                <hr>

                <p>
                    <strong>Người thao tác:</strong>
                    @if($activityLog->causer)
                        {{ $activityLog->causer->name ?? '—' }}
                        <br>
                        <span class="text-muted">
                            {{ $activityLog->causer->username ?? '' }}
                        </span>
                    @else
                        Hệ thống
                    @endif
                </p>

                <p>
                    <strong>Đối tượng:</strong>
                    @if($activityLog->subject_type)
                        {{ class_basename($activityLog->subject_type) }} #{{ $activityLog->subject_id }}
                    @else
                        —
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-code"></i> Dữ liệu chi tiết
                </h3>
            </div>

            <div class="card-body">
                @if($activityLog->properties && $activityLog->properties->count())
                    <pre style="white-space: pre-wrap; background:#f8f9fa; padding:15px; border:1px solid #ddd; border-radius:4px;">{{ json_encode($activityLog->properties->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @else
                    <div class="text-muted">
                        Nhật ký này không có dữ liệu chi tiết.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@stop