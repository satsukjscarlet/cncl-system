<?php

namespace App\Exports;

use App\Models\CertificateRequest;
use App\Models\SlaConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CertificateSummaryExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo = null,
        private readonly ?string $status = null,
        private readonly ?int $distributionCenterId = null
    ) {
    }

    public function collection(): Collection
    {
        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
        ]);

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        if ($this->distributionCenterId) {
            $query->where('distribution_center_id', $this->distributionCenterId);
        }

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();

        return $query->latest()
            ->get()
            ->map(function ($item) use ($slaDvkh, $slaPtn) {
                $minutes = Carbon::parse($item->created_at)->diffInMinutes(now());

                $slaStatus = 'Bình thường';

                if ($item->status === 'WAIT_DVKH' && $slaDvkh) {
                    if ($minutes >= $slaDvkh->limit_minutes) {
                        $slaStatus = 'Quá hạn DVKH';
                    } elseif ($minutes >= $slaDvkh->warning_minutes) {
                        $slaStatus = 'Gần quá hạn DVKH';
                    }
                }

                if (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING']) && $slaPtn) {
                    if ($minutes >= $slaPtn->limit_minutes) {
                        $slaStatus = 'Quá hạn PTN';
                    } elseif ($minutes >= $slaPtn->warning_minutes) {
                        $slaStatus = 'Gần quá hạn PTN';
                    }
                }

                return [
                    'so_yeu_cau' => $item->request_no,
                    'ngay_tao' => optional($item->created_at)->format('d/m/Y H:i'),
                    'trung_tam' => $item->distributionCenter->name ?? '',
                    'khach_hang' => $item->customer->customer_name ?? '',
                    'cong_trinh' => $item->customer->project_name ?? '',
                    'dia_diem_cong_trinh' => $item->customer->project_address ?? '',
                    'ngay_xuat_hang' => $item->delivery_date ? $item->delivery_date->format('d/m/Y') : '',
                    'so_hoa_don' => $item->invoice_no,
                    'yeu_cau_ky_tuoi' => $item->require_hard_copy ? 'Có' : 'Không',
                    'so_ban_ky_tuoi' => $item->hard_copy_quantity,
                    'trang_thai' => $this->statusText($item->status),
                    'nguoi_tao' => $item->creator->name ?? '',
                    'thoi_gian_cho_hien_tai_phut' => $minutes,
                    'canh_bao_sla' => $slaStatus,
                    'ghi_chu' => $item->note,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'so_yeu_cau',
            'ngay_tao',
            'trung_tam',
            'khach_hang',
            'cong_trinh',
            'dia_diem_cong_trinh',
            'ngay_xuat_hang',
            'so_hoa_don',
            'yeu_cau_ky_tuoi',
            'so_ban_ky_tuoi',
            'trang_thai',
            'nguoi_tao',
            'thoi_gian_cho_hien_tai_phut',
            'canh_bao_sla',
            'ghi_chu',
        ];
    }

    private function statusText(?string $status): string
    {
        return match ($status) {
            'DRAFT' => 'Nháp',
            'WAIT_DVKH' => 'Chờ DVKH',
            'WAIT_PTN' => 'Chờ PTN',
            'PTN_PROCESSING' => 'PTN đang xử lý',
            'SIGNED' => 'Đã ký số',
            'COMPLETED' => 'Hoàn tất',
            'CANCELLED' => 'Hủy/Trả lại',
            default => $status ?? '',
        };
    }
}