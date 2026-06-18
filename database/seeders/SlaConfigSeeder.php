<?php

namespace Database\Seeders;

use App\Models\SlaConfig;
use Illuminate\Database\Seeder;

class SlaConfigSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'code' => 'SLA_DVKH',
                'name' => 'SLA DVKH kiểm tra hồ sơ',
                'process_step' => 'DVKH',
                'warning_minutes' => 180,
                'limit_minutes' => 240,
                'description' => 'Cảnh báo sau 3 giờ, quá hạn sau 4 giờ làm việc.',
            ],
            [
                'code' => 'SLA_PTN',
                'name' => 'SLA PTN lập phiếu',
                'process_step' => 'PTN',
                'warning_minutes' => 360,
                'limit_minutes' => 480,
                'description' => 'Cảnh báo sau 6 giờ, quá hạn sau 8 giờ làm việc.',
            ],
            [
                'code' => 'SLA_TOTAL',
                'name' => 'SLA toàn trình',
                'process_step' => 'TOTAL',
                'warning_minutes' => 1200,
                'limit_minutes' => 1440,
                'description' => 'Cảnh báo sau 20 giờ, quá hạn sau 24 giờ.',
            ],
        ];

        foreach ($data as $item) {
            SlaConfig::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, [
                    'is_active' => true,
                ])
            );
        }
    }
}