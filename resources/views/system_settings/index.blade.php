@extends('adminlte::page')

@section('title', 'Cấu hình hệ thống')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/cncl-ui.css?v=20260611-3') }}">
    <style>
        .signature-designer {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, .85fr);
            gap: 16px;
        }

        .signature-preview-page {
            position: relative;
            min-height: 520px;
            border: 1px solid #d9dee7;
            background: linear-gradient(#fff 0 78%, #f6faf2 78% 100%);
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, .02);
        }

        .signature-preview-box {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            min-height: 86px;
            border: 1px dashed #0d6efd;
            background: rgba(13, 110, 253, .04);
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            overflow: hidden;
            text-align: center;
        }

        .signature-preview-image {
            width: 76px;
            height: 58px;
            object-fit: contain;
            flex: 0 0 auto;
        }

        .signature-preview-text {
            white-space: pre-line;
            line-height: 1.25;
            overflow-wrap: anywhere;
            font-weight: 600;
        }

        .signature-preview-bg {
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }

        @media (max-width: 991.98px) {
            .signature-designer {
                grid-template-columns: 1fr;
            }
        }
    </style>
@stop

@section('content_header')
<div>
    <h1 class="m-0">Cấu hình hệ thống</h1>
    <small class="text-muted">Thiết lập vận hành chung và mẫu chữ ký số VNPT SmartCA</small>
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
    $imagePath = old('smartca_signature_image_path', $signatureSettings['image_path'] ?? null);
    $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;
    $renderMode = (int) old('smartca_signature_render_mode', $signatureSettings['render_mode']);
    $signatureText = old('smartca_signature_text', $signatureSettings['signature_text']);
@endphp

<form method="POST" action="{{ route('system-settings.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-envelope"></i> Cấu hình email</h3>
        </div>
        <div class="card-body">
            <div class="custom-control custom-switch">
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
        </div>
    </div>

    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-signature"></i> Thiết kế mẫu chữ ký số</h3>
        </div>

        <div class="card-body">
            <div class="signature-designer">
                <div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="smartca_signature_render_mode">Kiểu hiển thị</label>
                            <select name="smartca_signature_render_mode" id="smartca_signature_render_mode" class="form-control @error('smartca_signature_render_mode') is-invalid @enderror">
                                <option value="0" {{ $renderMode === 0 ? 'selected' : '' }}>Chỉ chữ</option>
                                <option value="1" {{ $renderMode === 1 ? 'selected' : '' }}>Chữ + logo bên trái</option>
                                <option value="2" {{ $renderMode === 2 ? 'selected' : '' }}>Chỉ logo</option>
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
                        <textarea name="smartca_signature_text" id="smartca_signature_text" rows="4"
                                  class="form-control @error('smartca_signature_text') is-invalid @enderror">{{ $signatureText }}</textarea>
                        @error('smartca_signature_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">
                            Biến hỗ trợ: <code>{certificate_no}</code>, <code>{signed_by}</code>, <code>{signed_at}</code>.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="smartca_signature_page">Trang ký</label>
                            <input type="number" name="smartca_signature_page" id="smartca_signature_page"
                                   class="form-control @error('smartca_signature_page') is-invalid @enderror"
                                   min="1" max="50"
                                   value="{{ old('smartca_signature_page', $signatureSettings['page']) }}">
                            @error('smartca_signature_page')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-9">
                            <label for="smartca_signature_rectangle">Khung chữ ký PDF</label>
                            <div class="input-group">
                                <input type="text" name="smartca_signature_rectangle" id="smartca_signature_rectangle"
                                       class="form-control @error('smartca_signature_rectangle') is-invalid @enderror"
                                       value="{{ old('smartca_signature_rectangle', $signatureSettings['rectangle']) }}"
                                       placeholder="130,72,470,125">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="signatureBottomPreset">
                                        Vùng ký
                                    </button>
                                </div>
                                @error('smartca_signature_rectangle')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <small class="text-muted">Định dạng: <code>x1,y1,x2,y2</code>. A4 dọc thường rộng 595 point, cao 842 point. Vùng ký phía trên footer: <code>130,72,470,125</code>.</small>
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
                            <div class="mt-3 d-flex align-items-center">
                                <img src="{{ $imageUrl }}" alt="Mẫu chữ ký" class="border rounded mr-3" style="width:120px;height:72px;object-fit:contain;">
                                <div>
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

                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Xem trước</strong>
                        <span class="badge badge-light border">A4 mô phỏng</span>
                    </div>

                    <div class="signature-preview-page">
                        <div id="signaturePreviewBox" class="signature-preview-box">
                            <img id="signaturePreviewImage"
                                 src="{{ $imageUrl ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }}"
                                 alt="Ảnh chữ ký"
                                 class="signature-preview-image">
                            <div id="signaturePreviewText" class="signature-preview-text"></div>
                        </div>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Preview chỉ mô phỏng bố cục. File PDF ký thật sẽ do VNPT trả về sau bước <code>signExternal</code>.
                    </small>
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
@stop

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mode = document.getElementById('smartca_signature_render_mode');
        const text = document.getElementById('smartca_signature_text');
        const size = document.getElementById('smartca_signature_font_size');
        const color = document.getElementById('smartca_signature_font_color');
        const file = document.getElementById('smartca_signature_image');
        const preset = document.getElementById('signatureBottomPreset');
        const rectangle = document.getElementById('smartca_signature_rectangle');
        const previewBox = document.getElementById('signaturePreviewBox');
        const previewText = document.getElementById('signaturePreviewText');
        const previewImage = document.getElementById('signaturePreviewImage');

        function sampleText() {
            return text.value
                .replaceAll('{certificate_no}', 'CNCL-20260626-0001')
                .replaceAll('{signed_by}', 'Quản trị hệ thống')
                .replaceAll('{signed_at}', '26/06/2026 09:30:00');
        }

        function applyPreview() {
            const renderMode = parseInt(mode.value || '0', 10);
            previewText.textContent = sampleText();
            previewText.style.fontSize = `${size.value || 11}px`;
            previewText.style.color = color.value || '#000000';
            previewBox.classList.toggle('signature-preview-bg', renderMode === 4);
            previewBox.style.flexDirection = renderMode === 3 ? 'column' : 'row';

            if (renderMode === 0) {
                previewImage.style.display = 'none';
                previewText.style.display = 'block';
                previewBox.style.backgroundImage = '';
            } else if (renderMode === 2) {
                previewImage.style.display = 'block';
                previewText.style.display = 'none';
                previewBox.style.backgroundImage = '';
            } else {
                previewImage.style.display = renderMode === 4 ? 'none' : 'block';
                previewText.style.display = 'block';
                previewBox.style.backgroundImage = renderMode === 4 ? `url(${previewImage.src})` : '';
            }
        }

        [mode, text, size, color].forEach((element) => {
            element.addEventListener('input', applyPreview);
            element.addEventListener('change', applyPreview);
        });

        preset.addEventListener('click', function () {
            rectangle.value = '130,72,470,125';
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

        applyPreview();
    });
</script>
@stop
