<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Models\SystemSetting;
use App\Services\TestDataManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SystemSettingController extends Controller
{
    private const A4_WIDTH = 595;
    private const A4_HEIGHT = 842;

    public function index()
    {
        $autoSendEmail = SystemSetting::getValue('auto_send_email_after_sign', true);
        $mailSettings = [
            'cc_customer_email' => SystemSetting::getValue(
                'certificate_mail_cc_customer_email',
                config('certificate_mail.quality_certificate.cc_customer_email', true)
            ),
            'cc_dvkh' => SystemSetting::getValue(
                'certificate_mail_cc_dvkh',
                $this->emailListToText(config('certificate_mail.quality_certificate.cc.dvkh', []))
            ),
            'cc_ptn' => SystemSetting::getValue(
                'certificate_mail_cc_ptn',
                $this->emailListToText(config('certificate_mail.quality_certificate.cc.ptn', []))
            ),
            'cc_extra' => SystemSetting::getValue(
                'certificate_mail_cc_extra',
                $this->emailListToText(config('certificate_mail.quality_certificate.cc.extra', []))
            ),
        ];
        $signatureSettings = [
            'visible' => SystemSetting::getValue('smartca_signature_visible', true),
            'show_check' => SystemSetting::getValue('smartca_signature_show_check', true),
            'render_mode' => SystemSetting::getValue('smartca_signature_render_mode', 0),
            'font_size' => SystemSetting::getValue('smartca_signature_font_size', 11),
            'font_color' => SystemSetting::getValue('smartca_signature_font_color', '#b00020'),
            'signature_text' => SystemSetting::getValue(
                'smartca_signature_text',
                "PHIẾU ĐƯỢC KÝ ĐIỆN TỬ\nSố phiếu: {certificate_no}\nNgười ký: {signed_by}\nThời gian ký: {signed_at}"
            ),
            'page_mode' => SystemSetting::getValue('smartca_signature_page_mode', 'last'),
            'page' => SystemSetting::getValue('smartca_signature_page', 1),
            'rectangle' => SystemSetting::getValue('smartca_signature_rectangle', '315,150,565,220'),
            'image_path' => SystemSetting::getValue('smartca_signature_image_path'),
        ];

        return view('system_settings.index', compact('autoSendEmail', 'mailSettings', 'signatureSettings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'auto_send_email_after_sign' => ['nullable', 'boolean'],
            'certificate_mail_cc_customer_email' => ['nullable', 'boolean'],
            'certificate_mail_cc_dvkh' => ['nullable', 'string', 'max:2000'],
            'certificate_mail_cc_ptn' => ['nullable', 'string', 'max:2000'],
            'certificate_mail_cc_extra' => ['nullable', 'string', 'max:4000'],
            'smartca_signature_visible' => ['nullable', 'boolean'],
            'smartca_signature_show_check' => ['nullable', 'boolean'],
            'smartca_signature_render_mode' => ['required', 'integer', 'between:0,4'],
            'smartca_signature_font_size' => ['required', 'integer', 'between:8,24'],
            'smartca_signature_font_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'smartca_signature_text' => ['required', 'string', 'max:1000'],
            'smartca_signature_page_mode' => ['required', 'in:last,fixed'],
            'smartca_signature_page' => ['nullable', 'required_if:smartca_signature_page_mode,fixed', 'integer', 'min:1', 'max:50'],
            'smartca_signature_rectangle' => ['required', 'regex:/^\d+,\d+,\d+,\d+$/'],
            'smartca_signature_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'remove_smartca_signature_image' => ['nullable', 'boolean'],
        ], [
            'smartca_signature_rectangle.regex' => 'Khung chữ ký phải có dạng x1,y1,x2,y2, ví dụ 315,150,565,220.',
            'smartca_signature_image.max' => 'Ảnh chữ ký tối đa 2MB.',
        ]);

        $emailLists = [
            'certificate_mail_cc_dvkh' => $this->parseEmailList($data['certificate_mail_cc_dvkh'] ?? ''),
            'certificate_mail_cc_ptn' => $this->parseEmailList($data['certificate_mail_cc_ptn'] ?? ''),
            'certificate_mail_cc_extra' => $this->parseEmailList($data['certificate_mail_cc_extra'] ?? ''),
        ];

        foreach ($emailLists as $field => $result) {
            if ($result['invalid']) {
                return back()
                    ->withErrors([$field => 'Email không hợp lệ: ' . implode(', ', $result['invalid'])])
                    ->withInput();
            }
        }

        $rectangleError = $this->validateSignatureRectangle($data['smartca_signature_rectangle']);
        if ($rectangleError) {
            return back()
                ->withErrors(['smartca_signature_rectangle' => $rectangleError])
                ->withInput();
        }

        $data['smartca_signature_page'] = (int) ($data['smartca_signature_page'] ?? 1);
        $visibleSignature = $request->boolean('smartca_signature_visible', false);
        $showSignatureCheck = $request->boolean('smartca_signature_show_check', false);
        $currentImagePath = SystemSetting::getValue('smartca_signature_image_path');

        if (
            $visibleSignature
            && !$showSignatureCheck
            && (int) $data['smartca_signature_render_mode'] > 0
            && !$request->hasFile('smartca_signature_image')
            && (blank($currentImagePath) || $request->boolean('remove_smartca_signature_image'))
        ) {
            return back()
                ->withErrors(['smartca_signature_image' => 'Vui lòng tải ảnh/logo khi chọn kiểu hiển thị có ảnh.'])
                ->withInput();
        }

        $oldValue = [
            'auto_send_email_after_sign' => SystemSetting::getValue('auto_send_email_after_sign', true),
            'certificate_mail_cc_customer_email' => SystemSetting::getValue('certificate_mail_cc_customer_email', true),
            'certificate_mail_cc_dvkh' => SystemSetting::getValue('certificate_mail_cc_dvkh', ''),
            'certificate_mail_cc_ptn' => SystemSetting::getValue('certificate_mail_cc_ptn', ''),
            'certificate_mail_cc_extra' => SystemSetting::getValue('certificate_mail_cc_extra', ''),
            'smartca_signature_visible' => SystemSetting::getValue('smartca_signature_visible', true),
            'smartca_signature_show_check' => SystemSetting::getValue('smartca_signature_show_check', true),
            'smartca_signature_render_mode' => SystemSetting::getValue('smartca_signature_render_mode', 0),
            'smartca_signature_font_size' => SystemSetting::getValue('smartca_signature_font_size', 11),
            'smartca_signature_font_color' => SystemSetting::getValue('smartca_signature_font_color', '#b00020'),
            'smartca_signature_text' => SystemSetting::getValue('smartca_signature_text'),
            'smartca_signature_page_mode' => SystemSetting::getValue('smartca_signature_page_mode', 'last'),
            'smartca_signature_page' => SystemSetting::getValue('smartca_signature_page', 1),
            'smartca_signature_rectangle' => SystemSetting::getValue('smartca_signature_rectangle', '315,150,565,220'),
            'smartca_signature_image_path' => $currentImagePath,
        ];

        $autoSendEmail = $request->boolean('auto_send_email_after_sign');
        $ccCustomerEmail = $request->boolean('certificate_mail_cc_customer_email');
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

        $settings = [
            ['auto_send_email_after_sign', $autoSendEmail ? '1' : '0', 'boolean', 'Tự động gửi email sau khi ký số/phát hành phiếu CNCL.'],
            ['certificate_mail_cc_customer_email', $ccCustomerEmail ? '1' : '0', 'boolean', 'Tự động đưa email khách hàng vào CC khi gửi phiếu CNCL.'],
            ['certificate_mail_cc_dvkh', $this->emailListToText($emailLists['certificate_mail_cc_dvkh']['valid']), 'string', 'Danh sách email DVKH nhận CC khi gửi phiếu CNCL.'],
            ['certificate_mail_cc_ptn', $this->emailListToText($emailLists['certificate_mail_cc_ptn']['valid']), 'string', 'Danh sách email PTN nhận CC khi gửi phiếu CNCL.'],
            ['certificate_mail_cc_extra', $this->emailListToText($emailLists['certificate_mail_cc_extra']['valid']), 'string', 'Danh sách email CC bổ sung khi gửi phiếu CNCL.'],
            ['smartca_signature_visible', $visibleSignature ? '1' : '0', 'boolean', 'Hiển thị vùng chữ ký số trên PDF.'],
            ['smartca_signature_show_check', $showSignatureCheck ? '1' : '0', 'boolean', 'Hiển thị dấu tích xanh trong mẫu chữ ký số.'],
            ['smartca_signature_render_mode', $data['smartca_signature_render_mode'], 'integer', 'Kiểu hiển thị chữ ký số VNPT SmartCA.'],
            ['smartca_signature_font_size', $data['smartca_signature_font_size'], 'integer', 'Cỡ chữ mẫu chữ ký VNPT SmartCA.'],
            ['smartca_signature_font_color', $data['smartca_signature_font_color'], 'string', 'Màu chữ mẫu chữ ký VNPT SmartCA.'],
            ['smartca_signature_text', $data['smartca_signature_text'], 'string', 'Nội dung chữ ký hiển thị trên PDF.'],
            ['smartca_signature_page_mode', $data['smartca_signature_page_mode'], 'string', 'Cách chọn trang đặt chữ ký số trên PDF.'],
            ['smartca_signature_page', $data['smartca_signature_page'], 'integer', 'Trang đặt chữ ký số trên PDF.'],
            ['smartca_signature_rectangle', $data['smartca_signature_rectangle'], 'string', 'Tọa độ khung chữ ký số trên PDF.'],
            ['smartca_signature_image_path', $imagePath, 'string', 'Ảnh/logo dùng trong mẫu chữ ký VNPT SmartCA.'],
        ];

        foreach ($settings as [$key, $value, $type, $description]) {
            $this->setSetting($key, $value, $type, $description);
        }

        ActivityLogger::log(
            'Cấu hình hệ thống',
            'update',
            'Cập nhật cấu hình hệ thống, email và mẫu chữ ký số',
            $oldValue,
            [
                'auto_send_email_after_sign' => $autoSendEmail,
                'certificate_mail_cc_customer_email' => $ccCustomerEmail,
                'certificate_mail_cc_dvkh' => $emailLists['certificate_mail_cc_dvkh']['valid'],
                'certificate_mail_cc_ptn' => $emailLists['certificate_mail_cc_ptn']['valid'],
                'certificate_mail_cc_extra' => $emailLists['certificate_mail_cc_extra']['valid'],
                'smartca_signature_visible' => $visibleSignature,
                'smartca_signature_show_check' => $showSignatureCheck,
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

    public function seedTestData(TestDataManager $testDataManager)
    {
        $this->authorizeAdminOnly();

        $result = $testDataManager->seed();

        ActivityLogger::log(
            'Dữ liệu test',
            'seed',
            'Admin tạo lại dữ liệu test workflow/báo cáo.',
            null,
            $result
        );

        return redirect()
            ->route('system-settings.index')
            ->with('success', 'Đã tạo lại dữ liệu test: ' . $result['requests'] . ' yêu cầu, ' . $result['certificates'] . ' phiếu, ' . $result['customers'] . ' khách hàng.');
    }

    public function clearTestData(TestDataManager $testDataManager)
    {
        $this->authorizeAdminOnly();

        $result = $testDataManager->clear();

        ActivityLogger::log(
            'Dữ liệu test',
            'clear',
            'Admin xóa dữ liệu test workflow/báo cáo.',
            $result,
            null
        );

        return redirect()
            ->route('system-settings.index')
            ->with('success', 'Đã xóa dữ liệu test: ' . $result['requests'] . ' yêu cầu, ' . $result['certificates'] . ' phiếu, ' . $result['customers'] . ' khách hàng.');
    }

    private function validateSignatureRectangle(string $rectangle): ?string
    {
        [$x1, $y1, $x2, $y2] = array_map('intval', explode(',', $rectangle));

        if ($x1 >= $x2 || $y1 >= $y2) {
            return 'Tọa độ khung chữ ký không hợp lệ: x1 phải nhỏ hơn x2 và y1 phải nhỏ hơn y2.';
        }

        if ($x1 < 0 || $y1 < 0 || $x2 > self::A4_WIDTH || $y2 > self::A4_HEIGHT) {
            return 'Khung chữ ký phải nằm trong khổ A4 dọc: x từ 0-595, y từ 0-842.';
        }

        if (($x2 - $x1) < 120 || ($y2 - $y1) < 45) {
            return 'Khung chữ ký quá nhỏ. Nên để rộng tối thiểu 120 point và cao tối thiểu 45 point.';
        }

        return null;
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

    private function parseEmailList(?string $value): array
    {
        $items = preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        $valid = [];
        $invalid = [];

        foreach ($items as $item) {
            $email = strtolower(trim($item));

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $email;
            } else {
                $invalid[] = $item;
            }
        }

        return [
            'valid' => array_values(array_unique($valid)),
            'invalid' => array_values(array_unique($invalid)),
        ];
    }

    private function emailListToText(array|string|null $emails): string
    {
        if (is_string($emails)) {
            $emails = preg_split('/[\s,;]+/', $emails, -1, PREG_SPLIT_NO_EMPTY);
        }

        return implode("\n", array_values(array_unique(array_filter(array_map('trim', $emails ?: [])))));
    }

    private function authorizeAdminOnly(): void
    {
        abort_unless(Auth::user()?->hasRole('Admin'), 403);
    }
}
