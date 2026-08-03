@extends('adminlte::page')

@section('title', 'Thêm lý do yêu cầu gấp')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Thêm lý do yêu cầu gấp</h1>
        <small class="text-muted">Khai báo lý do dùng khi trung tâm mở yêu cầu cung cấp gấp</small>
    </div>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle"></i> Thông tin lý do
        </h3>
    </div>

    <form method="POST" action="{{ route('urgent-reasons.store') }}" class="cncl-form">
        <div class="card-body">
            @include('urgent_reasons._form')
        </div>
    </form>
</div>
@stop
