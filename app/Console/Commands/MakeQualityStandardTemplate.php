<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MakeQualityStandardTemplate extends Command
{
    protected $signature = 'make:quality-standard-template';

    protected $description = 'Tạo file mẫu import tiêu chuẩn chất lượng';

    public function handle(): int
    {
        $path = storage_path('app/templates');

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'ma_tieu_chuan',
            'ten_tieu_chuan',
            'mo_ta',
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValue('A2', 'ISO 1452-3:2009');
        $sheet->setCellValue('B2', 'ISO 1452-3:2009');
        $sheet->setCellValue('C2', 'Tiêu chuẩn phụ tùng PVC-U');

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/templates/template_tieu_chuan_chat_luong.xlsx'));

        $this->info('Đã tạo file mẫu import tiêu chuẩn chất lượng.');

        return self::SUCCESS;
    }
}