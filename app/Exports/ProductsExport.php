<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::with([
            'group',
            'qualityStandard'
        ])
            ->orderBy('product_code')
            ->get()
            ->map(function ($product) {

                return [
                    'ma_nhom_san_pham'      => $product->group?->code,
                    'ten_nhom_san_pham'     => $product->group?->name,

                    'ma_san_pham'           => $product->product_code,
                    'ten_san_pham'          => $product->product_name,

                    'don_vi_tinh'           => $product->unit,

                    'kich_thuoc_danh_nghia' => $product->nominal_size,

                    'yeu_cau_ky_thuat'      => $product->technical_requirements,

                    'tieu_chuan_san_pham' => $product->qualityStandard?->code,

                    'loai_phieu'            => $product->certificate_type,

                    'mau_phieu'             => $product->certificate_template,

                    'ghi_chu'               => $product->note,

                    'trang_thai'            => $product->is_active
                        ? 'Đang sử dụng'
                        : 'Ngừng sử dụng',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ma_nhom_san_pham',
            'ten_nhom_san_pham',

            'ma_san_pham',
            'ten_san_pham',

            'don_vi_tinh',

            'kich_thuoc_danh_nghia',

            'yeu_cau_ky_thuat',

            'tieu_chuan_san_pham',

            'loai_phieu',

            'mau_phieu',

            'ghi_chu',

            'trang_thai',
        ];
    }
}
