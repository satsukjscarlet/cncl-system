@extends('adminlte::page')

@section('title', 'Cập nhật khách hàng - công trình')

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật khách hàng - công trình</h1>
        <small class="text-muted">{{ $customer->customer_name }}</small>
    </div>
@stop

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit"></i> Thông tin khách hàng - công trình
        </h3>
    </div>

    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @method('PUT')

        <div class="card-body">
            @include('customers._form')
        </div>
    </form>
</div>
@stop