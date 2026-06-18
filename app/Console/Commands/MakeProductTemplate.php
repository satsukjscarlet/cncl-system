<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MakeProductTemplate extends Command
{
    protected $signature = 'make:product-template';

    protected $description = 'Tạo file mẫu import sản phẩm';

    public function handle(): int
    {
        $path = storage_path('app/templates');

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'ma_nhom_san_pham',
            'ten_nhom_san_pham',
            'ma_san_pham',
            'ten_san_pham',
            'don_vi_tinh',
            'kich_thuoc_danh_nghia',
            'yeu_cau_ky_thuat',
            'tieu_chuan_san_pham',
            'mau_phieu',
            'ghi_chu',
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValue('A2', 'NTP-013');
        $sheet->setCellValue('B2', 'Ống PVC-U');
        $sheet->setCellValue('C2', 'PVC-U-110');
        $sheet->setCellValue('D2', 'Ống PVC-U Ø110');
        $sheet->setCellValue('E2', 'Mét');
        $sheet->setCellValue('F2', 'Ø110');
        $sheet->setCellValue('G2', 'PN10');
        $sheet->setCellValue('H2', 'ISO 1452-2:2009');
        $sheet->setCellValue('I2', 'PVC');
        $sheet->setCellValue('J2', 'Dữ liệu mẫu');

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/templates/template_san_pham.xlsx'));

        $this->info('Đã tạo file mẫu import sản phẩm.');

        return self::SUCCESS;
    }
}