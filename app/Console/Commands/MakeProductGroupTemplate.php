<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MakeProductGroupTemplate extends Command
{
    protected $signature = 'make:product-group-template';

    protected $description = 'Tạo file mẫu import nhóm sản phẩm';

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
            'tieu_chuan',
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValue('A2', 'NTP-001');
        $sheet->setCellValue('B2', 'Ống PVC-U');
        $sheet->setCellValue('C2', 'ISO 1452-2:2009');

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/templates/template_nhom_san_pham.xlsx'));

        $this->info('Đã tạo file mẫu import nhóm sản phẩm.');

        return self::SUCCESS;
    }
}