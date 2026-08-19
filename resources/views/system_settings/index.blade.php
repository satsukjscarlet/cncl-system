@extends('adminlte::page')

@section('title', 'Cấu hình hệ thống')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: minmax(0, .95fr) minmax(420px, 1.05fr);
            gap: 16px;
        }

        .signature-preview-panel {
            position: sticky;
            top: 12px;
        }

        .signature-a4-preview {
            --scale: .62;
            position: relative;
            width: calc(595px * var(--scale));
            height: calc(842px * var(--scale));
            max-width: 100%;
            margin: 0 auto;
            border: 1px solid #cfd6df;
            background:
                linear-gradient(180deg, rgba(255,255,255,0) 0 82%, rgba(133, 196, 68, .13) 82% 100%),
                #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, .12);
            overflow: hidden;
        }

        .signature-a4-header {
            position: absolute;
            left: 20px;
            right: 20px;
            top: 18px;
            height: 64px;
            border-bottom: 1px solid #eef1f5;
            color: #c51f1f;
            font-family: "Times New Roman", serif;
            font-weight: 700;
            text-align: center;
            line-height: 1.2;
            padding-top: 8px;
        }

        .signature-a4-table {
            position: absolute;
            left: 20px;
            right: 20px;
            top: 170px;
            height: 230px;
            border: 1px solid #d8dee6;
            background: repeating-linear-gradient(
                to bottom,
                #fff,
                #fff 26px,
                #f3f6f9 27px
            );
        }

        .signature-a4-footer {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 16px;
            height: 62px;
            background: #86c844;
            color: #fff;
            font-size: 8px;
            padding: 8px 14px;
        }

        .signature-preview-box {
            position: absolute;
            border: 2px dashed #0d6efd;
            background: rgba(13, 110, 253, .06);
            color: #b00020;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 6px;
            text-align: center;
            overflow: hidden;
            line-height: 1.2;
        }

        .signature-preview-box.disabled {
            display: none;
        }

        .signature-preview-image {
            width: 54px;
            height: 42px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .signature-preview-check {
            position: absolute;
            left: 50%;
            bottom: -7px;
            width: 96px;
            height: 72px;
            background: transparent;
            color: #18a957;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 74px;
            font-weight: 900;
            line-height: 1;
            text-shadow: 0 1px 2px rgba(24, 169, 87, .25);
            transform: translateX(-50%) rotate(-7deg);
            z-index: 1;
            pointer-events: none;
        }

        .signature-preview-text {
            position: relative;
            z-index: 2;
            width: 100%;
            padding: 0 8px;
            white-space: pre-line;
            overflow-wrap: anywhere;
            font-weight: 700;
            text-shadow: 0 1px 0 rgba(255, 255, 255, .75);
        }

        .signature-preview-bg {
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .preset-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .field-hint {
            color: #6c757d;
            font-size: 12px;
        }

        @media (max-width: 1199.98px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .signature-preview-panel {
                position: static;
            }
        }
    </style>
@stop

@section('content_header')
<div>
    <h1 class="m-0">Cấu hình hệ thống</h1>
    <small class="text-muted">Thiết lập email, dữ liệu test và mẫu chữ ký số VNPT SmartCA</small>
</div>
@stop

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> Vui lòng kiểm tra lại dữ liệu cấu hình.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@php
    $imagePath = $signatureSettings['image_path'] ?? null;
    $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;
    $visible = old('smartca_signature_visible', $signatureSettings['visible'] ?? true);
    $showCheck = old('smartca_signature_show_check', $signatureSettings['show_check'] ?? true);
    $renderMode = (int) old('smartca_signature_render_mode', $signatureSettings['render_mode']);
    $signatureText = old('smartca_signature_text', $signatureSettings['signature_text']);
    $pageMode = old('smartca_signature_page_mode', $signatureSettings['page_mode'] ?? 'last');
    $rectangle = old('smartca_signature_rectangle', $signatureSettings['rectangle'] ?? '315,150,565,220');
@endphp

<form method="POST" action="{{ route('system-settings.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="card card-primary card-outline">
        <div class="card-header bg-white">
            <h3 class="card-title"><i class="fas fa-envelope"></i> Cấu hình email gửi phiếu</h3>
        </div>
        <div class="card-body">
            <div class="custom-control custom-switch mb-3">
                <input type="checkbox"
                       name="auto_send_email_after_sign"
                       value="1"
                       class="custom-control-input"
                       id="auto_send_email_after_sign"
                       {{ old('auto_send_email_after_sign', $autoSendEmail) ? 'checked' : '' }}>
                <label class="custom-control-label" for="auto_send_email_after_sign">
                    Tự động gửi email sau khi ký số/phát hành phiếu CNCL
                </label>
            </div>

            <div class="custom-control custom-switch mb-3">
                <input type="checkbox"
                       name="certificate_mail_cc_customer_email"
                       value="1"
                       class="custom-control-input"
                       id="certificate_mail_cc_customer_email"
                       {{ old('certificate_mail_cc_customer_email', $mailSettings['cc_customer_email'] ?? true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="certificate_mail_cc_customer_email">
                    CC email khách hàng nếu khách hàng có email
                </label>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="certificate_mail_cc_dvkh">Email CC DVKH</label>
                    <textarea name="certificate_mail_cc_dvkh" id="certificate_mail_cc_dvkh" rows="4"
                              class="form-control @error('certificate_mail_cc_dvkh') is-invalid @enderror"
                              placeholder="dvkh@example.com">{{ old('certificate_mail_cc_dvkh', $mailSettings['cc_dvkh'] ?? '') }}</textarea>
                    @error('certificate_mail_cc_dvkh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group col-md-4">
                    <label for="certificate_mail_cc_ptn">Email CC PTN</label>
                    <textarea name="certificate_mail_cc_ptn" id="certificate_mail_cc_ptn" rows="4"
                              class="form-control @error('certificate_mail_cc_ptn') is-invalid @enderror"
                              placeholder="ptn@example.com">{{ old('certificate_mail_cc_ptn', $mailSettings['cc_ptn'] ?? '') }}</textarea>
                    @error('certificate_mail_cc_ptn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="form-group col-md-4">
                    <label for="certificate_mail_cc_extra">Email CC bổ sung</label>
                    <textarea name="certificate_mail_cc_extra" id="certificate_mail_cc_extra" rows="4"
                              class="form-control @error('certificate_mail_cc_extra') is-invalid @enderror"
                              placeholder="email1@example.com&#10;email2@example.com">{{ old('certificate_mail_cc_extra', $mailSettings['cc_extra'] ?? '') }}</textarea>
                    @error('certificate_mail_cc_extra')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="field-hint">
                Có thể nhập mỗi email một dòng hoặc ngăn cách bằng dấu phẩy/chấm phẩy. Email nhận chính vẫn là email tài khoản Trung tâm phân phối tạo yêu cầu.
            </div>
        </div>
    </div>

    <div class="card card-info card-outline">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-signature"></i> Thiết kế chữ ký số trên PDF</h3>
            <span class="badge badge-light border">Khổ A4: 595 x 842 point</span>
        </div>

        <div class="card-body">
            <div class="settings-grid">
                <div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox"
                               name="smartca_signature_visible"
                               value="1"
                               class="custom-control-input"
                               id="smartca_signature_visible"
                               {{ $visible ? 'checked' : '' }}>
                        <label class="custom-control-label" for="smartca_signature_visible">
                            Hiển thị chữ ký số trên file PDF
                        </label>
                        <div class="field-hint mt-1">
                            Nếu tắt, PDF vẫn được ký số nhưng không có khung chữ ký nhìn thấy trên trang.
                        </div>
                    </div>

                    <div class="custom-control custom-switch mb-3">
                        <input type="checkbox"
                               name="smartca_signature_show_check"
                               value="1"
                               class="custom-control-input"
                               id="smartca_signature_show_check"
                               {{ $showCheck ? 'checked' : '' }}>
                        <label class="custom-control-label" for="smartca_signature_show_check">
                            Hiển thị dấu tích xanh xác nhận ký điện tử
                        </label>
                        <div class="field-hint mt-1">
                            Dấu tích sẽ được đưa vào ảnh chữ ký gửi sang VNPT SmartCA. Nếu có ảnh/logo riêng, hệ thống sẽ ghép dấu tích vào ảnh khi máy chủ hỗ trợ thư viện GD.
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="smartca_signature_render_mode">Kiểu hiển thị</label>
                            <select name="smartca_signature_render_mode" id="smartca_signature_render_mode"
                                    class="form-control @error('smartca_signature_render_mode') is-invalid @enderror">
                                <option value="0" {{ $renderMode === 0 ? 'selected' : '' }}>Chỉ chữ</option>
                                <option value="1" {{ $renderMode === 1 ? 'selected' : '' }}>Chữ + logo bên trái</option>
                                <option value="2" {{ $renderMode === 2 ? 'selected' : '' }}>Chỉ logo/ảnh</option>
                                <option value="3" {{ $renderMode === 3 ? 'selected' : '' }}>Chữ + logo phía trên</option>
                                <option value="4" {{ $renderMode === 4 ? 'selected' : '' }}>Chữ + ảnh nền toàn khung</option>
                            </select>
                            @error('smartca_signature_render_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-3">
                            <label for="smartca_signature_font_size">Cỡ chữ</label>
                            <input type="number" name="smartca_signature_font_size" id="smartca_signature_font_size"
                                   class="form-control @error('smartca_signature_font_size') is-invalid @enderror"
                                   min="8" max="24"
                                   value="{{ old('smartca_signature_font_size', $signatureSettings['font_size']) }}">
                            @error('smartca_signature_font_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-3">
                            <label for="smartca_signature_font_color">Màu chữ</label>
                            <input type="color" name="smartca_signature_font_color" id="smartca_signature_font_color"
                                   class="form-control @error('smartca_signature_font_color') is-invalid @enderror"
                                   value="{{ old('smartca_signature_font_color', $signatureSettings['font_color']) }}">
                            @error('smartca_signature_font_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="smartca_signature_text">Nội dung chữ ký</label>
                        <textarea name="smartca_signature_text" id="smartca_signature_text" rows="5"
                                  class="form-control @error('smartca_signature_text') is-invalid @enderror">{{ $signatureText }}</textarea>
                        @error('smartca_signature_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="field-hint mt-1">
                            Biến hỗ trợ: <code>{certificate_no}</code>, <code>{signed_by}</code>, <code>{signed_at}</code>.
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="smartca_signature_page_mode">Trang đặt chữ ký</label>
                            <select name="smartca_signature_page_mode" id="smartca_signature_page_mode"
                                    class="form-control @error('smartca_signature_page_mode') is-invalid @enderror">
                                <option value="last" {{ $pageMode === 'last' ? 'selected' : '' }}>Trang cuối</option>
                                <option value="fixed" {{ $pageMode === 'fixed' ? 'selected' : '' }}>Trang cụ thể</option>
                            </select>
                            @error('smartca_signature_page_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-3">
                            <label for="smartca_signature_page">Số trang</label>
                            <input type="number" name="smartca_signature_page" id="smartca_signature_page"
                                   class="form-control @error('smartca_signature_page') is-invalid @enderror"
                                   min="1" max="50"
                                   value="{{ old('smartca_signature_page', $signatureSettings['page']) }}">
                            @error('smartca_signature_page')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="field-hint">Chỉ dùng khi chọn trang cụ thể.</div>
                        </div>

                        <div class="form-group col-md-5">
                            <label for="smartca_signature_rectangle">Tọa độ khung</label>
                            <input type="text" name="smartca_signature_rectangle" id="smartca_signature_rectangle"
                                   class="form-control @error('smartca_signature_rectangle') is-invalid @enderror"
                                   value="{{ $rectangle }}"
                                   placeholder="315,150,565,220">
                            @error('smartca_signature_rectangle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="field-hint">Dạng <code>x1,y1,x2,y2</code>. Gốc tọa độ theo PDF, tính bằng point.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Vị trí nhanh</label>
                        <div class="preset-buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-rect="315,150,565,220">
                                Trên footer, bên phải
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-rect="200,150,395,220">
                                Trên footer, giữa
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-rect="30,150,280,220">
                                Trên footer, bên trái
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-rect="360,690,565,760">
                                Góc phải phía trên
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="smartca_signature_image">Ảnh/logo chữ ký</label>
                        <div class="custom-file">
                            <input type="file" name="smartca_signature_image" id="smartca_signature_image"
                                   class="custom-file-input @error('smartca_signature_image') is-invalid @enderror"
                                   accept="image/png,image/jpeg">
                            <label class="custom-file-label" for="smartca_signature_image">Chọn ảnh PNG/JPG tối đa 2MB</label>
                        </div>
                        @error('smartca_signature_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                        @if($imageUrl)
                            <div class="mt-3 d-flex align-items-center flex-wrap">
                                <img src="{{ $imageUrl }}" alt="Mẫu chữ ký" class="border rounded mr-3 mb-2" style="width:130px;height:78px;object-fit:contain;">
                                <div class="mb-2">
                                    <div class="small text-muted mb-1">Đã lưu: <code>{{ $imagePath }}</code></div>
                                    <a href="{{ $imageUrl }}" target="_blank" class="btn btn-sm btn-outline-info mr-2">
                                        <i class="fas fa-external-link-alt"></i> Mở ảnh
                                    </a>
                                    <div class="custom-control custom-checkbox d-inline-block">
                                        <input type="checkbox" class="custom-control-input" id="remove_smartca_signature_image" name="remove_smartca_signature_image" value="1">
                                        <label class="custom-control-label" for="remove_smartca_signature_image">Xóa ảnh hiện tại</label>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="signature-preview-panel">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Xem trước vị trí trên A4</strong>
                        <span id="signaturePreviewSize" class="badge badge-light border"></span>
                    </div>

                    <div class="signature-a4-preview" id="signatureA4Preview">
                        <div class="signature-a4-header">
                            CÔNG TY CỔ PHẦN NHỰA THIẾU NIÊN TIỀN PHONG<br>
                            PHIẾU CHỨNG NHẬN CHẤT LƯỢNG
                        </div>
                        <div class="signature-a4-table"></div>
                        <div class="signature-a4-footer">
                            Website: www.nhuatienphong.vn<br>
                            Trụ sở chính - Head office<br>
                            Số 222 Mạc Đăng Doanh, P. Hưng Đạo, TP. Hải Phòng, Việt Nam
                        </div>
                        <div id="signaturePreviewBox" class="signature-preview-box">
                            <span id="signaturePreviewCheck" class="signature-preview-check">
                                <i class="fas fa-check"></i>
                            </span>
                            <img id="signaturePreviewImage"
                                 src="{{ $imageUrl ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }}"
                                 alt="Ảnh chữ ký"
                                 class="signature-preview-image">
                            <div id="signaturePreviewText" class="signature-preview-text"></div>
                        </div>
                    </div>

                    <div class="alert alert-light border mt-3 mb-0">
                        <div class="font-weight-bold mb-1">Gợi ý dùng thực tế</div>
                        <div class="small text-muted">
                            Với phiếu nhiều trang, nên chọn <strong>Trang cuối</strong> và vị trí <strong>Trên footer, bên phải</strong> để tránh che bảng sản phẩm.
                            Nếu chọn kiểu có ảnh, ảnh nên là PNG nền trong suốt, ngang rộng hơn cao.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">
                <i class="fas fa-save"></i> Lưu cấu hình
            </button>
        </div>
    </div>
</form>

@if(auth()->user()?->hasRole('Admin'))
    <div class="card card-warning card-outline">
        <div class="card-header bg-white">
            <h3 class="card-title"><i class="fas fa-vial"></i> Dữ liệu test nghiệm thu</h3>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Khu vực này chỉ dành cho Admin. Dữ liệu test dùng prefix <code>TEST-SLA</code>, phục vụ kiểm thử workflow, báo cáo, SLA và hàng đợi ký.
            </p>

            <div class="d-flex flex-wrap align-items-center">
                <form method="POST" action="{{ route('system-settings.test-data.seed') }}" class="mr-2 mb-2"
                      onsubmit="return confirm('Tạo lại dữ liệu test TEST-SLA? Dữ liệu test cũ cùng prefix sẽ được làm mới.');">
                    @csrf
                    <button class="btn btn-warning">
                        <i class="fas fa-plus-circle"></i> Thêm lại data test
                    </button>
                </form>

                <form method="POST" action="{{ route('system-settings.test-data.clear') }}" class="mb-2"
                      onsubmit="return confirm('Xóa toàn bộ dữ liệu test TEST-SLA? Thao tác này không xóa dữ liệu thật nhưng không thể hoàn tác dữ liệu test đã xóa.');">
                    @csrf
                    <button class="btn btn-outline-danger">
                        <i class="fas fa-trash-alt"></i> Xóa data test
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const A4_WIDTH = 595;
        const A4_HEIGHT = 842;
        const visible = document.getElementById('smartca_signature_visible');
        const showCheck = document.getElementById('smartca_signature_show_check');
        const mode = document.getElementById('smartca_signature_render_mode');
        const text = document.getElementById('smartca_signature_text');
        const size = document.getElementById('smartca_signature_font_size');
        const color = document.getElementById('smartca_signature_font_color');
        const file = document.getElementById('smartca_signature_image');
        const rectangle = document.getElementById('smartca_signature_rectangle');
        const pageMode = document.getElementById('smartca_signature_page_mode');
        const page = document.getElementById('smartca_signature_page');
        const previewBox = document.getElementById('signaturePreviewBox');
        const previewCheck = document.getElementById('signaturePreviewCheck');
        const previewText = document.getElementById('signaturePreviewText');
        const previewImage = document.getElementById('signaturePreviewImage');
        const previewSize = document.getElementById('signaturePreviewSize');
        const previewPage = document.getElementById('signatureA4Preview');

        function sampleText() {
            return text.value
                .replaceAll('{certificate_no}', 'CNCL-20260626-0001')
                .replaceAll('{signed_by}', 'Trưởng PTN')
                .replaceAll('{signed_at}', '26/06/2026 09:30:00');
        }

        function parseRectangle() {
            const parts = rectangle.value.split(',').map(part => parseInt(part.trim(), 10));
            if (parts.length !== 4 || parts.some(Number.isNaN)) {
                return null;
            }
            const [x1, y1, x2, y2] = parts;
            if (x1 >= x2 || y1 >= y2 || x1 < 0 || y1 < 0 || x2 > A4_WIDTH || y2 > A4_HEIGHT) {
                return null;
            }
            return { x1, y1, x2, y2, width: x2 - x1, height: y2 - y1 };
        }

        function applyRectangle(rect) {
            const scale = previewPage.clientWidth / A4_WIDTH;
            previewBox.style.left = `${rect.x1 * scale}px`;
            previewBox.style.width = `${rect.width * scale}px`;
            previewBox.style.height = `${rect.height * scale}px`;
            previewBox.style.bottom = `${rect.y1 * scale}px`;
            previewSize.textContent = `${rect.width} x ${rect.height} pt`;
        }

        function applyMode(renderMode, isVisible) {
            const hasGreenCheck = isVisible && showCheck.checked;
            const effectiveMode = hasGreenCheck ? 4 : renderMode;

            previewBox.classList.toggle('signature-preview-bg', effectiveMode === 4);
            previewBox.style.flexDirection = effectiveMode === 3 ? 'column' : 'row';
            previewCheck.style.display = hasGreenCheck ? 'inline-flex' : 'none';

            if (hasGreenCheck) {
                previewImage.style.display = 'none';
                previewText.style.display = 'block';
                previewBox.style.backgroundImage = '';
                return;
            }

            if (effectiveMode === 0) {
                previewImage.style.display = 'none';
                previewText.style.display = 'block';
                previewBox.style.backgroundImage = '';
                return;
            }

            if (effectiveMode === 2) {
                previewImage.style.display = 'block';
                previewText.style.display = 'none';
                previewBox.style.backgroundImage = '';
                return;
            }

            previewImage.style.display = effectiveMode === 4 ? 'none' : 'block';
            previewText.style.display = 'block';
            previewBox.style.backgroundImage = effectiveMode === 4 ? `url(${previewImage.src})` : '';
        }

        function applyPreview() {
            const rect = parseRectangle();
            const isVisible = visible.checked;
            const renderMode = parseInt(mode.value || '0', 10);

            previewBox.classList.toggle('disabled', !isVisible || !rect);
            rectangle.classList.toggle('is-invalid', !rect);
            page.readOnly = pageMode.value !== 'fixed';
            page.classList.toggle('bg-light', pageMode.value !== 'fixed');

            if (!rect) {
                previewSize.textContent = 'Tọa độ chưa hợp lệ';
                return;
            }

            previewText.textContent = sampleText();
            previewText.style.fontSize = `${Math.max(8, Math.min(24, parseInt(size.value || '11', 10))) * .72}px`;
            previewText.style.color = color.value || '#b00020';
            applyRectangle(rect);
            applyMode(renderMode, isVisible);
        }

        document.querySelectorAll('[data-rect]').forEach(button => {
            button.addEventListener('click', function () {
                rectangle.value = this.getAttribute('data-rect');
                applyPreview();
            });
        });

        [visible, showCheck, mode, text, size, color, rectangle, pageMode, page].forEach(element => {
            element.addEventListener('input', applyPreview);
            element.addEventListener('change', applyPreview);
        });

        file.addEventListener('change', function () {
            const selected = file.files && file.files[0];
            const label = file.nextElementSibling;
            label.textContent = selected ? selected.name : 'Chọn ảnh PNG/JPG tối đa 2MB';

            if (!selected) {
                applyPreview();
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                previewImage.src = event.target.result;
                applyPreview();
            };
            reader.readAsDataURL(selected);
        });

        window.addEventListener('resize', applyPreview);
        applyPreview();
    });
</script>
@stop
