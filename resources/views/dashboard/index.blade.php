@extends('adminlte::page')

@section('title', 'Dashboard CNCL')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Dashboard CNCL</h1>
        <small class="text-muted">Tổng quan vận hành hệ thống cấp Phiếu Chứng nhận Chất lượng</small>
    </div>
</div>
@stop

@section('content')

<div class="row">
    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalRequests }}</h3>
                <p>Tổng yêu cầu</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-alt"></i>
            </div>
            <a href="{{ route('certificate-requests.index') }}" class="small-box-footer">
                Xem chi tiết <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $waitDvkh }}</h3>
                <p>Chờ DVKH</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check"></i>
            </div>
            <a href="{{ route('dvkh.requests.index') }}" class="small-box-footer">
                Xử lý <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $waitPtn }}</h3>
                <p>Chờ PTN</p>
            </div>
            <div class="icon">
                <i class="fas fa-vials"></i>
            </div>
            <a href="{{ route('ptn.requests.index') }}" class="small-box-footer">
                Xử lý <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $ptnProcessing }}</h3>
                <p>PTN đang xử lý</p>
            </div>
            <div class="icon">
                <i class="fas fa-flask"></i>
            </div>
            <a href="{{ route('ptn.requests.index') }}" class="small-box-footer">
                Xem <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $completed }}</h3>
                <p>Hoàn tất</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('quality-certificates.index') }}" class="small-box-footer">
                Xem phiếu <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-2 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $cancelled }}</h3>
                <p>Trả lại / Hủy</p>
            </div>
            <div class="icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <a href="{{ route('certificate-requests.index', ['status' => 'CANCELLED']) }}" class="small-box-footer">
                Xem <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Phiếu CNCL theo tháng năm {{ now()->year }}
                </h3>
            </div>

            <div class="card-body">
                <canvas id="monthlyChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-signature"></i> Tình trạng phiếu
                </h3>
            </div>

            <div class="card-body">
                <div class="info-box">
                    <span class="info-box-icon bg-info">
                        <i class="fas fa-file-alt"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Tổng phiếu</span>
                        <span class="info-box-number">{{ $totalCertificates }}</span>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Chưa ký/phát hành</span>
                        <span class="info-box-number">{{ $unsignedCertificates }}</span>
                    </div>
                </div>

                <div class="info-box">
                    <span class="info-box-icon bg-success">
                        <i class="fas fa-check"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Đã ký/phát hành</span>
                        <span class="info-box-number">{{ $signedCertificates }}</span>
                    </div>
                </div>

                @if($slaTotal)
                    <div class="alert alert-light border mb-0">
                        <strong>SLA toàn trình:</strong>
                        cảnh báo {{ $slaTotal->warning_minutes }} phút,
                        quá hạn {{ $slaTotal->limit_minutes }} phút.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-check"></i> Yêu cầu chờ DVKH
                </h3>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Số YC</th>
                            <th>Khách hàng</th>
                            <th>Thời gian tạo</th>
                            <th style="width:70px">Xem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dvkhWaiting as $item)
                            <tr>
                                <td>{{ $item->request_no }}</td>
                                <td>
                                    {{ $item->customer->customer_name ?? '—' }}
                                    <div class="text-muted small">{{ $item->customer->project_name ?? '' }}</div>
                                </td>
                                <td>{{ optional($item->created_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('dvkh.requests.show', $item) }}" class="btn btn-xs btn-info">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Không có yêu cầu chờ DVKH.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-vials"></i> Yêu cầu chờ PTN
                </h3>
            </div>

            <div class="card-body table-responsive p-0">
                <table class="table table-sm table-hover table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Số YC</th>
                            <th>Khách hàng</th>
                            <th>Trạng thái</th>
                            <th style="width:70px">Xem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ptnWaiting as $item)
                            <tr>
                                <td>{{ $item->request_no }}</td>
                                <td>
                                    {{ $item->customer->customer_name ?? '—' }}
                                    <div class="text-muted small">{{ $item->customer->project_name ?? '' }}</div>
                                </td>
                                <td>
                                    @include('certificate_requests.partials.status_badge', ['status' => $item->status])
                                </td>
                                <td>
                                    <a href="{{ route('ptn.requests.show', $item) }}" class="btn btn-xs btn-info">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Không có yêu cầu chờ PTN.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card card-danger card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exclamation-triangle"></i> Cảnh báo SLA
        </h3>

        <div class="card-tools">
            <span class="badge badge-danger">{{ $slaAlerts->count() }} cảnh báo</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Số yêu cầu</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Công đoạn</th>
                    <th>Thời gian chờ</th>
                    <th>Ngưỡng SLA</th>
                    <th>Mức cảnh báo</th>
                    <th style="width:80px">Xem</th>
                </tr>
            </thead>

            <tbody>
                @forelse($slaAlerts as $item)
                    <tr>
                        <td>{{ $item->request_no }}</td>
                        <td>
                            <strong>{{ $item->customer->customer_name ?? '—' }}</strong>
                            <div class="text-muted small">{{ $item->customer->project_name ?? '' }}</div>
                        </td>
                        <td>{{ $item->sla_step_name }}</td>
                        <td>{{ number_format($item->sla_minutes / 60, 1) }} giờ</td>
                        <td>
                            Cảnh báo: {{ $item->sla_warning_minutes }} phút
                            <br>
                            Quá hạn: {{ $item->sla_limit_minutes }} phút
                        </td>
                        <td>
                            @if($item->sla_level === 'danger')
                                <span class="badge badge-danger">Quá hạn</span>
                            @else
                                <span class="badge badge-warning">Gần quá hạn</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status === 'WAIT_DVKH')
                                <a href="{{ route('dvkh.requests.show', $item) }}" class="btn btn-xs btn-info">Xem</a>
                            @else
                                <a href="{{ route('ptn.requests.show', $item) }}" class="btn btn-xs btn-info">Xem</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            Chưa có yêu cầu gần quá hạn hoặc quá hạn SLA.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('monthlyChart');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Số phiếu',
                data: @json($chartValues),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
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
</script>
@stop