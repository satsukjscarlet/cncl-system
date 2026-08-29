@extends('adminlte::page')

@section('title', 'Phiếu chờ gửi ký')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .ready-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            margin-bottom: 14px;
        }

        .ready-summary-card {
            background: #fff;
            border: 1px solid #dde3ea;
            border-left: 4px solid #17a2b8;
            border-radius: 6px;
            padding: 14px 16px;
        }

        .ready-summary-card strong {
            display: block;
            font-size: 26px;
            line-height: 1;
        }

        .ready-summary-card span {
            color: #5b6675;
            font-size: 13px;
        }

        .ready-toolbar {
            align-items: end;
            display: grid;
            gap: 12px;
            grid-template-columns: minmax(260px, 1.5fr) minmax(220px, 1fr) 150px 210px;
        }

        .ready-table {
            min-width: 1180px;
        }

        .ready-actions {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: center;
        }

        @media (max-width: 1199.98px) {
            .ready-toolbar {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .ready-toolbar {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
<div class="d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1 class="m-0">Phiếu chờ gửi ký</h1>
        <small class="text-muted">Quản lý các phiếu đã được Trưởng PTN duyệt nội dung và sẵn sàng gửi VNPT SmartCA.</small>
    </div>

    <div class="mt-2 mt-md-0">
        <a href="{{ route('quality-certificates.signing-queue', ['status' => 'WAIT_APPROVAL']) }}" class="btn btn-outline-primary">
            <i class="fas fa-user-check"></i> Phiếu chờ duyệt
        </a>
        <a href="{{ route('quality-certificates.signing-queue', ['status' => 'PENDING']) }}" class="btn btn-outline-secondary">
            <i class="fas fa-mobile-alt"></i> Đang chờ app
        </a>
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

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="ready-summary">
    <div class="ready-summary-card">
        <strong>{{ $readyCount }}</strong>
        <span>Phiếu đang chờ gửi ký</span>
    </div>
    <div class="ready-summary-card">
        <strong>10</strong>
        <span>Giới hạn đề xuất mỗi lần gửi ký hàng loạt</span>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET">
            <div class="ready-toolbar">
                <div class="form-group mb-0">
                    <label for="keyword">Từ khóa</label>
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

                <div class="form-group mb-0">
                    <label for="distribution_center_id">Trung tâm</label>
                    <select id="distribution_center_id" name="distribution_center_id" class="form-control select2">
                        <option value="">Tất cả trung tâm</option>
                        @foreach($centers as $center)
                            <option value="{{ $center->id }}" {{ request('distribution_center_id') == $center->id ? 'selected' : '' }}>
                                {{ $center->code }} - {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-0">
                    <label>&nbsp;</label>
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" name="urgent" value="1" class="custom-control-input" id="urgentOnly" {{ request()->boolean('urgent') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="urgentOnly">Chỉ phiếu gấp</label>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label>&nbsp;</label>
                    <div class="d-flex" style="gap:8px">
                        <button class="btn btn-primary">
                            <i class="fas fa-search"></i> Lọc
                        </button>
                        <a href="{{ route('quality-certificates.ready-to-sign') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-sync"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <div>
            <h3 class="card-title"><i class="fas fa-paper-plane"></i> Danh sách chờ gửi ký</h3>
            <div class="text-muted small mt-1">Chỉ gồm các phiếu đã duyệt nội dung, chưa gửi VNPT SmartCA.</div>
        </div>
        <div class="card-tools">
            @if($certificates->count() > 0)
                <form id="bulkSignForm"
                      action="{{ route('quality-certificates.bulk-sign') }}"
                      method="POST"
                      class="d-inline"
                      data-loading-lock
                      data-loading-message="Đang gửi ký hàng loạt sang VNPT SmartCA. Vui lòng chờ..."
                      onsubmit="var checked = document.querySelectorAll('.ready-checkbox:checked').length; if (!checked) { alert('Vui lòng chọn ít nhất một phiếu để gửi ký.'); return false; } if (checked > 10) { alert('Mỗi lần chỉ gửi tối đa 10 phiếu để kịp xác nhận trên app VNPT SmartCA.'); return false; } if (!confirm('Gửi ' + checked + ' phiếu đã chọn sang VNPT SmartCA?')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success mr-2">
                        <i class="fas fa-file-signature"></i> Gửi ký phiếu đã chọn
                    </button>
                </form>
            @endif
            <span class="badge badge-info">Tổng số: {{ $certificates->total() }}</span>
        </div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered ready-table mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width:42px" class="text-center"><input type="checkbox" id="checkAllReady"></th>
                    <th style="width:60px">STT</th>
                    <th style="width:170px">Số phiếu</th>
                    <th style="width:170px">Yêu cầu</th>
                    <th>Khách hàng / Công trình</th>
                    <th style="width:160px">Trung tâm</th>
                    <th style="width:150px">PTN lập</th>
                    <th style="width:145px">Ngày duyệt</th>
                    <th style="width:190px" class="text-center">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($certificates as $certificate)
                    <tr>
                        <td class="text-center">
                            <input type="checkbox"
                                   name="certificate_ids[]"
                                   value="{{ $certificate->id }}"
                                   form="bulkSignForm"
                                   class="ready-checkbox">
                        </td>
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
                        <td>{{ optional($certificate->updated_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-center">
                            <div class="ready-actions">
                                <a href="{{ route('quality-certificates.show', $certificate) }}" class="btn btn-sm btn-info" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('quality-certificates.pdf', $certificate) }}" target="_blank" class="btn btn-sm btn-secondary" title="Xem PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <form action="{{ route('quality-certificates.sign', $certificate) }}"
                                      method="POST"
                                      class="d-inline"
                                      data-loading-lock
                                      data-loading-message="Đang gửi yêu cầu ký sang VNPT SmartCA. Vui lòng chờ..."
                                      onsubmit="if (!confirm('Gửi yêu cầu ký phiếu này sang VNPT SmartCA?')) return false; window.CnclLoading && window.CnclLoading.show(this.getAttribute('data-loading-message')); return true;">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Gửi ký SmartCA">
                                        <i class="fas fa-file-signature"></i>
                                    </button>
                                </form>
                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        title="Trả lại PTN/DVKH"
                                        data-toggle="modal"
                                        data-target="#rejectSignatureModal{{ $certificate->id }}">
                                    <i class="fas fa-undo"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <br>
                            Không có phiếu nào đang chờ gửi ký.
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
                    <h5 class="modal-title"><i class="fas fa-undo"></i> Trả lại phiếu</h5>
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
@endforeach
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var checkAll = document.getElementById('checkAllReady');

            if (!checkAll) {
                return;
            }

            checkAll.addEventListener('change', function () {
                document.querySelectorAll('.ready-checkbox').forEach(function (checkbox) {
                    checkbox.checked = checkAll.checked;
                });
            });
        });
    </script>
@stop
