<?php

namespace App\Exports;

use App\Models\SlaConfig;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SlaConfigsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return SlaConfig::orderBy('process_step')
            ->get()
            ->map(function ($sla) {
                return [
                    'ma_sla' => $sla->code,
                    'ten_sla' => $sla->name,
                    'cong_doan' => $sla->process_step,
                    'canh_bao_phut' => $sla->warning_minutes,
                    'qua_han_phut' => $sla->limit_minutes,
                    'mo_ta' => $sla->description,
                    'trang_thai' => $sla->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ma_sla',
            'ten_sla',
            'cong_doan',
            'canh_bao_phut',
            'qua_han_phut',
            'mo_ta',
            'trang_thai',
        ];
    }
}