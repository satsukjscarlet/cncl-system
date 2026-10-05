<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesUnitsTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'TP',
                'HNHTM',
                'Công ty TNHH TM Thành Mơ',
                'Số 263 đường Trường Chinh, Phường Khương Mai, Quận Thanh Xuân, Thành phố Hà Nội',
                '0976215533',
                '0101436674',
                '059110079600',
                'GĐ. Bà Nghiêm Thị Mơ',
                'Ký mới T4/2023',
                '1',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'ma_trung_tam',
            'ma_bravo',
            'ten_dvbh',
            'dia_chi_cua_hang',
            'so_dt_lien_lac',
            'ma_so_thue',
            'so_tai_khoan',
            'dai_dien_chuc_vu',
            'ghi_chu',
            'dang_su_dung',
        ];
    }
}
