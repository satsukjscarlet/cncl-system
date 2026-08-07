@extends('adminlte::page')

@section('title', 'Trưởng PTN duyệt ký')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .signing-metrics {
            display: grid;
            grid-template-columns: repeat(6, minmax(140px, 1fr));
            gap: 12px;
        }

        .signing-metric {
            background: #fff;
            border: 1px solid #dde3ea;
            border-left: 4px solid #007bff;
            border-radius: 6px;
            padding: 12px 14px;
            min-height: 76px;
        }

        .signing-metric strong {
            display: block;
            font-size: 24px;
            line-height: 1;
        }

        .signing-metric span {
            color: #5f6b7a;
            font-size: 13px;
        }

        .signing-filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .signing-filter-tabs .btn {
            border-radius: 6px;
        }

        @media (max-width: 1200px) {
            .signing-metrics {
                grid-template-columns: repeat(3, minmax(140px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .signing-metrics {
                grid-template-columns: repeat(2, minmax(140px, 1fr));
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Trưởng PTN duyệt ký</h1>
        <small class="text-muted">Hàng đợi kiểm tra, ký số và trả lại phiếu CNCL</small>
    </div>
    @if((($metrics['pending'] ?? 0) + ($metrics['expired'] ?? 0)) > 0)
        <form action="{{ route('quality-certificates.bulk-smartca-status') }}"
              method="POST"
              class="mt-2 mt-md-0"
              onsubmit="return confirm('Kiểm tra tối đa 30 phiếu đang chờ app VNPT SmartCA?')">
            @csrf
            <input type="hidden" name="limit" value="30">
            <button class="btn btn-primary">
                <i class="fas fa-sync"></i> Kiểm tra tất cả đang chờ app
            </button>
        </form>
    @endif
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="signing-metrics mb-3">
    <div class="signing-metric">
        <strong>{{ $metrics['ready'] }}</strong>
        <span>Chờ gửi ký</span>
    </div>
    <div class="signing-metric" style="border-left-color:#007bff">
        <strong>{{ $metrics['pending'] }}</strong>
        <span>Đang chờ app</span>
    </div>
    <div class="signing-metric" style="border-left-color:#dc3545">
        <strong>{{ $metrics['expired'] }}</strong>
        <span>Hết hạn ký</span>
    </div>
    <div class="signing-metric" style="border-left-color:#6c757d">
        <strong>{{ $metrics['rejected'] }}</strong>
        <span>Đã trả lại</span>
    </div>
    <div class="signing-metric" style="border-left-color:#28a745">
        <strong>{{ $metrics['signed_today'] }}</strong>
        <span>Đã ký hôm nay</span>
    </div>
    <div class="signing-metric" style="border-left-color:#ffc107">
        <strong>{{ $metrics['urgent'] }}</strong>
        <span>Yêu cầu gấp</span>
    </div>
</div>

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter"></i> Bộ lọc duyệt ký</h3>
    </div>
    <div class="card-body">
        <div class="signing-filter-tabs mb-3">
            @php
                $tabs = [
                    'ACTIONABLE' => 'Cần xử lý',
                    'READY' => 'Chờ gửi ký',
                    'PENDING' => 'Đang chờ app',
                    'EXPIRED' => 'Hết hạn ký',
                    'URGENT' => 'Yêu cầu gấp',
                    'REJECTED' => 'Đã trả lại',
                    'SIGNED_TODAY' => 'Đã ký hôm nay',
                ];
                $currentStatus = request('status', 'ACTIONABLE');
            @endphp
            @foreach($tabs as $value => $label)
                <a href="{{ route('quality-certificates.signing-queue', array_filter(['status' => $value, 'keyword' => request('keyword')])) }}"
                   class="btn btn-sm {{ $currentStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" class="row align-items-end">
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            <div class="col-lg-9 col-md-8">
                <div class="form-group">
                    <label>Từ khóa</label>
                    <input type="text"
                           name="keyword"
                           class="form-control"
                           value="{{ request('keyword') }}"
                           placeholder="Số phiếu, số yêu cầu, khách hàng, công trình, hóa đơn">
                </div>
            </div>
            <div class="col-lg-3 col-md-4">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                    <a href="{{ route('quality-certificates.signing-queue') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title"><i class="fas fa-user-check"></i> Danh sách phiếu cần duyệt</h3>
        <div class="card-tools">
            <span class="badge badge-info">Tổng số: {{ $certificates->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Số phiếu</th>
                    <th>Yêu cầu</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Trung tâm</th>
                    <th>PTN lập</th>
                    <th>Trạng thái ký</th>
                    <th>Hạn xác nhận</th>
                    <th style="width:230px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $certificate)
                    @php
                        $ttl = max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
                        $requestedAt = $certificate->smartca_requested_at ?: $certificate->updated_at;
                        $expiresAt = $requestedAt ? $requestedAt->copy()->addMinutes($ttl) : null;
                        $expired = $certificate->smartca_status === 'EXPIRED'
                            || ($certificate->smartca_status === 'PENDING' && $expiresAt && $expiresAt->lte(now()));
                    @endphp
                    <tr>
                        <td>{{ $certificates->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $certificate->certificate_no }}</strong>
                            <div class="text-muted small">{{ optional($certificate->created_at)->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>
                            {{ $certificate->request->request_no ?? '-' }}
                            @if($certificate->request?->is_urgent)
                                <div class="small text-danger">
                                    <i class="fas fa-bolt"></i> {{ $certificate->request->urgentReason->name ?? 'Yêu cầu gấp' }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $certificate->request->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">{{ $certificate->request->customer->project_name ?? '' }}</div>
                        </td>
                        <td>{{ $certificate->request->distributionCenter->name ?? '-' }}</td>
                        <td>{{ $certificate->creator->name ?? '-' }}</td>
                        <td>
                            @if($certificate->status === 'REJECTED')
                                <span class="badge badge-secondary"><i class="fas fa-undo"></i> Đã trả lại</span>
                            @elseif($certificate->signed_at)
                                <span class="badge badge-success"><i class="fas fa-check"></i> Đã ký</span>
                            @elseif($expired)
                                <span class="badge badge-danger"><i class="fas fa-hourglass-end"></i> Hết hạn ký</span>
                            @elseif($certificate->smartca_status === 'PENDING')
                                <span class="badge badge-primary"><i class="fas fa-hourglass-half"></i> Chờ app</span>
                            @else
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Chờ gửi ký</span>
                            @endif
                        </td>
                        <td>
                            @if($certificate->smartca_status === 'PENDING' || $certificate->smartca_status === 'EXPIRED')
                                {{ $expiresAt ? $expiresAt->format('d/m/Y H:i') : '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('quality-certificates.show', $certificate) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('quality-certificates.pdf', $certificate) }}" target="_blank" class="btn btn-sm btn-secondary" title="Xem PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>

                            @if(!$certificate->signed_at && $certificate->status !== 'REJECTED')
                                @if($certificate->smartca_status === 'PENDING' && !$expired)
                                    <form action="{{ route('quality-certificates.smartca-status', $certificate) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-primary" title="Kiểm tra kết quả ký">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </form>
                                @else
                                    <form action="{{ route('quality-certificates.sign', $certificate) }}" method="POST"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ $expired ? 'Gửi lại yêu cầu ký phiếu này?' : 'Gửi yêu cầu ký phiếu này?' }}')">
                                        @csrf
                                        <button class="btn btn-sm {{ $expired ? 'btn-warning' : 'btn-success' }}" title="{{ $expired ? 'Gửi lại yêu cầu ký' : 'Gửi ký SmartCA' }}">
                                            <i class="fas fa-file-signature"></i>
                                        </button>
                                    </form>
                                @endif

                                @if($certificate->smartca_status !== 'PENDING' || $expired)
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            title="Từ chối ký / trả lại"
                                            data-toggle="modal"
                                            data-target="#rejectSignatureModal{{ $certificate->id }}">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <br>
                            Không có phiếu trong hàng đợi duyệt ký.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row align-items-center">
            <div class="col-md-6 text-muted small mb-2 mb-md-0">
                Hiển thị {{ $certificates->firstItem() ?? 0 }} - {{ $certificates->lastItem() ?? 0 }}
                / {{ $certificates->total() }} bản ghi
            </div>
            <div class="col-md-6">
                <div class="float-md-right">
                    {{ $certificates->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($certificates as $certificate)
    @php
        $ttl = max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
        $requestedAt = $certificate->smartca_requested_at ?: $certificate->updated_at;
        $expiresAt = $requestedAt ? $requestedAt->copy()->addMinutes($ttl) : null;
        $expired = $certificate->smartca_status === 'EXPIRED'
            || ($certificate->smartca_status === 'PENDING' && $expiresAt && $expiresAt->lte(now()));
    @endphp

    @if(!$certificate->signed_at && $certificate->status !== 'REJECTED' && ($certificate->smartca_status !== 'PENDING' || $expired))
        <div class="modal fade" id="rejectSignatureModal{{ $certificate->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST"
                      action="{{ route('quality-certificates.reject-signature', $certificate) }}"
                      class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-undo"></i> Từ chối ký / trả lại phiếu</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body text-left">
                        <p>Phiếu: <strong>{{ $certificate->certificate_no }}</strong></p>
                        <div class="form-group">
                            <label>Trả về bước <span class="text-danger">*</span></label>
                            <select name="reject_to" class="form-control" required>
                                <option value="PTN">PTN xử lý lại</option>
                                <option value="DVKH">DVKH xác nhận lại</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lý do trả lại <span class="text-danger">*</span></label>
                            <textarea name="rejected_reason" class="form-control" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                        <button class="btn btn-danger">
                            <i class="fas fa-paper-plane"></i> Xác nhận trả lại
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach
@stop
