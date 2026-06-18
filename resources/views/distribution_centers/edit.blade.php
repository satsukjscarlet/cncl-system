@extends('adminlte::page')

@section('title', 'Cập nhật trung tâm phân phối')

@section('content_header')
    <div>
        <h1 class="m-0">Cập nhật trung tâm phân phối</h1>
        <small class="text-muted">{{ $center->code }} - {{ $center->name }}</small>
    </div>
@stop

@section('content')
    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-edit"></i> Thông tin trung tâm
            </h3>
        </div>

        <form method="POST" action="{{ route('distribution-centers.update', $center) }}">
            @method('PUT')

            <div class="card-body">
                @include('distribution_centers._form')
            </div>
        </form>
    </div>
@stop
