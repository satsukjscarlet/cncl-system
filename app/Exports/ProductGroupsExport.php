<?php

namespace App\Exports;

use App\Models\ProductGroup;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductGroupsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return ProductGroup::select('code', 'name', 'description', 'is_active')->get();
    }

    public function headings(): array
    {
        return [
            'Mã nhóm sản phẩm',
            'Tên nhóm sản phẩm',
            'Tiêu chuẩn',
            'Trạng thái',
        ];
    }
}