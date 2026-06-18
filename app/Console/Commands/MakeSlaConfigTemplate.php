<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MakeSlaConfigTemplate extends Command
{
    protected $signature = 'make:sla-config-template';

    protected $description = 'Tạo file mẫu import cấu hình SLA';

    public function handle(): int
    {
        $path = storage_path('app/templates');

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'ma_sla',
            'ten_sla',
            'cong_doan',
            'canh_bao_phut',
            'qua_han_phut',
            'mo_ta',
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValue('A2', 'SLA_DVKH');
        $sheet->setCellValue('B2', 'SLA DVKH kiểm tra hồ sơ');
        $sheet->setCellValue('C2', 'DVKH');
        $sheet->setCellValue('D2', 180);
        $sheet->setCellValue('E2', 240);
        $sheet->setCellValue('F2', 'Cảnh báo sau 3 giờ, quá hạn sau 4 giờ.');

        $sheet->setCellValue('A3', 'SLA_PTN');
        $sheet->setCellValue('B3', 'SLA PTN lập phiếu');
        $sheet->setCellValue('C3', 'PTN');
        $sheet->setCellValue('D3', 360);
        $sheet->setCellValue('E3', 480);
        $sheet->setCellValue('F3', 'Cảnh báo sau 6 giờ, quá hạn sau 8 giờ.');

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/templates/template_sla.xlsx'));

        $this->info('Đã tạo file mẫu import SLA.');

        return self::SUCCESS;
    }
}