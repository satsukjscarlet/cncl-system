@extends('adminlte::page')

@section('title', 'Cập nhật SLA')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260725-1') }}">
@stop

@section('content_header')
<h1>Cập nhật cấu hình SLA</h1>
@stop

@section('content')

<div class="card card-primary card-outline">

    <form method="POST"
          class="cncl-form"
          action="{{ route('sla-configs.update',$slaConfig) }}">

        @csrf
        @method('PUT')

        <div class="card-body">

            @include('sla_configs._form')

        </div>

        <div class="card-footer">

            <button class="btn btn-primary">
                <i class="fas fa-save"></i>
                Cập nhật
            </button>

            <a href="{{ route('sla-configs.index') }}"
               class="btn btn-secondary">
                Quay lại
            </a>

        </div>

    </form>

</div>

@stop
