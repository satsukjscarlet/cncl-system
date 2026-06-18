<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\QualityStandard;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $productCode = trim($row['ma_san_pham'] ?? '');
        $productName = trim($row['ten_san_pham'] ?? '');

        if (empty($productCode) || empty($productName)) {
            return null;
        }

        $groupCode = trim($row['ma_nhom_san_pham'] ?? '');
        $groupName = trim($row['ten_nhom_san_pham'] ?? '');

        $group = null;

        if (!empty($groupCode)) {
            $group = ProductGroup::where('code', $groupCode)->first();
        }

        if (!$group && !empty($groupName)) {
            $group = ProductGroup::firstOrCreate(
                ['name' => $groupName],
                [
                    'code' => !empty($groupCode)
                        ? $groupCode
                        : strtoupper(Str::slug($groupName, '-')),
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        if (!$group) {
            return null;
        }

        $standardCode = trim($row['tieu_chuan_san_pham'] ?? '');

        $standard = null;

        if (!empty($standardCode)) {
            $standard = QualityStandard::firstOrCreate(
                ['code' => $standardCode],
                [
                    'name' => $standardCode,
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        return Product::updateOrCreate(
            ['product_code' => $productCode],
            [
                'product_group_id' => $group->id,
                'quality_standard_id' => $standard?->id,
                'product_name' => $productName,
                'unit' => $row['don_vi_tinh'] ?? null,
                'nominal_size' => $row['kich_thuoc_danh_nghia'] ?? null,
                'technical_requirements' => $row['yeu_cau_ky_thuat'] ?? null,
                'certificate_type' => $row['loai_phieu'] ?? 'CNCL',
                'certificate_template' => $row['mau_phieu'] ?? null,
                'note' => $row['ghi_chu'] ?? null,
                'is_active' => true,
            ]
        );
    }
}