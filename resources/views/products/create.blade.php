@extends('adminlte::page')

@section('title', 'Thêm sản phẩm')

@section('content_header')
    <h1 class="m-0">Thêm sản phẩm</h1>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-plus-circle"></i> Thông tin sản phẩm</h3>
    </div>

    <form method="POST" action="{{ route('products.store') }}">
        <div class="card-body">
            @include('products._form')
        </div>
    </form>
</div>
@stop