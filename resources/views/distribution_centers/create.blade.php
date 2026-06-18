@extends('adminlte::page')

@section('title', 'Thêm trung tâm phân phối')

@section('content_header')
    <div>
        <h1 class="m-0">Thêm trung tâm phân phối</h1>
        <small class="text-muted">Khai báo trung tâm phát sinh yêu cầu cấp phiếu CNCL</small>
    </div>
@stop

@section('content')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-plus-circle"></i> Thông tin trung tâm
            </h3>
        </div>

        <form method="POST" action="{{ route('distribution-centers.store') }}">
            <div class="card-body">
                @include('distribution_centers._form')
            </div>
        </form>
    </div>
@stop
