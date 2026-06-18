<?php

namespace App\Imports;

use App\Models\ProductGroup;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductGroupsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $name = $row['ten_nhom_san_pham'] ?? null;

        if (!$name) {
            return null;
        }

        return ProductGroup::updateOrCreate(
            ['name' => $name],
            [
                'code' => $row['ma_nhom_san_pham'] ?: strtoupper(Str::slug($name, '-')),
                'description' => $row['tieu_chuan'] ?? null,
                'is_active' => true,
            ]
        );
    }
}