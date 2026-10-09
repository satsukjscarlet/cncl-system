<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class HardCopyReportExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Collection $certificates,
        private readonly array $centerStats,
        private readonly array $salesUnitStats,
        private readonly array $monthlyStats,
        private readonly array $filterSummary
    ) {
    }

    public function sheets(): array
    {
        return [
            new HardCopyFilterSummarySheet($this->filterSummary),
            new HardCopyMonthlySummarySheet($this->monthlyStats),
            new HardCopyCenterSummarySheet($this->centerStats),
            new HardCopySalesUnitSummarySheet($this->salesUnitStats),
            new HardCopyDetailSheet($this->certificates),
        ];
    }
}

class HardCopyMonthlySummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $monthlyStats)
    {
    }

    public function title(): string
    {
        return 'Theo thang';
    }

    public function headings(): array
    {
        return [
            'Thang ky so',
            'Yeu cau ky tuoi',
            'Phieu da ky so',
            'So ban in yeu cau',
            'Trang In don',
            'To In don du kien',
            'Trang In bo',
            'To In bo du kien',
        ];
    }

    public function collection(): Collection
    {
        return collect($this->monthlyStats)->map(fn (array $row) => [
            'thang_ky_so' => $row['month_label'] ?? '',
            'yeu_cau_ky_tuoi' => $row['request_count'] ?? 0,
            'phieu_da_ky_so' => $row['certificate_count'] ?? 0,
            'so_ban_in_yeu_cau' => $row['copy_count'] ?? 0,
            'trang_in_don' => $row['single_page_count'] ?? 0,
            'to_in_don_du_kien' => $row['single_sheet_count'] ?? 0,
            'trang_in_bo' => $row['batch_page_count'] ?? 0,
            'to_in_bo_du_kien' => $row['batch_sheet_count'] ?? 0,
        ]);
    }
}

class HardCopyFilterSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $filterSummary)
    {
    }

    public function title(): string
    {
        return 'Dieu kien loc';
    }

    public function headings(): array
    {
        return [
            'Tieu chi',
            'Gia tri',
        ];
    }

    public function collection(): Collection
    {
        $rows = collect($this->filterSummary)->map(fn (array $row) => [
            'tieu_chi' => $row['label'] ?? '',
            'gia_tri' => $row['value'] ?? '',
        ]);

        return $rows->push([
            'tieu_chi' => 'Thoi gian xuat',
            'gia_tri' => now()->format('d/m/Y H:i:s'),
        ]);
    }
}

class HardCopyCenterSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $centerStats)
    {
    }

    public function title(): string
    {
        return 'Tong hop trung tam';
    }

    public function headings(): array
    {
        return [
            'Trung tam',
            'Yeu cau ky tuoi',
            'Phieu da ky so',
            'So ban in yeu cau',
            'Trang In don',
            'To In don du kien',
            'Trang In bo',
            'To In bo du kien',
            'Luot bam In don ghi nhan',
            'Luot bam In bo ghi nhan',
        ];
    }

    public function collection(): Collection
    {
        return collect($this->centerStats)->map(function (array $row) {
            $center = $row['center'] ?? null;

            return [
                'trung_tam' => trim(($center?->code ? $center->code . ' - ' : '') . ($center?->name ?? '')),
                'yeu_cau_ky_tuoi' => $row['request_count'] ?? 0,
                'phieu_da_ky_so' => $row['certificate_count'] ?? 0,
                'so_ban_in_yeu_cau' => $row['copy_count'] ?? 0,
                'trang_in_don' => $row['single_page_count'] ?? 0,
                'to_in_don_du_kien' => $row['single_sheet_count'] ?? 0,
                'trang_in_bo' => $row['batch_page_count'] ?? 0,
                'to_in_bo_du_kien' => $row['batch_sheet_count'] ?? 0,
                'luot_bam_in_don' => $row['single_print_count'] ?? 0,
                'luot_bam_in_bo' => $row['batch_print_count'] ?? 0,
            ];
        });
    }
}

class HardCopySalesUnitSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $salesUnitStats)
    {
    }

    public function title(): string
    {
        return 'Tong hop DVBH';
    }

    public function headings(): array
    {
        return [
            'Trung tam',
            'Ma DVBH',
            'Don vi ban hang',
            'Yeu cau ky tuoi',
            'Phieu da ky so',
            'So ban in yeu cau',
            'Trang In don',
            'To In don du kien',
            'Trang In bo',
            'To In bo du kien',
        ];
    }

    public function collection(): Collection
    {
        return collect($this->salesUnitStats)->map(function (array $row) {
            $center = $row['center'] ?? null;
            $salesUnit = $row['sales_unit'] ?? null;

            return [
                'trung_tam' => trim(($center?->code ? $center->code . ' - ' : '') . ($center?->name ?? '')),
                'ma_dvbh' => $salesUnit?->code ?? 'Chua gan',
                'don_vi_ban_hang' => $salesUnit?->name ?? 'Chua gan don vi ban hang',
                'yeu_cau_ky_tuoi' => $row['request_count'] ?? 0,
                'phieu_da_ky_so' => $row['certificate_count'] ?? 0,
                'so_ban_in_yeu_cau' => $row['copy_count'] ?? 0,
                'trang_in_don' => $row['single_page_count'] ?? 0,
                'to_in_don_du_kien' => $row['single_sheet_count'] ?? 0,
                'trang_in_bo' => $row['batch_page_count'] ?? 0,
                'to_in_bo_du_kien' => $row['batch_sheet_count'] ?? 0,
            ];
        });
    }
}

class HardCopyDetailSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly Collection $certificates)
    {
    }

    public function title(): string
    {
        return 'Chi tiet phieu';
    }

    public function headings(): array
    {
        return [
            'So phieu',
            'So yeu cau',
            'Ngay ky so',
            'Trung tam',
            'Don vi ban hang',
            'Khach hang',
            'Cong trinh',
            'Dia diem cong trinh',
            'So hoa don',
            'So ban in yeu cau',
            'Trang In don',
            'To In don du kien',
            'Trang In bo',
            'To In bo du kien',
            'Ghi nhan bam in',
            'Luot bam In don ghi nhan',
            'Luot bam In bo ghi nhan',
            'Lan bam in gan nhat',
            'Nguoi bam in gan nhat',
        ];
    }

    public function collection(): Collection
    {
        return $this->certificates->map(function ($certificate) {
            $request = $certificate->request;
            $customer = $request?->customer;
            $center = $request?->distributionCenter;
            $salesUnit = $request?->salesUnit;
            $copyQuantity = max(0, (int) ($request?->hard_copy_quantity ?? 0));
            $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
            $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
            $normalPrintLogs = $certificate->printLogs
                ->where('print_mode', 'normal')
                ->sortByDesc(fn ($log) => optional($log->created_at)->timestamp ?? 0);
            $singlePrintCount = $normalPrintLogs->where('print_template', 'single')->count();
            $batchPrintCount = $normalPrintLogs->where('print_template', 'batch')->count();
            $latestNormalPrint = $normalPrintLogs->first();

            return [
                'so_phieu' => $certificate->certificate_no,
                'so_yeu_cau' => $request?->request_no,
                'ngay_ky_so' => optional($certificate->signed_at)->format('d/m/Y H:i'),
                'trung_tam' => trim(($center?->code ? $center->code . ' - ' : '') . ($center?->name ?? '')),
                'don_vi_ban_hang' => trim(($salesUnit?->code ? $salesUnit->code . ' - ' : '') . ($salesUnit?->name ?? '')),
                'khach_hang' => $customer?->customer_name,
                'cong_trinh' => $customer?->project_name,
                'dia_diem_cong_trinh' => $customer?->project_address,
                'so_hoa_don' => $request?->invoice_no,
                'so_ban_in_yeu_cau' => $copyQuantity,
                'trang_in_don' => $singlePages,
                'to_in_don_du_kien' => $copyQuantity * $singlePages,
                'trang_in_bo' => $batchPages,
                'to_in_bo_du_kien' => $copyQuantity * $batchPages,
                'ghi_nhan_bam_in' => $this->printStatusText($singlePrintCount, $batchPrintCount),
                'luot_bam_in_don' => $singlePrintCount,
                'luot_bam_in_bo' => $batchPrintCount,
                'lan_bam_in_gan_nhat' => optional($latestNormalPrint?->created_at)->format('d/m/Y H:i'),
                'nguoi_bam_in_gan_nhat' => $latestNormalPrint?->user?->name,
            ];
        });
    }

    private function printStatusText(int $singlePrintCount, int $batchPrintCount): string
    {
        if ($singlePrintCount > 0 && $batchPrintCount > 0) {
            return 'Da bam In don va In bo';
        }

        if ($singlePrintCount > 0) {
            return 'Da bam In don';
        }

        if ($batchPrintCount > 0) {
            return 'Da bam In bo';
        }

        return 'Chua ghi nhan bam in';
    }
}
