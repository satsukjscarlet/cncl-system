@extends('adminlte::page')

@section('title', 'PTN lập phiếu trực tiếp')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">PTN lập phiếu trực tiếp</h1>
        <small class="text-muted">Phòng thử nghiệm chủ động lập phiếu CNCL, không cần yêu cầu từ Trung tâm và không qua bước DVKH.</small>
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
            <i class="fas fa-vials"></i> Thông tin phiếu lập trực tiếp
        </h3>
    </div>

    <form method="POST" action="{{ route('ptn.requests.direct-store') }}" class="cncl-form certificate-request-form">
        <div class="card-body">
            @php
                $formBackUrl = route('ptn.requests.index');
                $formSubmitText = 'Lưu và lập phiếu';
                $formSubmitIcon = 'fas fa-file-signature';
            @endphp

            @include('certificate_requests._form')
        </div>
    </form>
</div>
@stop
