@extends('adminlte::page')

@section('title', 'Cập nhật đơn vị tính')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật đơn vị tính</h1>
        <small class="text-muted">{{ $unit->code }} - {{ $unit->name }}</small>
    </div>
@stop

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit"></i> Thông tin đơn vị tính</h3>
    </div>

    <form method="POST" action="{{ route('product-units.update', $unit) }}" class="cncl-form">
        @method('PUT')
        <div class="card-body">
            @include('product_units._form')
        </div>
    </form>
</div>
@stop
