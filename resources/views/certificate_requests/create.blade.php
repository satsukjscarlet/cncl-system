@extends('adminlte::page')

@section('title', 'Tạo yêu cầu cấp phiếu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260818-1') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Tạo yêu cầu cấp phiếu CNCL</h1>
        <small class="text-muted">Trung tâm phân phối tạo đề nghị cấp Phiếu Chứng nhận Chất lượng</small>
    </div>
@stop

@section('content')
@if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle"></i> Thông tin yêu cầu
        </h3>
    </div>

    <form method="POST" action="{{ route('certificate-requests.store') }}" class="cncl-form certificate-request-form">
        <div class="card-body">
            @include('certificate_requests._form')
        </div>
    </form>
</div>
@stop
