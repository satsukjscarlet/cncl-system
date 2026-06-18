<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::updateOrCreate(
            ['key' => 'auto_send_email_after_sign'],
            [
                'value' => '1',
                'type' => 'boolean',
                'description' => 'Tự động gửi email cho khách hàng sau khi ký số/phát hành phiếu CNCL.',
            ]
        );
    }
}