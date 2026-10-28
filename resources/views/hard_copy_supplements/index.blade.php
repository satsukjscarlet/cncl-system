@extends('adminlte::page')
@section('title', 'Bổ sung bản ký tươi')
@section('css')
<link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20261026') }}">
<link rel="stylesheet" href="{{ asset('css/hard-copy-supplements.css?v=20261028-1') }}">
@stop
@section('content_header')
<div class="supplement-header">
    <div><h1 class="m-0">Bổ sung bản ký tươi</h1><small class="text-muted">Theo dõi yêu cầu bổ sung bản giấy của phiếu CNCL đã ký số</small></div>
    <div class="supplement-links">
        @can('certificate.view')<a class="btn btn-outline-secondary" href="{{ route('quality-certificates.index', ['status' => 'SIGNED']) }}"><i class="fas fa-certificate"></i> Phiếu đã phát hành</a>@endcan
        @can('report.view')<a class="btn btn-outline-primary" href="{{ route('reports.hard-copy-supplements') }}"><i class="fas fa-chart-bar"></i> Báo cáo bổ sung</a>@endcan
    </div>
</div>
@stop
@section('content')
@include('hard_copy_supplements.messages')
<div class="supplement-index">
<nav class="supplement-status-tabs" aria-label="Lọc nhanh trạng thái">
    <a href="{{ route('hard-copy-supplements.index', request()->except(['status', 'page', 'work_queue'])) }}" class="{{ !request('status') && !request()->boolean('work_queue') ? 'active' : '' }}" @if(!request('status') && !request()->boolean('work_queue')) aria-current="page" @endif><i class="fas fa-list" aria-hidden="true"></i> Tất cả <span class="badge badge-light">{{ number_format($statusCounts->sum()) }}</span></a>
    @foreach(\App\Models\HardCopySupplement::LABELS as $key => $label)
    <a href="{{ route('hard-copy-supplements.index', array_merge(request()->except(['page', 'work_queue']), ['status' => $key])) }}" class="{{ request('status') === $key ? 'active' : '' }}" @if(request('status') === $key) aria-current="page" @endif>{{ $label }} <span class="badge badge-light">{{ number_format($statusCounts[$key] ?? 0) }}</span></a>
    @endforeach
</nav>
<div class="supplement-filter-panel">
    <div class="filter-title mb-3"><span class="filter-icon"><i class="fas fa-filter"></i></span> Bộ lọc yêu cầu</div>
    @include('hard_copy_supplements.filters')
</div>
<div class="supplement-list-panel">
<div class="supplement-list-heading"><h2><i class="fas fa-list-alt text-primary mr-2"></i>Danh sách yêu cầu bổ sung</h2><span class="badge badge-light border">{{ number_format($items->total()) }} yêu cầu</span></div>
@include('hard_copy_supplements.table', ['sortable' => true])
</div>
</div>
@stop
