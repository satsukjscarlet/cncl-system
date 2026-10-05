<?php

namespace App\Exports;

use App\Models\SalesUnit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesUnitsExport implements FromCollection, WithHeadings
{
    public function __construct(private ?int $distributionCenterId = null)
    {
    }

    public function collection()
    {
        return SalesUnit::with('distributionCenter')
            ->when($this->distributionCenterId, fn ($query) => $query->where('distribution_center_id', $this->distributionCenterId))
            ->orderBy('code')
            ->get()
            ->map(fn ($salesUnit) => [
                'ma_trung_tam' => $salesUnit->distributionCenter?->code,
                'trung_tam' => $salesUnit->distributionCenter
                    ? $salesUnit->distributionCenter->code . ' - ' . $salesUnit->distributionCenter->name
                    : '',
                'ma_bravo' => $salesUnit->code,
                'ten_dvbh' => $salesUnit->name,
                'dia_chi_cua_hang' => $salesUnit->address,
                'so_dt_lien_lac' => $salesUnit->phone,
                'ma_so_thue' => $salesUnit->tax_code,
                'so_tai_khoan' => $salesUnit->bank_account,
                'dai_dien_chuc_vu' => $salesUnit->representative,
                'ghi_chu' => $salesUnit->note,
                'trang_thai' => $salesUnit->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng',
            ]);
    }

    public function headings(): array
    {
        return [
            'ma_trung_tam',
            'trung_tam',
            'ma_bravo',
            'ten_dvbh',
            'dia_chi_cua_hang',
            'so_dt_lien_lac',
            'ma_so_thue',
            'so_tai_khoan',
            'dai_dien_chuc_vu',
            'ghi_chu',
            'trang_thai',
        ];
    }
}
