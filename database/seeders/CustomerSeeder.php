<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'customer_code' => 'KH001',
                'customer_name' => 'Công ty CP tư vấn đầu tư xây dựng và thương mại Win-Win',
                'customer_address' => null,
                'tax_code' => null,
                'contact_person' => null,
                'phone' => null,
                'email' => 'winwin@example.com',
                'project_name' => 'Xây dựng khách sạn Panacea Hill Mộc Châu',
                'project_address' => 'Quốc lộ 6, tiểu khu 14, thị trấn Mộc Châu, tỉnh Sơn La',
                'is_active' => true,
            ],
            [
                'customer_code' => 'KH002',
                'customer_name' => 'Công ty Cấp nước Hải Phòng',
                'customer_address' => 'Hải Phòng',
                'tax_code' => null,
                'contact_person' => null,
                'phone' => '0900000000',
                'email' => 'capnuochp@example.com',
                'project_name' => 'Dự án cấp nước Hải Phòng',
                'project_address' => 'Hải Phòng',
                'is_active' => true,
            ],
        ];

        foreach ($data as $item) {
            Customer::updateOrCreate(
                ['customer_code' => $item['customer_code']],
                $item
            );
        }
    }
}