@extends('adminlte::page')

@section('title', 'Phiếu CNCL')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Danh sách Phiếu CNCL</h1>
        <small class="text-muted">Quản lý Phiếu Chứng nhận Chất lượng đã được PTN lập</small>
    </div>
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

<div class="card card-primary card-outline filter-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Bộ lọc dữ liệu
        </h3>
    </div>

    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-lg-7 col-md-6">
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
                <div class="form-group">
                    <label>Trạng thái ký</label>
                    <select name="status" class="form-control select2">
                        <option value="SIGN_READY" {{ request('status') == 'SIGN_READY' ? 'selected' : '' }}>Chờ Trưởng PTN ký</option>
                        <option value="SMARTCA_PENDING" {{ request('status') == 'SMARTCA_PENDING' ? 'selected' : '' }}>Đang chờ ký số</option>
                        <option value="">Tất cả</option>
                        <option value="UNSIGNED" {{ request('status') == 'UNSIGNED' ? 'selected' : '' }}>Chưa ký</option>
                        <option value="SIGNED" {{ request('status') == 'SIGNED' ? 'selected' : '' }}>Đã ký/phát hành</option>
                        <option value="SMARTCA_EXPIRED" {{ request('status') == 'SMARTCA_EXPIRED' ? 'selected' : '' }}>Yêu cầu ký hết hạn</option>
                        <option value="REVOKED" {{ request('status') == 'REVOKED' ? 'selected' : '' }}>Đã hủy/thu hồi</option>
                        <option value="REJECTED" {{ request('status') == 'REJECTED' ? 'selected' : '' }}>Đã trả lại</option>
                    </select>
                </div>
            </div>

            <div class="col-lg-2 col-md-2">
                <div class="form-group filter-actions">
                    <button class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm
                    </button>
                    <a href="{{ route('quality-certificates.index') }}" class="btn btn-secondary" title="Xóa bộ lọc">
                        <i class="fas fa-sync"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title">
            <i class="fas fa-file-signature"></i> Danh sách phiếu
        </h3>

        <div class="card-tools">
            <span class="badge badge-info">
                Tổng số: {{ $certificates->total() }}
            </span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:60px">STT</th>
                    <th>Số phiếu</th>
                    <th>Số yêu cầu</th>
                    <th>Khách hàng / Công trình</th>
                    <th>Trung tâm</th>
                    <th>Người lập</th>
                    <th>Ngày ký</th>
                    <th>Trạng thái</th>
                    <th style="width:170px" class="text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                @forelse($certificates as $certificate)
                    <tr>
                        <td>{{ $certificates->firstItem() + $loop->index }}</td>

                        <td>
                            <strong>{{ $certificate->certificate_no }}</strong>
                            <div class="text-muted small">
                                {{ optional($certificate->created_at)->format('d/m/Y H:i') }}
                            </div>
                            @if($certificate->replacesCertificate)
                                <div class="mt-1">
                                    <span class="badge badge-info">
                                        <i class="fas fa-redo"></i> Phiếu cấp lại
                                    </span>
                                    <a class="small d-block mt-1" href="{{ route('quality-certificates.show', $certificate->replacesCertificate) }}">
                                        Cấp lại cho: {{ $certificate->replacesCertificate->certificate_no }}
                                    </a>
                                </div>
                            @endif
                            @if($certificate->replacedByCertificate)
                                <div class="mt-1">
                                    <span class="badge badge-warning">
                                        <i class="fas fa-exchange-alt"></i> Đã có phiếu thay thế
                                    </span>
                                    <a class="small d-block mt-1" href="{{ route('quality-certificates.show', $certificate->replacedByCertificate) }}">
                                        Phiếu mới: {{ $certificate->replacedByCertificate->certificate_no }}
                                    </a>
                                </div>
                            @endif
                        </td>

                        <td>{{ $certificate->request->request_no ?? '-' }}</td>

                        <td>
                            <strong>{{ $certificate->request->customer->customer_name ?? '-' }}</strong>
                            <div class="text-muted small">
                                {{ $certificate->request->customer->project_name ?? '' }}
                            </div>
                        </td>

                        <td>{{ $certificate->request->distributionCenter->name ?? '-' }}</td>

                        <td>{{ $certificate->creator->name ?? '-' }}</td>

                        <td>{{ $certificate->signed_at ? $certificate->signed_at->format('d/m/Y H:i') : '-' }}</td>

                        <td>
                            @if($certificate->status === 'REVOKED')
                                <span class="badge badge-danger">
                                    <i class="fas fa-ban"></i> Đã hủy/thu hồi
                                </span>
                            @elseif($certificate->status === 'REJECTED')
                                <span class="badge badge-secondary">
                                    <i class="fas fa-undo"></i> Đã trả lại
                                </span>
                            @elseif($certificate->smartca_status === 'EXPIRED' || ($certificate->smartca_status === 'PENDING' && $certificate->smartca_requested_at && $certificate->smartca_requested_at->copy()->addMinutes(max(1, (int) config('services.smartca.pending_ttl_minutes', 5)))->lte(now())))
                                <span class="badge badge-danger">
                                    <i class="fas fa-hourglass-end"></i> Hết hạn ký
                                </span>
                            @elseif($certificate->smartca_status === 'PENDING')
                                <span class="badge badge-primary">
                                    <i class="fas fa-hourglass-half"></i> Chờ ký
                                </span>
                            @elseif($certificate->signed_at)
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Đã ký/phát hành
                                </span>
                            @else
                                <span class="badge badge-warning">
                                    <i class="fas fa-clock"></i> Chưa ký
                                </span>
                            @endif
                        </td>

                        <td class="text-center">
                            <a href="{{ route('quality-certificates.show', $certificate) }}"
                               class="btn btn-sm btn-info"
                               title="Xem">
                                <i class="fas fa-eye"></i>
                            </a>
                            @can('request.create')
                                @if($certificate->canRequestReissue())
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            title="Yêu cầu cấp lại"
                                            data-toggle="modal"
                                            data-target="#reissueModal{{ $certificate->id }}">
                                        <i class="fas fa-redo"></i>
                                    </button>

                                    <div class="modal fade" id="reissueModal{{ $certificate->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST"
                                                  action="{{ route('quality-certificates.request-reissue', $certificate) }}"
                                                  class="modal-content">
                                                @csrf

                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-redo"></i> Yêu cầu cấp lại phiếu
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>

                                                <div class="modal-body text-left">
                                                    <p>Phiếu: <strong>{{ $certificate->certificate_no }}</strong></p>
                                                    <div class="alert alert-warning">
                                                        Yêu cầu này sẽ được gửi sang DVKH. Khi DVKH xác nhận, phiếu cũ sẽ bị hủy/thu hồi và quy trình cấp phiếu mới bắt đầu.
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Lý do cấp lại <span class="text-danger">*</span></label>
                                                        <textarea name="reissue_reason"
                                                                  class="form-control"
                                                                  rows="4"
                                                                  required></textarea>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                                                    <button class="btn btn-danger">
                                                        <i class="fas fa-paper-plane"></i> Gửi yêu cầu
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <br>
                            Chưa có phiếu CNCL.
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

@stop
