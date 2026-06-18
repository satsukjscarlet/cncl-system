@extends('adminlte::page')

@section('title', 'Cập nhật nhóm sản phẩm')

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật nhóm sản phẩm</h1>
        <small class="text-muted">{{ $group->code }} - {{ $group->name }}</small>
    </div>
@stop

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit"></i> Thông tin nhóm sản phẩm
        </h3>
    </div>

    <form method="POST" action="{{ route('product-groups.update', $group) }}">
        @method('PUT')

        <div class="card-body">
            @include('product_groups._form')
        </div>
    </form>
</div>
@stop