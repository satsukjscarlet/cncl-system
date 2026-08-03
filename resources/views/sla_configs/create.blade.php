@extends('adminlte::page')

@section('title', 'Thêm SLA')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
<h1>Thêm cấu hình SLA</h1>
@stop

@section('content')

<div class="card card-primary card-outline">

    <form method="POST"
          class="cncl-form"
          action="{{ route('sla-configs.store') }}">

        @csrf

        <div class="card-body">

            @include('sla_configs._form')

        </div>

        <div class="card-footer">

            <button class="btn btn-primary">
                <i class="fas fa-save"></i>
                Lưu
            </button>

            <a href="{{ route('sla-configs.index') }}"
               class="btn btn-secondary">
                Quay lại
            </a>

        </div>

    </form>

</div>

@stop
