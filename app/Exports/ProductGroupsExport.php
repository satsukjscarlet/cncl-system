<?php

namespace App\Exports;

use App\Models\ProductGroup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProductGroupsExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private bool $template = false)
    {
    }

    public function collection()
    {
        if ($this->template) {
            return collect();
        }

        return ProductGroup::with('defaultQualityStandard')->orderBy('code')->get()->map(fn ($group) => [
            $group->code, $group->name, $group->description, (int) $group->is_active,
            $group->defaultQualityStandard?->code,
        ]);
    }

    public function headings(): array
    {
        return [
            'Mã nhóm sản phẩm',
            'Tên nhóm sản phẩm',
            'Ghi chú',
            'Trạng thái',
            'Mã tiêu chuẩn mặc định',
        ];
    }
}
