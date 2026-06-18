<?php

namespace Database\Seeders;

use App\Models\ProductGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductGroupSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['Ống HDPE100', 'ISO 4427-2:2019'],
            ['Ống HDPE80', 'ISO 4427-2:2019'],
            ['Phụ tùng HDPE100', 'ISO 4427-3:2019'],
            ['Phụ tùng HDPE80', 'ISO 4427-3:2019'],
            ['Phụ tùng Nối ống HDPE', 'ISO 4427-3:2019'],
            ['Đai khởi thủy', 'ISO 4427-3:2019'],
            ['Ống nhựa xoắn HDPE 1 lớp', 'KSC 8455:2005'],
            ['Ống nhựa xoắn HDPE 1 lớp', 'TCVN 7997:2009'],
            ['Ống PP-R', 'DIN 8077:2008&DIN8078:2008'],
            ['Phụ tùng PP-R', 'DIN 16962:2000'],
            ['Phụ tùng PP-R ép phun', 'DIN 16962:2000'],
            ['Van PP-R', 'EN 1074-2:2000'],
            ['Ống PVC-U', 'ISO 1452-2:2009'],
            ['Phụ tùng PVC-U ép phun', 'ISO 1452-3:2009'],
            ['Phụ tùng PVC-U tạo', 'Kích thước theo ISO 1452-3:2009'],
            ['Van cầu PVC-U', 'ISO 1452-4:2009'],
            ['Keo dán PVC', 'DIN 16970:1970'],
            ['Nắp hố ga composite', 'BS EN 124:2015'],
            ['Ống luồn dây điện chịu va đập cao PVC-U', 'BS 6099-2-2:1982'],
            ['Ống luồn dây điện chịu va đập cao PVC-U', 'TCVN 7417-1:2010 (IEC 61386-1:2008)'],
            ['Phụ tùng ống luồn dây điện chịu va đập cao PVC-U', 'BS 4607:1982'],
            ['Phụ tùng PVC-U ép phun (inch)', 'BS EN ISO 1452-3:2009'],
            ['Gioăng nối ống và phụ tùng HDPE, PP-R, PVC', 'EN 681:2000'],
            ['Ống và phụ tùng gân sóng 2 lớp HDPE, PP', 'ISO 21138-3:2007'],
            ['Ống thoát nước PVC-U theo tiêu chuẩn ISO 3633', 'ISO 3633:2002'],
            ['Phụ tùng PVC-U ép phun theo tiêu chuẩn ISO 3633', 'ISO 3633:2002'],
            ['Phụ tùng PVC-U ép phun theo đơn đặt hàng của Sekisui', 'TCVN 12755:2020'],
            ['Thanh tường rào PVC-U', 'TCCS138:2014/NTP'],
            ['Ống CPVC dùng trong hệ thống Sprinkler tự động', 'TCVN 12653-1,2:2024'],
            ['Phụ tùng CPVC dùng trong hệ thống Sprinkler tự động', 'TCVN 12653-1,2:2024'],
            ['Ống PVC-M', 'AS/NZS 4765:2017'],
            ['Máng luồn dây điện PVC-U', 'TCCS02:2010/NTP'],
            ['Máng hứng nước mưa', 'TCCS24:2013/NTP'],
            ['Sản phẩm ép phun đặc chủng', 'TCCS 18:2020/NTP'],
            ['Phụ tùng chế tạo sẵn PVC', 'Kích thước ISO 1452-3:2009'],
            ['Ống lọc PVC-U', 'TCCS50:2010/NTP'],
            ['Phụ tùng PVC-U ép phun phủ composite và ép phun dán keo', 'Kích thước ISO 1452-3:2009'],
            ['Mỡ bôi trơn', 'TCCS17/2020/NTP'],
            ['Ống PVC-U lõi xoắn', 'TCVN 14489:2025'],
        ];

        foreach ($data as $index => [$name, $standard]) {
            ProductGroup::updateOrCreate(
                ['name' => $name, 'description' => $standard],
                [
                    'code' => 'NTP-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'description' => $standard,
                    'is_active' => true,
                ]
            );
        }
    }
}