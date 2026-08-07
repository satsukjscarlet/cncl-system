@extends('adminlte::page')

@section('title', 'Dashboard CNCL')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .workspace-card {
            border: 1px solid #e1e6ef;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(17, 24, 39, .05);
            min-height: 132px;
        }

        .workspace-card .icon-box {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
        }

        .workspace-card .value {
            font-size: 30px;
            line-height: 1;
            font-weight: 700;
        }

        .work-table td,
        .work-table th {
            vertical-align: middle;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #eef2f7;
            color: #344054;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Không gian làm việc CNCL</h1>
        <small class="text-muted">
            Vai trò hiện tại: <strong>{{ $role }}</strong>. Hệ thống chỉ ưu tiên hiển thị các việc phù hợp với tài khoản này.
        </small>
    </div>

    <div class="mt-2 mt-md-0">
        @can('request.create')
            <a href="{{ route('certificate-requests.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tạo yêu cầu
            </a>
        @endcan

        @can('ptn.process')
            <a href="{{ route('ptn.requests.direct-create') }}" class="btn btn-outline-primary">
                <i class="fas fa-vials"></i> PTN cấp trực tiếp
            </a>
        @endcan

        @can('certificate.sign')
            <a href="{{ route('quality-certificates.signing-queue') }}" class="btn btn-outline-dark">
                <i class="fas fa-user-check"></i> Hàng đợi ký
            </a>
        @endcan
    </div>
</div>
@stop

@section('content')
<div class="row">
    @foreach($cards as $card)
        @php
            $bg = match($card['color']) {
                'success' => 'bg-success',
                'warning' => 'bg-warning',
                'danger' => 'bg-danger',
                'info' => 'bg-info',
                'orange' => 'bg-orange',
                default => 'bg-primary',
            };
        @endphp

        <div class="col-xl-3 col-md-6 mb-3">
            <a href="{{ $card['url'] }}" class="text-decoration-none text-reset">
                <div class="workspace-card p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="text-muted font-weight-bold mb-2">{{ $card['label'] }}</div>
                            <div class="value">{{ number_format($card['value']) }}</div>
                        </div>
                        <span class="icon-box {{ $bg }}">
                            <i class="{{ $card['icon'] }}"></i>
                        </span>
                    </div>
                    <div class="text-muted small mt-3">
                        Mở danh sách <i class="fas fa-arrow-right ml-1"></i>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row">
    <div class="col-xl-7">
        @include('dashboard.partials.work_list', ['list' => $primaryList])
    </div>

    <div class="col-xl-5">
        @include('dashboard.partials.work_list', ['list' => $secondaryList])
    </div>
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Phiếu CNCL theo tháng năm {{ now()->year }}
                </h3>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="115"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i> Cảnh báo SLA
                </h3>
                <div class="card-tools">
                    <span class="badge badge-danger">{{ $slaAlerts->count() }}</span>
                </div>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover mb-0 work-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Số yêu cầu</th>
                            <th>Khách hàng</th>
                            <th>Công đoạn</th>
                            <th class="text-right">Chờ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($slaAlerts as $item)
                            @php
                                $slaUrl = route('certificate-requests.show', $item);

                                if ($item->status === 'WAIT_DVKH' && auth()->user()->can('dvkh.process')) {
                                    $slaUrl = route('dvkh.requests.show', $item);
                                } elseif (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING']) && auth()->user()->can('ptn.process')) {
                                    $slaUrl = route('ptn.requests.show', $item);
                                }
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ $slaUrl }}">
                                        {{ $item->request_no }}
                                    </a>
                                </td>
                                <td>
                                    <strong>{{ $item->customer->customer_name ?? '-' }}</strong>
                                    <div class="text-muted small">{{ $item->distributionCenter->name ?? '' }}</div>
                                </td>
                                <td>{{ $item->sla_step_name }}</td>
                                <td class="text-right">
                                    <span class="badge badge-{{ $item->sla_level === 'danger' ? 'danger' : 'warning' }}">
                                        {{ number_format($item->sla_minutes / 60, 1) }} giờ
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Chưa có cảnh báo SLA.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('monthlyChart');

    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Số phiếu',
                    data: @json($chartValues),
                    borderWidth: 1,
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
</script>
@stop
