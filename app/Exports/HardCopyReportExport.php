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

class HardCopyMonthlySummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $monthlyStats) {}
    public function title(): string { return 'Theo thang'; }
    public function headings(): array
    {
        return ['Tháng ký số', 'Yêu cầu cần ký tươi', 'Tổng số bản in', 'Trang In đơn', 'Tờ In đơn dự kiến', 'Trang In bộ', 'Tờ In bộ dự kiến'];
    }
    public function collection(): Collection
    {
        return collect($this->monthlyStats)->map(fn (array $row) => [
            $row['month_label'] ?? '',
            'yeu_cau_ky_tuoi' => $row['request_count'] ?? 0,
            'tong_so_ban' => $row['total_copy_count'] ?? 0,
            'trang_in_don' => $row['single_page_count'] ?? 0,
            'to_in_don_du_kien' => $row['single_sheet_count'] ?? 0,
            'trang_in_bo' => $row['batch_page_count'] ?? 0,
            'to_in_bo_du_kien' => $row['batch_sheet_count'] ?? 0,
        ]);
    }
}

class HardCopyCenterSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $centerStats) {}
    public function title(): string { return 'Tong hop trung tam'; }
    public function headings(): array
    {
        return ['Trung tâm', 'Yêu cầu cần ký tươi', 'Tổng số bản in', 'Trang In đơn', 'Tờ In đơn dự kiến', 'Trang In bộ', 'Tờ In bộ dự kiến'];
    }
    public function collection(): Collection
    {
        return collect($this->centerStats)->map(fn (array $row) => [
            trim(($row['center']?->code ?? '') . ' - ' . ($row['center']?->name ?? '')),
            'yeu_cau_ky_tuoi' => $row['request_count'] ?? 0,
            'tong_so_ban' => $row['total_copy_count'] ?? 0,
            'trang_in_don' => $row['single_page_count'] ?? 0,
            'to_in_don_du_kien' => $row['single_sheet_count'] ?? 0,
            'trang_in_bo' => $row['batch_page_count'] ?? 0,
            'to_in_bo_du_kien' => $row['batch_sheet_count'] ?? 0,
        ]);
    }
}

class HardCopySalesUnitSummarySheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly array $salesUnitStats) {}
    public function title(): string { return 'Tong hop DVBH'; }
    public function headings(): array
    {
        return ['Trung tâm', 'Đơn vị bán hàng', 'Yêu cầu cần ký tươi', 'Tổng số bản in', 'Trang In đơn', 'Tờ In đơn dự kiến', 'Trang In bộ', 'Tờ In bộ dự kiến'];
    }
    public function collection(): Collection
    {
        return collect($this->salesUnitStats)->map(fn (array $row) => [
            $row['center']?->name ?? '',
            trim(($row['sales_unit']?->code ?? '') . ' - ' . ($row['sales_unit']?->name ?? 'Chưa gắn')),
            'yeu_cau_ky_tuoi' => $row['request_count'] ?? 0,
            'tong_so_ban' => $row['total_copy_count'] ?? 0,
            'trang_in_don' => $row['single_page_count'] ?? 0,
            'to_in_don_du_kien' => $row['single_sheet_count'] ?? 0,
            'trang_in_bo' => $row['batch_page_count'] ?? 0,
            'to_in_bo_du_kien' => $row['batch_sheet_count'] ?? 0,
        ]);
    }
}

class HardCopyDetailSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly Collection $certificates) {}
    public function title(): string { return 'Chi tiet phieu'; }
    public function headings(): array
    {
        return ['Số phiếu', 'Số yêu cầu', 'Ngày ký số', 'Trung tâm', 'Đơn vị bán hàng', 'Khách hàng', 'Công trình', 'Địa điểm công trình', 'Số hóa đơn', 'Số bản ban đầu', 'Bổ sung đã hoàn tất', 'Tổng số bản in', 'Trang In đơn', 'Tờ In đơn dự kiến', 'Trang In bộ', 'Tờ In bộ dự kiến', 'Ghi nhận mở file ban đầu', 'Lượt mở In đơn ban đầu', 'Lượt mở In bộ ban đầu', 'Lần mở file ban đầu gần nhất', 'Người mở file ban đầu gần nhất'];
    }
    public function collection(): Collection
    {
        return $this->certificates->map(function ($certificate) {
            $request = $certificate->request;
            $original = $request?->require_hard_copy ? max(0, (int) $request->hard_copy_quantity) : 0;
            $extra = (int) $certificate->completed_supplement_quantity;
            $total = $original + $extra;
            $singlePages = max(1, (int) $certificate->hard_copy_single_page_count);
            $batchPages = max(1, (int) $certificate->hard_copy_batch_page_count);
            $logs = $certificate->printLogs->where('print_mode', 'normal')->whereNull('hard_copy_supplement_id')
                ->sortByDesc(fn ($log) => $log->created_at?->timestamp ?? 0);
            $single = $logs->where('print_template', 'single')->count();
            $batch = $logs->where('print_template', 'batch')->count();
            $latest = $logs->first();
            return [
                'so_phieu' => $certificate->certificate_no,
                'so_yeu_cau' => $request?->request_no,
                'ngay_ky_so' => $certificate->signed_at?->format('d/m/Y H:i'),
                'trung_tam' => $request?->distributionCenter?->name,
                'don_vi_ban_hang' => $request?->salesUnit?->name,
                'khach_hang' => $request?->customer?->customer_name,
                'cong_trinh' => $request?->customer?->project_name,
                'dia_diem' => $request?->customer?->project_address,
                'hoa_don' => $request?->invoice_no,
                'so_ban_ban_dau' => $original,
                'bo_sung_da_hoan_tat' => $extra,
                'tong_so_ban' => $total,
                'trang_in_don' => $singlePages,
                'to_in_don_du_kien' => $total * $singlePages,
                'trang_in_bo' => $batchPages,
                'to_in_bo_du_kien' => $total * $batchPages,
                'ghi_nhan_mo_file' => match (true) {
                    $single > 0 && $batch > 0 => 'Đã mở cả hai',
                    $single > 0 => 'Đã mở In đơn',
                    $batch > 0 => 'Đã mở In bộ',
                    default => 'Chưa ghi nhận',
                },
                'luot_in_don' => $single,
                'luot_in_bo' => $batch,
                'lan_mo_gan_nhat' => $latest?->created_at?->format('d/m/Y H:i'),
                'nguoi_mo_gan_nhat' => $latest?->user?->name,
            ];
        });
    }
}
