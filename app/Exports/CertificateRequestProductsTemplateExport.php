<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CertificateRequestProductsTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'ma_san_pham',
            'so_luong',
        ];
    }

    public function array(): array
    {
        $products = Product::where('is_active', true)
            ->orderBy('product_code')
            ->limit(2)
            ->get();

        if ($products->isEmpty()) {
            return [
                ['MA_SAN_PHAM_1', 1],
                ['MA_SAN_PHAM_2', 1],
            ];
        }

        return $products
            ->values()
            ->map(fn (Product $product, int $index) => [
                $product->product_code,
                $index === 0 ? 44 : 22,
            ])
            ->all();
    }
}
