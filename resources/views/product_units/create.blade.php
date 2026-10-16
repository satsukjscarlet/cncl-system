@extends('adminlte::page')

@section('title', 'Thêm đơn vị tính')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
    <div>
        <h1 class="m-0">Thêm đơn vị tính</h1>
        <small class="text-muted">Khai báo đơn vị tính dùng khi tạo sản phẩm</small>
    </div>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Thông tin đơn vị tính</h3>
    </div>

    <form method="POST" action="{{ route('product-units.store') }}" class="cncl-form">
        <div class="card-body">
            @include('product_units._form')
        </div>
    </form>
</div>
@stop
