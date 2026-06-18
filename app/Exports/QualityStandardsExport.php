<?php

namespace App\Exports;

use App\Models\QualityStandard;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class QualityStandardsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return QualityStandard::orderBy('code')
            ->get()
            ->map(function ($standard) {
                return [
                    'ma_tieu_chuan' => $standard->code,
                    'ten_tieu_chuan' => $standard->name,
                    'mo_ta' => $standard->description,
                    'trang_thai' => $standard->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ma_tieu_chuan',
            'ten_tieu_chuan',
            'mo_ta',
            'trang_thai',
        ];
    }
}