@extends('adminlte::page')

@section('title', 'Thêm người dùng')

@section('content_header')
<div>
    <h1 class="m-0">Thêm người dùng</h1>
    <small class="text-muted">Tạo tài khoản và gán vai trò sử dụng hệ thống</small>
</div>
@stop

@section('content')
@if(session('error'))
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user-plus"></i> Thông tin tài khoản
        </h3>
    </div>

    <form method="POST" action="{{ route('users.store') }}">
        <div class="card-body">
            @include('users._form')
        </div>
    </form>
</div>
@stop
