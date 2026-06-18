@extends('adminlte::page')

@section('title', 'Cấu hình hệ thống')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div>
    <h1 class="m-0">Cấu hình hệ thống</h1>
    <small class="text-muted">Thiết lập các tham số vận hành chung của hệ thống CNCL</small>
</div>
@stop

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cogs"></i> Cấu hình email
        </h3>
    </div>

    <form method="POST" action="{{ route('system-settings.update') }}">
        @csrf

        <div class="card-body">
            <div class="form-group">
                <div class="custom-control custom-switch">
                    <input type="checkbox"
                           name="auto_send_email_after_sign"
                           value="1"
                           class="custom-control-input"
                           id="auto_send_email_after_sign"
                           {{ $autoSendEmail ? 'checked' : '' }}>

                    <label class="custom-control-label" for="auto_send_email_after_sign">
                        Tự động gửi email sau khi ký số/phát hành phiếu CNCL
                    </label>
                </div>

                <small class="text-muted d-block mt-2">
                    Nếu bật, hệ thống tự động gửi Phiếu CNCL PDF tới email khách hàng sau khi PTN ký số/phát hành.
                    Nếu tắt, phiếu vẫn được ký/phát hành nhưng không gửi email tự động; người dùng có thể bấm “Gửi lại email” thủ công.
                </small>
            </div>
        </div>

        <div class="card-footer">
            <button class="btn btn-primary">
                <i class="fas fa-save"></i> Lưu cấu hình
            </button>
        </div>
    </form>
</div>

@stop