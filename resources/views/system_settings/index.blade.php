@extends('adminlte::page')

@section('title', 'Cáº¥u hÃ¬nh há»‡ thá»‘ng')

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
    <h1 class="m-0">Cáº¥u hÃ¬nh há»‡ thá»‘ng</h1>
    <small class="text-muted">Thiáº¿t láº­p váº­n hÃ nh chung vÃ  máº«u chá»¯ kÃ½ sá»‘ VNPT SmartCA</small>
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
        <i class="fas fa-exclamation-triangle"></i> Vui lÃ²ng kiá»ƒm tra láº¡i dá»¯ liá»‡u cáº¥u hÃ¬nh.
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@php
    $imagePath = old('smartca_signature_image_path', $signatureSettings['image_path'] ?? null);
    $imageUrl = $imagePath ? asset('storage/' . $imagePath) : null;
    $renderMode = (int) old('smartca_signature_render_mode', $signatureSettings['render_mode']);
    $signatureText = old('smartca_signature_text', $signatureSettings['signature_text']);
    $pageMode = old('smartca_signature_page_mode', $signatureSettings['page_mode'] ?? 'last');
@endphp

<form method="POST" action="{{ route('system-settings.update') }}" enctype="multipart/form-data">
    @csrf

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-envelope"></i> Cáº¥u hÃ¬nh email</h3>
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
                    Tá»± Ä‘á»™ng gá»­i email sau khi kÃ½ sá»‘/phÃ¡t hÃ nh phiáº¿u CNCL
                </label>
            </div>
        </div>
    </div>

    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-signature"></i> Thiáº¿t káº¿ máº«u chá»¯ kÃ½ sá»‘</h3>
        </div>

        <div class="card-body">
            <div class="signature-designer">
                <div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="smartca_signature_render_mode">Kiá»ƒu hiá»ƒn thá»‹</label>
                            <select name="smartca_signature_render_mode" id="smartca_signature_render_mode" class="form-control @error('smartca_signature_render_mode') is-invalid @enderror">
                                <option value="0" {{ $renderMode === 0 ? 'selected' : '' }}>Chá»‰ chá»¯</option>
                                <option value="1" {{ $renderMode === 1 ? 'selected' : '' }}>Chá»¯ + logo bÃªn trÃ¡i</option>
                                <option value="2" {{ $renderMode === 2 ? 'selected' : '' }}>Chá»‰ logo</option>
                                <option value="3" {{ $renderMode === 3 ? 'selected' : '' }}>Chá»¯ + logo phÃ­a trÃªn</option>
                                <option value="4" {{ $renderMode === 4 ? 'selected' : '' }}>Chá»¯ + áº£nh ná»n toÃ n khung</option>
                            </select>
                            @error('smartca_signature_render_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-3">
                            <label for="smartca_signature_font_size">Cá»¡ chá»¯</label>
                            <input type="number" name="smartca_signature_font_size" id="smartca_signature_font_size"
                                   class="form-control @error('smartca_signature_font_size') is-invalid @enderror"
                                   min="8" max="24"
                                   value="{{ old('smartca_signature_font_size', $signatureSettings['font_size']) }}">
                            @error('smartca_signature_font_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-3">
                            <label for="smartca_signature_font_color">MÃ u chá»¯</label>
                            <input type="color" name="smartca_signature_font_color" id="smartca_signature_font_color"
                                   class="form-control @error('smartca_signature_font_color') is-invalid @enderror"
                                   value="{{ old('smartca_signature_font_color', $signatureSettings['font_color']) }}">
                            @error('smartca_signature_font_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="smartca_signature_text">Ná»™i dung chá»¯ kÃ½</label>
                        <textarea name="smartca_signature_text" id="smartca_signature_text" rows="4"
                                  class="form-control @error('smartca_signature_text') is-invalid @enderror">{{ $signatureText }}</textarea>
                        @error('smartca_signature_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="text-muted">
                            Biáº¿n há»— trá»£: <code>{certificate_no}</code>, <code>{signed_by}</code>, <code>{signed_at}</code>.
                        </small>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="smartca_signature_page_mode">V&#7883; tr&#237; trang k&#253;</label>
                            <select name="smartca_signature_page_mode" id="smartca_signature_page_mode"
                                    class="form-control @error('smartca_signature_page_mode') is-invalid @enderror">
                                <option value="last" {{ $pageMode === 'last' ? 'selected' : '' }}>Trang cu&#7889;i</option>
                                <option value="fixed" {{ $pageMode === 'fixed' ? 'selected' : '' }}>Trang c&#7909; th&#7875;</option>
                            </select>
                            @error('smartca_signature_page_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group col-md-2">
                            <label for="smartca_signature_page">Trang k&#253;</label>
                            <input type="number" name="smartca_signature_page" id="smartca_signature_page"
                                   class="form-control @error('smartca_signature_page') is-invalid @enderror"
                                   min="1" max="50"
                                   value="{{ old('smartca_signature_page', $signatureSettings['page']) }}">
                            @error('smartca_signature_page')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Ch&#7881; d&#249;ng khi ch&#7885;n trang c&#7909; th&#7875;.</small>
                        </div>
                        <div class="form-group col-md-7">
                            <label for="smartca_signature_rectangle">Khung chá»¯ kÃ½ PDF</label>
                            <div class="input-group">
                                <input type="text" name="smartca_signature_rectangle" id="smartca_signature_rectangle"
                                       class="form-control @error('smartca_signature_rectangle') is-invalid @enderror"
                                       value="{{ old('smartca_signature_rectangle', $signatureSettings['rectangle']) }}"
                                       placeholder="315,150,565,220">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="signatureBottomPreset">
                                        VÃ¹ng kÃ½
                                    </button>
                                </div>
                                @error('smartca_signature_rectangle')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <small class="text-muted">Äá»‹nh dáº¡ng: <code>x1,y1,x2,y2</code>. A4 dá»c thÆ°á»ng rá»™ng 595 point, cao 842 point. VÃ¹ng kÃ½ phÃ­a trÃªn footer: <code>315,150,565,220</code>.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="smartca_signature_image">áº¢nh/logo chá»¯ kÃ½</label>
                        <div class="custom-file">
                            <input type="file" name="smartca_signature_image" id="smartca_signature_image"
                                   class="custom-file-input @error('smartca_signature_image') is-invalid @enderror"
                                   accept="image/png,image/jpeg">
                            <label class="custom-file-label" for="smartca_signature_image">Chá»n áº£nh PNG/JPG tá»‘i Ä‘a 2MB</label>
                        </div>
                        @error('smartca_signature_image')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

                        @if($imageUrl)
                            <div class="mt-3 d-flex align-items-center">
                                <img src="{{ $imageUrl }}" alt="Máº«u chá»¯ kÃ½" class="border rounded mr-3" style="width:120px;height:72px;object-fit:contain;">
                                <div>
                                    <div class="small text-muted mb-1">ÄÃ£ lÆ°u: <code>{{ $imagePath }}</code></div>
                                    <a href="{{ $imageUrl }}" target="_blank" class="btn btn-sm btn-outline-info mr-2">
                                        <i class="fas fa-external-link-alt"></i> Má»Ÿ áº£nh
                                    </a>
                                    <div class="custom-control custom-checkbox d-inline-block">
                                        <input type="checkbox" class="custom-control-input" id="remove_smartca_signature_image" name="remove_smartca_signature_image" value="1">
                                        <label class="custom-control-label" for="remove_smartca_signature_image">XÃ³a áº£nh hiá»‡n táº¡i</label>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>Xem trÆ°á»›c</strong>
                        <span class="badge badge-light border">A4 mÃ´ phá»ng</span>
                    </div>

                    <div class="signature-preview-page">
                        <div id="signaturePreviewBox" class="signature-preview-box">
                            <img id="signaturePreviewImage"
                                 src="{{ $imageUrl ?: 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' }}"
                                 alt="áº¢nh chá»¯ kÃ½"
                                 class="signature-preview-image">
                            <div id="signaturePreviewText" class="signature-preview-text"></div>
                        </div>
                    </div>

                    <small class="text-muted d-block mt-2">
                        Preview chá»‰ mÃ´ phá»ng bá»‘ cá»¥c. File PDF kÃ½ tháº­t sáº½ do VNPT tráº£ vá» sau bÆ°á»›c <code>signExternal</code>.
                    </small>
                </div>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-end">
            <button class="btn btn-primary">
                <i class="fas fa-save"></i> LÆ°u cáº¥u hÃ¬nh
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
                .replaceAll('{signed_by}', 'Quáº£n trá»‹ há»‡ thá»‘ng')
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
            rectangle.value = '315,150,565,220';
        });

        file.addEventListener('change', function () {
            const selected = file.files && file.files[0];
            const label = file.nextElementSibling;
            label.textContent = selected ? selected.name : 'Chá»n áº£nh PNG/JPG tá»‘i Ä‘a 2MB';

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
