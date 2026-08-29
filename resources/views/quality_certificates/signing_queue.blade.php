@extends('adminlte::page')

@section('title', 'Trưởng PTN duyệt ký')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .signing-metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .signing-metric {
            background: #fff;
            border: 1px solid #dde3ea;
            border-left: 4px solid #007bff;
            border-radius: 6px;
            color: inherit;
            min-height: 78px;
            padding: 12px 14px;
        }

        .signing-metric:hover {
            color: inherit;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .signing-metric strong {
            display: block;
            font-size: 24px;
            line-height: 1;
        }

        .signing-metric span {
            color: #5b6675;
            font-size: 13px;
        }

        .signing-metric.warning { border-left-color: #ffc107; }
        .signing-metric.danger { border-left-color: #dc3545; }
        .signing-metric.success { border-left-color: #28a745; }
        .signing-metric.muted { border-left-color: #6c757d; }

        .signing-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .signing-tabs .btn {
            border-radius: 5px;
            font-weight: 600;
        }

        .filter-toolbar.signing-filter-toolbar {
            align-items: end;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .filter-toolbar.signing-filter-toolbar .filter-title {
            grid-column: 1 / -1;
            min-height: auto;
            padding-bottom: 0;
        }

        .filter-toolbar.signing-filter-toolbar .filter-keyword {
            grid-column: span 3;
        }

        .filter-toolbar.signing-filter-toolbar .filter-actions {
            justify-content: flex-end;
        }

        .signing-table {
            min-width: 1180px;
        }

        .signing-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: center;
        }

        .signing-actions .btn {
            min-width: 34px;
        }

        .signing-row-expired { background: #fff1f1; }
        .signing-row-pending { background: #f3f8ff; }
        .signing-row-ready { background: #fffdf1; }

        @media (max-width: 1199.98px) {
            .filter-toolbar.signing-filter-toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .filter-toolbar.signing-filter-toolbar .filter-keyword,
            .filter-toolbar.signing-filter-toolbar .filter-actions {
                grid-column: 1 / -1;
            }

            .filter-toolbar.signing-filter-toolbar .filter-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 575.98px) {
            .filter-toolbar.signing-filter-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Trưởng PTN duyệt ký</h1>
        <small class="text-muted">Theo dõi phiếu chờ ký số, kiểm tra kết quả VNPT SmartCA và trả lại phiếu khi cần sửa.</small>
    </div>

    @if((($metrics['pending'] ?? 0) + ($metrics['expired'] ?? 0)) > 0)
        <form action="{{ route('quality-certificates.bulk-smartca-status') }}"
              method="POST"
              class="mt-2 mt-md-0"
              data-loading-lock
              data-loading-message="Đang kiểm tra hàng loạt kết quả ký VNPT SmartCA và gửi email cho các phiếu ký thành công. Vui lòng chờ..."
              onsubmit="if (!confirm('Kiểm tra tối đa 30 phiếu đang chờ app VNPT SmartCA?')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
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

<div class="signing-metric-grid">
    <a class="signing-metric warning" href="{{ route('quality-certificates.ready-to-sign') }}">
        <strong>{{ $metrics['ready'] ?? 0 }}</strong>
        <span>Chờ gửi ký</span>
    </a>
    <a class="signing-metric" href="{{ route('quality-certificates.signing-queue', ['status' => 'WAIT_APPROVAL']) }}">
        <strong>{{ $metrics['waiting_approval'] ?? 0 }}</strong>
        <span>Chờ Trưởng PTN duyệt</span>
    </a>
    <a class="signing-metric" href="{{ route('quality-certificates.signing-queue', ['status' => 'PENDING']) }}">
        <strong>{{ $metrics['pending'] ?? 0 }}</strong>
        <span>Đang chờ app SmartCA</span>
    </a>
    <a class="signing-metric danger" href="{{ route('quality-certificates.signing-queue', ['status' => 'EXPIRED']) }}">
        <strong>{{ $metrics['expired'] ?? 0 }}</strong>
        <span>Hết hạn cần xử lý</span>
    </a>
    <a class="signing-metric danger" href="{{ route('quality-certificates.signing-queue', ['status' => 'URGENT']) }}">
        <strong>{{ $metrics['urgent'] ?? 0 }}</strong>
        <span>Yêu cầu gấp chưa ký</span>
    </a>
    <a class="signing-metric success" href="{{ route('quality-certificates.signing-queue', ['status' => 'SIGNED_TODAY']) }}">
        <strong>{{ $metrics['signed_today'] ?? 0 }}</strong>
        <span>Đã ký hôm nay</span>
    </a>
    <a class="signing-metric muted" href="{{ route('quality-certificates.signing-queue', ['status' => 'REJECTED']) }}">
        <strong>{{ $metrics['rejected'] ?? 0 }}</strong>
        <span>Đã trả lại</span>
    </a>
</div>

@php
    $currentStatus = request('status', 'ACTIONABLE');
    $tabs = [
        'ACTIONABLE' => 'Cần xử lý',
        'WAIT_APPROVAL' => 'Chờ duyệt',
        'PENDING' => 'Đang chờ app',
        'EXPIRED' => 'Hết hạn',
        'URGENT' => 'Yêu cầu gấp',
        'SIGNED_TODAY' => 'Đã ký hôm nay',
        'REJECTED' => 'Đã trả lại',
    ];
@endphp

<div class="card request-filter-card">
    <div class="card-body">
        <div class="signing-tabs">
            @foreach($tabs as $value => $label)
                <a href="{{ route('quality-certificates.signing-queue', array_filter(['status' => $value, 'keyword' => request('keyword')])) }}"
                   class="btn btn-sm {{ $currentStatus === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET">
            <div class="filter-toolbar signing-filter-toolbar">
                <input type="hidden" name="status" value="{{ $currentStatus }}">

                <div class="filter-title">
                    <span class="filter-icon"><i class="fas fa-filter"></i></span>
                    <span>Bộ lọc</span>
                </div>

                <div class="filter-field filter-keyword">
                    <label for="keyword">Từ khóa</label>
                    <div class="form-group mb-0">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input id="keyword"
                                   type="text"
                                   name="keyword"
                                   class="form-control"
                                   value="{{ request('keyword') }}"
                                   placeholder="Số phiếu, số yêu cầu, hóa đơn, khách hàng, công trình">
                        </div>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Lọc</button>
                    <a href="{{ route('quality-certificates.signing-queue') }}" class="btn btn-outline-secondary" title="Làm mới">
                        <i class="fas fa-sync"></i>
                    </a>
                    @if(request()->hasAny(['keyword', 'status']))
                        <a href="{{ route('quality-certificates.signing-queue') }}" class="btn btn-outline-danger">
                            <i class="fas fa-times"></i> Xóa lọc
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card request-list-card">
    <div class="card-header bg-white">
        <div>
            <h3 class="card-title"><i class="fas fa-user-check"></i> Danh sách phiếu duyệt ký</h3>
            <div class="text-muted small mt-1">Ưu tiên phiếu hết hạn, đang chờ app, yêu cầu gấp và phiếu cũ hơn.</div>
        </div>
        <div class="card-tools">
            <a href="{{ route('quality-certificates.ready-to-sign') }}" class="btn btn-sm btn-outline-success mr-2">
                <i class="fas fa-paper-plane"></i> Mở màn chờ gửi ký
            </a>
            <span class="badge badge-info">Tổng số: {{ $certificates->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered request-table signing-table mb-0">
            <thead>
                <tr>
                    <th style="width:60px">STT</th>
                    <th style="width:170px">Số phiếu</th>
                    <th style="width:170px">Yêu cầu</th>
                    <th>Khách hàng / Công trình</th>
                    <th style="width:150px">Trung tâm</th>
                    <th style="width:150px">PTN lập</th>
                    <th style="width:150px">Trạng thái ký</th>
                    <th style="width:145px">Hạn xác nhận</th>
                    <th style="width:170px" class="text-center">Thao tác</th>
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
                        $statusClass = 'badge-warning';
                        $statusIcon = 'fas fa-paper-plane';
                        $statusText = 'Chờ gửi ký';
                        $rowClass = 'signing-row-ready';

                        if ($certificate->status === 'REJECTED') {
                            $statusClass = 'badge-secondary';
                            $statusIcon = 'fas fa-undo';
                            $statusText = 'Đã trả lại';
                            $rowClass = '';
                        } elseif ($certificate->signed_at) {
                            $statusClass = 'badge-success';
                            $statusIcon = 'fas fa-check';
                            $statusText = 'Đã ký';
                            $rowClass = '';
                        } elseif ($certificate->isAwaitingManagerApproval()) {
                            $statusClass = 'badge-info';
                            $statusIcon = 'fas fa-user-check';
                            $statusText = 'Chờ Trưởng PTN duyệt';
                            $rowClass = '';
                        } elseif ($expired) {
                            $statusClass = 'badge-danger';
                            $statusIcon = 'fas fa-hourglass-end';
                            $statusText = 'Hết hạn ký';
                            $rowClass = 'signing-row-expired';
                        } elseif ($certificate->smartca_status === 'PENDING') {
                            $statusClass = 'badge-primary';
                            $statusIcon = 'fas fa-mobile-alt';
                            $statusText = 'Đang chờ app';
                            $rowClass = 'signing-row-pending';
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>{{ $certificates->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $certificate->certificate_no }}</strong>
                            <div class="text-muted small">{{ optional($certificate->created_at)->format('d/m/Y H:i') }}</div>
                        </td>
                        <td>
                            <strong>{{ $certificate->request->request_no ?? '-' }}</strong>
                            @if($certificate->request?->invoice_no)
                                <div class="text-muted small">HĐ: {{ $certificate->request->invoice_no }}</div>
                            @endif
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
                            <span class="badge {{ $statusClass }}">
                                <i class="{{ $statusIcon }}"></i> {{ $statusText }}
                            </span>
                            @if($certificate->pades_status && in_array($certificate->smartca_status, ['PENDING', 'SIGNED', 'EXPIRED'], true))
                                <div class="text-muted small mt-1">PAdES: {{ $certificate->pades_status }}</div>
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
                            <div class="signing-actions">
                                <a href="{{ route('quality-certificates.show', $certificate) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('quality-certificates.pdf', $certificate) }}" target="_blank" class="btn btn-sm btn-secondary" title="Xem PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>

                                @if(!$certificate->signed_at && $certificate->status !== 'REJECTED')
                                    @if(in_array($certificate->smartca_status, ['PENDING', 'EXPIRED'], true) && $certificate->smartca_transaction_id)
                                        <form action="{{ route('quality-certificates.smartca-status', $certificate) }}"
                                              method="POST"
                                              class="d-inline"
                                              data-loading-lock
                                              data-loading-message="Đang kiểm tra kết quả ký VNPT SmartCA và gửi email nếu ký thành công. Vui lòng chờ..."
                                              onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary" title="Kiểm tra kết quả ký">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </form>
                                        @if($expired)
                                            <form action="{{ route('quality-certificates.sign', $certificate) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  data-loading-lock
                                                  data-loading-message="Đang gửi lại yêu cầu ký sang VNPT SmartCA. Vui lòng chờ..."
                                                  onsubmit="if (!confirm('Gửi lại yêu cầu ký phiếu này? Hệ thống sẽ kiểm tra giao dịch cũ trước khi gửi lại.')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" title="Gửi lại yêu cầu ký">
                                                    <i class="fas fa-file-signature"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        @if($certificate->canApproveForSigningQueue())
                                            <form action="{{ route('quality-certificates.approve-for-signing', $certificate) }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  data-loading-lock
                                                  data-loading-message="Đang đưa phiếu vào danh sách chờ gửi ký. Vui lòng chờ..."
                                                  onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                                @csrf
                                                <button class="btn btn-sm btn-primary" title="Duyệt đưa vào chờ gửi ký">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('quality-certificates.sign', $certificate) }}"
                                              method="POST"
                                              class="d-inline"
                                              data-loading-lock
                                              data-loading-message="Đang gửi yêu cầu ký sang VNPT SmartCA. Vui lòng chờ..."
                                              onsubmit="if (!confirm('{{ $certificate->isAwaitingManagerApproval() ? 'Gửi ký trực tiếp phiếu này? Thao tác này đồng thời xác nhận Trưởng PTN đã duyệt nội dung.' : 'Gửi yêu cầu ký phiếu này sang VNPT SmartCA?' }}')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                            @csrf
                                            <button class="btn btn-sm btn-success" title="Gửi ký SmartCA">
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
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
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
                      class="modal-content"
                      data-loading-lock
                      data-loading-message="Đang trả lại phiếu. Vui lòng chờ..."
                      onsubmit="window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
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
