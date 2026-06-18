<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MakeCustomerTemplate extends Command
{
    protected $signature = 'make:customer-template';

    protected $description = 'Tạo file mẫu import khách hàng - công trình';

    public function handle(): int
    {
        $path = storage_path('app/templates');

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'ma_khach_hang',
            'ten_khach_hang',
            'dia_chi_khach_hang',
            'ma_so_thue',
            'nguoi_lien_he',
            'dien_thoai',
            'email',
            'ten_cong_trinh',
            'dia_diem_cong_trinh',
        ];

        foreach ($headers as $index => $header) {
            $col = chr(65 + $index);
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setCellValue('A2', 'KH001');
        $sheet->setCellValue('B2', 'Công ty ABC');
        $sheet->setCellValue('C2', 'Hải Phòng');
        $sheet->setCellValue('D2', '0200000000');
        $sheet->setCellValue('E2', 'Nguyễn Văn A');
        $sheet->setCellValue('F2', '0900000000');
        $sheet->setCellValue('G2', 'abc@example.com');
        $sheet->setCellValue('H2', 'Dự án cấp nước ABC');
        $sheet->setCellValue('I2', 'Hải Phòng');

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/templates/template_khach_hang_cong_trinh.xlsx'));

        $this->info('Đã tạo file mẫu import khách hàng - công trình.');

        return self::SUCCESS;
    }
}