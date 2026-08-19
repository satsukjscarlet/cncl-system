<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['auto_send_email_after_sign', '1', 'boolean', 'Tự động gửi email sau khi ký số/phát hành phiếu CNCL.'],
            ['certificate_mail_cc_customer_email', '1', 'boolean', 'Tự động đưa email khách hàng vào CC khi gửi phiếu CNCL.'],
            ['certificate_mail_cc_dvkh', '', 'string', 'Danh sách email DVKH nhận CC khi gửi phiếu CNCL.'],
            ['certificate_mail_cc_ptn', '', 'string', 'Danh sách email PTN nhận CC khi gửi phiếu CNCL.'],
            ['certificate_mail_cc_extra', '', 'string', 'Danh sách email CC bổ sung khi gửi phiếu CNCL.'],
            ['smartca_signature_visible', '1', 'boolean', 'Hiển thị vùng chữ ký số trên PDF.'],
            ['smartca_signature_show_check', '1', 'boolean', 'Hiển thị dấu tích xanh trong mẫu chữ ký số.'],
            ['smartca_signature_render_mode', '0', 'integer', 'Kiểu hiển thị chữ ký số VNPT SmartCA.'],
            ['smartca_signature_font_size', '11', 'integer', 'Cỡ chữ mẫu chữ ký VNPT SmartCA.'],
            ['smartca_signature_font_color', '#b00020', 'string', 'Màu chữ mẫu chữ ký VNPT SmartCA.'],
            [
                'smartca_signature_text',
                "PHIẾU ĐƯỢC KÝ ĐIỆN TỬ\nSố phiếu: {certificate_no}\nNgười ký: {signed_by}\nThời gian ký: {signed_at}",
                'string',
                'Nội dung chữ ký hiển thị trên PDF.',
            ],
            ['smartca_signature_page_mode', 'last', 'string', 'Cách chọn trang đặt chữ ký số trên PDF.'],
            ['smartca_signature_page', '1', 'integer', 'Trang đặt chữ ký số trên PDF.'],
            ['smartca_signature_rectangle', '315,150,565,220', 'string', 'Tọa độ khung chữ ký số trên PDF.'],
            ['smartca_signature_image_path', '', 'string', 'Ảnh/logo dùng trong mẫu chữ ký VNPT SmartCA.'],
        ];

        foreach ($settings as [$key, $value, $type, $description]) {
            SystemSetting::firstOrCreate(
                ['key' => $key],
                compact('value', 'type', 'description')
            );
        }
    }
}
