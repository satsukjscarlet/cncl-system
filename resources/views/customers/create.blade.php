@extends('adminlte::page')

@section('title', 'Thêm khách hàng - công trình')

@section('content_header')
    <div>
        <h1 class="m-0">Thêm khách hàng - công trình</h1>
        <small class="text-muted">Khai báo thông tin khách hàng và công trình phục vụ cấp phiếu CNCL</small>
    </div>
@stop

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus-circle"></i> Thông tin khách hàng - công trình
        </h3>
    </div>

    <form method="POST" action="{{ route('customers.store') }}">
        <div class="card-body">
            @include('customers._form')
        </div>
    </form>
</div>
@stop