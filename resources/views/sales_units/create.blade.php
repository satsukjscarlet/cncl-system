@extends('adminlte::page')

@section('title', 'Thêm đơn vị bán hàng')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
    <h1>Thêm đơn vị bán hàng</h1>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-store"></i> Thông tin đơn vị bán hàng</h3>
    </div>
    <form method="POST" action="{{ route('sales-units.store') }}" class="cncl-form">
        <div class="card-body">
            @include('sales_units._form')
        </div>
    </form>
</div>
@stop
