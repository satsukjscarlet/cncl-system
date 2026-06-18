@extends('adminlte::page')

@section('title', 'Cập nhật sản phẩm')

@section('content_header')
    <h1 class="m-0">Cập nhật sản phẩm</h1>
    <small class="text-muted">{{ $product->product_code }} - {{ $product->product_name }}</small>
@stop

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-edit"></i> Thông tin sản phẩm</h3>
    </div>

    <form method="POST" action="{{ route('products.update', $product) }}">
        @method('PUT')
        <div class="card-body">
            @include('products._form')
        </div>
    </form>
</div>
@stop