<?php

namespace Database\Seeders;

use App\Models\UrgentReason;
use Illuminate\Database\Seeder;

class UrgentReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            [
                'code' => 'KHACH_CAN_GAP',
                'name' => 'Khách hàng cần phiếu gấp',
                'description' => 'Khách hàng yêu cầu có phiếu sớm để hoàn thiện hồ sơ.',
            ],
            [
                'code' => 'BO_SUNG_CHUNG_TU',
                'name' => 'Bổ sung chứng từ cho hàng đã giao',
                'description' => 'Hàng đã xuất/giao, cần bổ sung phiếu CNCL cho chứng từ.',
            ],
            [
                'code' => 'DU_AN_CONG_TRINH',
                'name' => 'Theo tiến độ dự án/công trình',
                'description' => 'Công trình hoặc dự án cần phiếu để nghiệm thu, thanh toán, bàn giao.',
            ],
            [
                'code' => 'CHI_DAO_NOI_BO',
                'name' => 'Theo chỉ đạo nội bộ',
                'description' => 'Yêu cầu ưu tiên xử lý theo chỉ đạo từ bộ phận quản lý.',
            ],
            [
                'code' => 'LY_DO_KHAC',
                'name' => 'Lý do khác',
                'description' => 'Các trường hợp gấp khác ngoài danh mục chuẩn.',
            ],
        ];

        foreach ($reasons as $reason) {
            UrgentReason::updateOrCreate(
                ['code' => $reason['code']],
                $reason + ['is_active' => true]
            );
        }
    }
}
