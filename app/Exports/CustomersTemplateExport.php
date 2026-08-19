<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'NP',
                'KH-NP-001',
                'Công ty mẫu Nam Phương',
                'Địa chỉ khách hàng',
                '0100000000',
                'Nguyễn Văn A',
                '0900000000',
                'khachhang@example.com',
                'Công trình mẫu',
                'Địa điểm công trình',
                '1',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'ma_trung_tam',
            'ma_khach_hang',
            'ten_khach_hang',
            'dia_chi_khach_hang',
            'ma_so_thue',
            'nguoi_lien_he',
            'dien_thoai',
            'email',
            'ten_cong_trinh',
            'dia_diem_cong_trinh',
            'dang_su_dung',
        ];
    }
}
