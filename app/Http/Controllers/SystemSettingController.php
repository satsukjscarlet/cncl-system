<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    public function index()
    {
        $autoSendEmail = SystemSetting::getValue('auto_send_email_after_sign', true);
        $signatureSettings = [
            'render_mode' => SystemSetting::getValue('smartca_signature_render_mode', 0),
            'font_size' => SystemSetting::getValue('smartca_signature_font_size', 11),
            'font_color' => SystemSetting::getValue('smartca_signature_font_color', '#000000'),
            'signature_text' => SystemSetting::getValue('smartca_signature_text', "Phiếu CNCL: {certificate_no}\nThời gian ký: {signed_at}"),
            'page_mode' => SystemSetting::getValue('smartca_signature_page_mode', 'last'),
            'page' => SystemSetting::getValue('smartca_signature_page', 1),
            'rectangle' => SystemSetting::getValue('smartca_signature_rectangle', '315,150,565,220'),
            'image_path' => SystemSetting::getValue('smartca_signature_image_path'),
        ];

        return view('system_settings.index', compact('autoSendEmail', 'signatureSettings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'auto_send_email_after_sign' => ['nullable', 'boolean'],
            'smartca_signature_render_mode' => ['required', 'integer', 'between:0,4'],
            'smartca_signature_font_size' => ['required', 'integer', 'between:8,24'],
            'smartca_signature_font_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'smartca_signature_text' => ['required', 'string', 'max:1000'],
            'smartca_signature_page_mode' => ['required', 'in:last,fixed'],
            'smartca_signature_page' => ['required', 'integer', 'min:1', 'max:50'],
            'smartca_signature_rectangle' => ['required', 'regex:/^\d+,\d+,\d+,\d+$/'],
            'smartca_signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_smartca_signature_image' => ['nullable', 'boolean'],
        ], [
            'smartca_signature_rectangle.regex' => 'Khung chữ ký phải có dạng x1,y1,x2,y2, ví dụ 130,72,470,125.',
            'smartca_signature_image.max' => 'Ảnh chữ ký tối đa 2MB.',
        ]);

        [$x1, $y1, $x2, $y2] = array_map('intval', explode(',', $data['smartca_signature_rectangle']));

        if ($x1 >= $x2 || $y1 >= $y2) {
            return back()
                ->withErrors(['smartca_signature_rectangle' => 'Tọa độ khung chữ ký không hợp lệ: x1 phải nhỏ hơn x2 và y1 phải nhỏ hơn y2.'])
                ->withInput();
        }

        $currentImagePath = SystemSetting::getValue('smartca_signature_image_path');

        if (
            (int) $data['smartca_signature_render_mode'] > 0
            && !$request->hasFile('smartca_signature_image')
            && (blank($currentImagePath) || $request->boolean('remove_smartca_signature_image'))
        ) {
            return back()
                ->withErrors(['smartca_signature_image' => 'Vui lòng tải ảnh/logo khi chọn kiểu hiển thị có ảnh.'])
                ->withInput();
        }

        $oldValue = [
            'auto_send_email_after_sign' => SystemSetting::getValue('auto_send_email_after_sign', true),
            'smartca_signature_render_mode' => SystemSetting::getValue('smartca_signature_render_mode', 0),
            'smartca_signature_font_size' => SystemSetting::getValue('smartca_signature_font_size', 11),
            'smartca_signature_font_color' => SystemSetting::getValue('smartca_signature_font_color', '#000000'),
            'smartca_signature_text' => SystemSetting::getValue('smartca_signature_text'),
            'smartca_signature_page_mode' => SystemSetting::getValue('smartca_signature_page_mode', 'last'),
            'smartca_signature_page' => SystemSetting::getValue('smartca_signature_page', 1),
            'smartca_signature_rectangle' => SystemSetting::getValue('smartca_signature_rectangle', '315,150,565,220'),
            'smartca_signature_image_path' => $currentImagePath,
        ];

        $autoSendEmail = $request->boolean('auto_send_email_after_sign');
        $imagePath = $currentImagePath;

        if ($request->boolean('remove_smartca_signature_image') && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }

        if ($request->hasFile('smartca_signature_image')) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            $file = $request->file('smartca_signature_image');
            $imagePath = $file->storeAs(
                'signature-templates',
                'smartca-signature-' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            if (!$imagePath || !Storage::disk('public')->exists($imagePath)) {
                return back()
                    ->withErrors(['smartca_signature_image' => 'Không lưu được ảnh chữ ký. Vui lòng kiểm tra quyền ghi thư mục storage/app/public.'])
                    ->withInput();
            }
        }

        $this->setSetting('auto_send_email_after_sign', $autoSendEmail ? '1' : '0', 'boolean', 'Tự động gửi email cho khách hàng sau khi ký số/phát hành phiếu CNCL.');
        $this->setSetting('smartca_signature_render_mode', $data['smartca_signature_render_mode'], 'integer', 'Kiểu hiển thị chữ ký số VNPT SmartCA.');
        $this->setSetting('smartca_signature_font_size', $data['smartca_signature_font_size'], 'integer', 'Cỡ chữ mẫu chữ ký VNPT SmartCA.');
        $this->setSetting('smartca_signature_font_color', $data['smartca_signature_font_color'], 'string', 'Màu chữ mẫu chữ ký VNPT SmartCA.');
        $this->setSetting('smartca_signature_text', $data['smartca_signature_text'], 'string', 'Nội dung chữ ký hiển thị trên PDF.');
        $this->setSetting('smartca_signature_page_mode', $data['smartca_signature_page_mode'], 'string', 'Cach chon trang dat chu ky so tren PDF.');
        $this->setSetting('smartca_signature_page', $data['smartca_signature_page'], 'integer', 'Trang đặt chữ ký số trên PDF.');
        $this->setSetting('smartca_signature_rectangle', $data['smartca_signature_rectangle'], 'string', 'Tọa độ khung chữ ký số trên PDF.');
        $this->setSetting('smartca_signature_image_path', $imagePath, 'string', 'Ảnh/logo dùng trong mẫu chữ ký VNPT SmartCA.');

        ActivityLogger::log(
            'Cấu hình hệ thống',
            'update',
            'Cập nhật cấu hình hệ thống và mẫu chữ ký số',
            $oldValue,
            [
                'auto_send_email_after_sign' => $autoSendEmail,
                'smartca_signature_render_mode' => $data['smartca_signature_render_mode'],
                'smartca_signature_font_size' => $data['smartca_signature_font_size'],
                'smartca_signature_font_color' => $data['smartca_signature_font_color'],
                'smartca_signature_text' => $data['smartca_signature_text'],
                'smartca_signature_page_mode' => $data['smartca_signature_page_mode'],
                'smartca_signature_page' => $data['smartca_signature_page'],
                'smartca_signature_rectangle' => $data['smartca_signature_rectangle'],
                'smartca_signature_image_path' => $imagePath,
            ]
        );

        return redirect()
            ->route('system-settings.index')
            ->with('success', 'Cập nhật cấu hình hệ thống thành công.');
    }

    private function setSetting(string $key, mixed $value, string $type, ?string $description = null): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value === null ? null : (string) $value,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
