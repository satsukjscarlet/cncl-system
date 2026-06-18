@extends('adminlte::page')

@section('title', 'Cập nhật tiêu chuẩn chất lượng')

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật tiêu chuẩn chất lượng</h1>
        <small class="text-muted">{{ $standard->code }} - {{ $standard->name }}</small>
    </div>
@stop

@section('content')
<div class="card card-warning card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-edit"></i> Thông tin tiêu chuẩn
        </h3>
    </div>

    <form method="POST" action="{{ route('quality-standards.update', $standard) }}">
        @method('PUT')

        <div class="card-body">
            @include('quality_standards._form')
        </div>
    </form>
</div>
@stop