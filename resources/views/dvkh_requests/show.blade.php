@extends('adminlte::page')

@section('title', 'DVKH kiểm tra yêu cầu')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .dvkh-page .page-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .dvkh-summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .dvkh-summary-item {
            border: 1px solid #dde3ea;
            border-radius: 6px;
            background: #fff;
            padding: 12px 14px;
            min-height: 78px;
        }

        .dvkh-summary-label {
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .dvkh-summary-value {
            font-weight: 700;
            font-size: 16px;
            overflow-wrap: anywhere;
        }

        .dvkh-info-grid {
            display: grid;
            grid-template-columns: 170px minmax(0, 1fr);
            gap: 8px 12px;
        }

        .dvkh-info-label {
            color: #6c757d;
            font-weight: 600;
        }

        .dvkh-info-value {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .dvkh-action-box {
            border: 1px solid #d9ecff;
            border-left: 4px solid #0d6efd;
            border-radius: 6px;
            background: #f7fbff;
            padding: 14px;
        }

        .dvkh-products th,
        .dvkh-products td {
            vertical-align: middle !important;
        }

        .dvkh-products .col-center {
            text-align: center;
            white-space: nowrap;
        }

        .dvkh-products .product-name {
            min-width: 260px;
        }

        @media (max-width: 1199.98px) {
            .dvkh-summary {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dvkh-page .content-header-bar {
                align-items: flex-start !important;
                flex-direction: column;
            }

            .dvkh-page .page-actions {
                justify-content: flex-start;
                width: 100%;
            }

            .dvkh-summary {
                grid-template-columns: 1fr;
            }

            .dvkh-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
<div class="dvkh-page">
    <div class="content-header-bar d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">DVKH kiểm tra yêu cầu</h1>
            <small class="text-muted">{{ $certificateRequest->request_no }}</small>
        </div>

        <div class="page-actions">
            <a href="{{ route('dvkh.requests.index') }}" class="btn btn-default">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="dvkh-page">
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

    @include('quality_certificates.partials.workflow_steps', ['steps' => $requestWorkflowSteps])

    <div class="dvkh-summary">
        <div class="dvkh-summary-item">
            <div class="dvkh-summary-label">Trạng thái</div>
            <div class="dvkh-summary-value">
                @include('certificate_requests.partials.status_badge', ['certificateRequest' => $certificateRequest])
            </div>
        </div>

        <div class="dvkh-summary-item">
            <div class="dvkh-summary-label">Trung tâm</div>
            <div class="dvkh-summary-value">{{ $certificateRequest->distributionCenter->name ?? '-' }}</div>
        </div>

        <div class="dvkh-summary-item">
            <div class="dvkh-summary-label">Số hóa đơn</div>
            <div class="dvkh-summary-value">
                {{ $certificateRequest->invoice_no ?: '-' }}
                @if($invoiceDuplicates->isNotEmpty())
                    <span class="badge badge-warning ml-1"><i class="fas fa-exclamation-triangle"></i> Trùng</span>
                @endif
            </div>
        </div>

        <div class="dvkh-summary-item">
            <div class="dvkh-summary-label">Sản phẩm</div>
            <div class="dvkh-summary-value">{{ $certificateRequest->details->count() }} dòng</div>
        </div>
    </div>

    @if($invoiceDuplicates->isNotEmpty())
        <div class="alert alert-warning">
            <div class="font-weight-bold mb-2">
                <i class="fas fa-exclamation-triangle"></i>
                Cảnh báo: Số hóa đơn {{ $certificateRequest->invoice_no }} đã tồn tại trên hệ thống.
            </div>
            <div class="mb-2">
                DVKH vui lòng kiểm tra các yêu cầu bên dưới trước khi xác nhận chuyển PTN.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white mb-0">
                    <thead>
                        <tr>
                            <th>Số yêu cầu</th>
                            <th>Khách hàng / Công trình</th>
                            <th>Trung tâm</th>
                            <th>Trạng thái</th>
                            <th>Phiếu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoiceDuplicates as $duplicate)
                            <tr>
                                <td>
                                    <a href="{{ route('certificate-requests.show', $duplicate) }}" target="_blank">
                                        {{ $duplicate->request_no }}
                                    </a>
                                    <div class="text-muted small">{{ optional($duplicate->created_at)->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>
                                    <strong>{{ $duplicate->customer->customer_name ?? '-' }}</strong>
                                    <div class="text-muted small">{{ $duplicate->customer->project_name ?? '' }}</div>
                                </td>
                                <td>{{ $duplicate->distributionCenter->name ?? '-' }}</td>
                                <td>@include('certificate_requests.partials.status_badge', ['certificateRequest' => $duplicate])</td>
                                <td>{{ $duplicate->qualityCertificate->certificate_no ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @can('dvkh.process')
        @if($certificateRequest->status === 'WAIT_DVKH')
            @php
                $approveConfirm = $invoiceDuplicates->isNotEmpty()
                    ? 'Số hóa đơn của yêu cầu này đang trùng với yêu cầu khác. Bạn vẫn muốn xác nhận và chuyển sang PTN?'
                    : 'Xác nhận yêu cầu này và chuyển sang PTN?';
            @endphp

            <div class="dvkh-action-box mb-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="mb-2 mb-md-0">
                        <div class="font-weight-bold">Yêu cầu đang chờ DVKH xử lý</div>
                        <div class="text-muted small">Kiểm tra thông tin bên dưới, sau đó xác nhận chuyển PTN hoặc trả lại cho Trung tâm.</div>
                    </div>

                    <div>
                        <form action="{{ route('dvkh.requests.approve', $certificateRequest) }}"
                              method="POST"
                              class="d-inline"
                              data-loading-message="Đang xác nhận yêu cầu và chuyển sang PTN, vui lòng chờ..."
                              onsubmit="if (!confirm({!! json_encode($approveConfirm, JSON_UNESCAPED_UNICODE) !!})) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Xác nhận chuyển PTN
                            </button>
                        </form>

                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#dvkhRejectModal">
                            <i class="fas fa-times"></i> Trả lại
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endcan

    <div class="row">
        <div class="col-lg-5">
            <div class="card card-primary card-outline">
                <div class="card-header bg-white">
                    <h3 class="card-title"><i class="fas fa-file-alt"></i> Thông tin yêu cầu</h3>
                </div>

                <div class="card-body">
                    <div class="dvkh-info-grid">
                        <div class="dvkh-info-label">Số yêu cầu</div>
                        <div class="dvkh-info-value font-weight-bold">{{ $certificateRequest->request_no }}</div>

                        <div class="dvkh-info-label">Loại yêu cầu</div>
                        <div class="dvkh-info-value">
                            @if($certificateRequest->request_type === 'REISSUE')
                                <span class="badge badge-danger"><i class="fas fa-redo"></i> Cấp lại</span>
                            @elseif($certificateRequest->request_type === 'DIRECT_PTN')
                                <span class="badge badge-info"><i class="fas fa-vials"></i> PTN lập trực tiếp</span>
                            @else
                                <span class="badge badge-light border">Cấp mới</span>
                            @endif
                        </div>

                        @if($certificateRequest->request_type === 'REISSUE')
                            <div class="dvkh-info-label">Phiếu cũ</div>
                            <div class="dvkh-info-value">
                                @forelse($certificateRequest->reissueCertificates as $oldCertificate)
                                    <a href="{{ route('quality-certificates.show', $oldCertificate) }}" target="_blank" class="badge badge-light border mr-1 mb-1">
                                        {{ $oldCertificate->certificate_no }}
                                    </a>
                                @empty
                                    {{ $certificateRequest->reissueOfCertificate->certificate_no ?? '-' }}
                                @endforelse
                            </div>

                            <div class="dvkh-info-label">Lý do cấp lại</div>
                            <div class="dvkh-info-value">{{ $certificateRequest->reissue_reason ?: '-' }}</div>
                        @endif

                        <div class="dvkh-info-label">Ngày xuất hàng</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->delivery_date ? $certificateRequest->delivery_date->format('d/m/Y') : '-' }}</div>

                        <div class="dvkh-info-label">Số hóa đơn</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->invoice_no ?: '-' }}</div>

                        <div class="dvkh-info-label">Ký tươi</div>
                        <div class="dvkh-info-value">
                            @if($certificateRequest->require_hard_copy)
                                <span class="badge badge-warning">{{ $certificateRequest->hard_copy_quantity }} bản</span>
                            @else
                                Không
                            @endif
                        </div>

                        <div class="dvkh-info-label">Yêu cầu gấp</div>
                        <div class="dvkh-info-value">
                            @if($certificateRequest->is_urgent)
                                <span class="badge badge-danger"><i class="fas fa-bolt"></i> Có</span>
                                <div class="text-danger small mt-1">{{ $certificateRequest->urgentReason->name ?? 'Chưa chọn lý do' }}</div>
                            @else
                                Không
                            @endif
                        </div>

                        <div class="dvkh-info-label">Người tạo yêu cầu</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->requester_name ?: '-' }}</div>

                        <div class="dvkh-info-label">Tài khoản tạo</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->creator->name ?? '-' }}</div>

                        <div class="dvkh-info-label">Ngày tạo</div>
                        <div class="dvkh-info-value">{{ optional($certificateRequest->created_at)->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-info card-outline">
                <div class="card-header bg-white">
                    <h3 class="card-title"><i class="fas fa-building"></i> Khách hàng / Công trình</h3>
                </div>

                <div class="card-body">
                    <div class="dvkh-info-grid">
                        <div class="dvkh-info-label">Mã khách hàng</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->customer->customer_code ?? '-' }}</div>

                        <div class="dvkh-info-label">Khách hàng</div>
                        <div class="dvkh-info-value font-weight-bold">{{ $certificateRequest->customer->customer_name ?? '-' }}</div>

                        <div class="dvkh-info-label">Địa chỉ KH</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->customer->customer_address ?? '-' }}</div>

                        <div class="dvkh-info-label">Email</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->customer->email ?? '-' }}</div>

                        <div class="dvkh-info-label">Công trình</div>
                        <div class="dvkh-info-value font-weight-bold">{{ $certificateRequest->customer->project_name ?? '-' }}</div>

                        <div class="dvkh-info-label">Địa điểm</div>
                        <div class="dvkh-info-value">{{ $certificateRequest->customer->project_address ?? '-' }}</div>

                        <div class="dvkh-info-label">Ghi chú</div>
                        <div class="dvkh-info-value">{!! nl2br(e($certificateRequest->note ?: '-')) !!}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-box"></i> Danh sách sản phẩm</h3>
            <span class="badge badge-info">Tổng số: {{ $certificateRequest->details->count() }}</span>
        </div>

        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-hover mb-0 dvkh-products">
                <thead class="thead-light">
                    <tr>
                        <th class="col-center" style="width:60px">STT</th>
                        <th style="width:160px">Mã SP</th>
                        <th class="product-name">Tên sản phẩm</th>
                        <th class="col-center" style="width:140px">Kích thước</th>
                        <th>Yêu cầu kỹ thuật</th>
                        <th class="col-center" style="width:150px">Tiêu chuẩn</th>
                        <th class="col-center" style="width:120px">Số lượng</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($certificateRequest->details as $detail)
                        <tr>
                            <td class="col-center">{{ $loop->iteration }}</td>
                            <td>{{ $detail->product->product_code ?? '-' }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $detail->product->product_name ?? '-' }}</div>
                                <div class="text-muted small">{{ $detail->product->group->name ?? '' }}</div>
                            </td>
                            <td class="col-center">{{ $detail->product->nominal_size ?? '-' }}</td>
                            <td>{{ $detail->product->technical_requirements ?? '-' }}</td>
                            <td class="col-center">{{ $detail->product->qualityStandard->code ?? '-' }}</td>
                            <td class="col-center font-weight-bold">{{ rtrim(rtrim(number_format($detail->quantity, 2, '.', ''), '0'), '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-box-open fa-2x mb-2"></i>
                                <div>Yêu cầu chưa có sản phẩm.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('dvkh.process')
        @if($certificateRequest->status === 'WAIT_DVKH')
            <div class="modal fade" id="dvkhRejectModal" tabindex="-1">
                <div class="modal-dialog">
                    <form method="POST"
                          action="{{ route('dvkh.requests.reject', $certificateRequest) }}"
                          class="modal-content"
                          data-loading-message="Đang trả lại yêu cầu, vui lòng chờ...">
                        @csrf

                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-times-circle"></i> Trả lại yêu cầu</h5>
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>

                        <div class="modal-body">
                            <p>Yêu cầu: <strong>{{ $certificateRequest->request_no }}</strong></p>

                            <div class="form-group">
                                <label>Lý do trả lại <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="4" required placeholder="Nhập lý do để Trung tâm biết cần chỉnh sửa nội dung nào"></textarea>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>
                            <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Trả lại</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan
</div>
@stop
