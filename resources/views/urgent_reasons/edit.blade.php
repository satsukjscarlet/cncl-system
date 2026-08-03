@extends('adminlte::page')

@section('title', 'Cập nhật lý do yêu cầu gấp')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật lý do yêu cầu gấp</h1>
        <small class="text-muted">{{ $urgentReason->code }} - {{ $urgentReason->name }}</small>
    </div>
@stop

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit"></i> Thông tin lý do
        </h3>
    </div>

    <form method="POST" action="{{ route('urgent-reasons.update', $urgentReason) }}" class="cncl-form">
        @method('PUT')

        <div class="card-body">
            @include('urgent_reasons._form')
        </div>
    </form>
</div>
@stop
