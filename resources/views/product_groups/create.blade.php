@extends('adminlte::page')

@section('title', 'Thêm nhóm sản phẩm')

@section('content_header')
    <div>
        <h1 class="m-0">Thêm nhóm sản phẩm</h1>
        <small class="text-muted">Khai báo nhóm sản phẩm mới</small>
    </div>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle"></i> Thông tin nhóm sản phẩm
        </h3>
    </div>

    <form method="POST" action="{{ route('product-groups.store') }}">
        <div class="card-body">
            @include('product_groups._form')
        </div>
    </form>
</div>
@stop