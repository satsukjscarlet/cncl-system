<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\ProductUnit;
use App\Models\QualityStandard;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsEmptyRows
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $failed = 0;

    public array $errors = [];

    public function model(array $row)
    {
        try {
            $productCode = trim((string) ($row['ma_san_pham'] ?? ''));
            $productName = trim((string) ($row['ten_san_pham'] ?? ''));

            if (empty($productCode) || empty($productName)) {
                $this->skipped++;

                return null;
            }

            $groupCode = trim((string) ($row['ma_nhom_san_pham'] ?? ''));
            $groupName = trim((string) ($row['ten_nhom_san_pham'] ?? ''));

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
                $this->skipped++;

                return null;
            }

            $standardCode = trim((string) ($row['tieu_chuan_san_pham'] ?? ''));

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

            $unitName = $this->nullableString($row['don_vi_tinh'] ?? null);
            $unit = $unitName ? $this->resolveProductUnit($unitName) : null;

            $product = Product::updateOrCreate(
                ['product_code' => $productCode],
                [
                    'product_group_id' => $group->id,
                    'quality_standard_id' => $standard?->id,
                    'unit_id' => $unit?->id,
                    'product_name' => $productName,
                    'unit' => $unitName,
                    'nominal_size' => $this->nullableString($row['kich_thuoc_danh_nghia'] ?? null),
                    'technical_requirements' => $this->nullableString($row['yeu_cau_ky_thuat'] ?? null),
                    'certificate_type' => $this->nullableString($row['loai_phieu'] ?? null) ?: 'CNCL',
                    'certificate_template' => $this->nullableString($row['mau_phieu'] ?? null),
                    'note' => $this->nullableString($row['ghi_chu'] ?? null),
                    'is_active' => true,
                ]
            );

            $product->wasRecentlyCreated ? $this->created++ : $this->updated++;

            return $product;
        } catch (\Throwable $e) {
            $this->failed++;

            if (count($this->errors) < 20) {
                $this->errors[] = $e->getMessage();
            }

            return null;
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveProductUnit(string $name): ProductUnit
    {
        $unit = ProductUnit::where('name', $name)->first();

        if ($unit) {
            return $unit;
        }

        $baseCode = strtoupper(Str::slug($name, '_')) ?: 'DVT';
        $code = substr($baseCode, 0, 40);
        $index = 2;

        while (ProductUnit::where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 36) . '_' . $index;
            $index++;
        }

        return ProductUnit::create([
            'code' => $code,
            'name' => $name,
            'description' => null,
            'is_active' => true,
        ]);
    }
}
