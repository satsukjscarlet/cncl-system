@extends('adminlte::page')

@section('title', 'Cập nhật yêu cầu cấp phiếu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật yêu cầu cấp phiếu</h1>
        <small class="text-muted">{{ $certificateRequest->request_no }}</small>
    </div>
@stop

@section('content')
@if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit"></i> Thông tin yêu cầu
        </h3>
    </div>

    <form method="POST" action="{{ route('certificate-requests.update', $certificateRequest) }}" class="cncl-form certificate-request-form">
        @method('PUT')

        <div class="card-body">
            @include('certificate_requests._form')
        </div>
    </form>
</div>
@stop
