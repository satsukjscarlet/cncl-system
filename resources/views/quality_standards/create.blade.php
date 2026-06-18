@extends('adminlte::page')

@section('title', 'Thêm tiêu chuẩn chất lượng')

@section('content_header')
    <div>
        <h1 class="m-0">Thêm tiêu chuẩn chất lượng</h1>
        <small class="text-muted">Khai báo tiêu chuẩn chất lượng dùng cho danh mục sản phẩm</small>
    </div>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle"></i> Thông tin tiêu chuẩn
        </h3>
    </div>

    <form method="POST" action="{{ route('quality-standards.store') }}">
        <div class="card-body">
            @include('quality_standards._form')
        </div>
    </form>
</div>
@stop