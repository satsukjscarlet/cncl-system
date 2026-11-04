<?php

namespace App\Exports;

use App\Models\SlaConfig;
use App\Services\SlaClockService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CertificateSummaryExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly ?string $dateFrom = null,
        private readonly ?string $dateTo = null,
        private readonly ?string $status = null,
        private readonly ?int $distributionCenterId = null,
        private readonly ?string $certificateStatus = null,
        private readonly string $dateBasis = 'request_created_at',
        private readonly ?\App\Models\User $user = null
    ) {
    }

    public function collection(): Collection
    {
        $user = $this->user ?? auth()->user();
        abort_unless($user, 403);
        $filters = \Illuminate\Http\Request::create('/reports/summary', 'GET', array_filter([
            'date_from' => $this->dateFrom, 'date_to' => $this->dateTo, 'date_basis' => $this->dateBasis,
            'request_status' => $this->status, 'distribution_center_id' => $this->distributionCenterId,
            'certificate_status' => $this->certificateStatus,
        ], fn ($value) => $value !== null && $value !== ''));
        $service = app(\App\Services\CertificateReportQuery::class);
        $query = $service->requestBase($filters, $user)->with(['distributionCenter', 'customer', 'creator', 'reportCertificate']);
        if ($this->certificateStatus) $service->requestStatus($query, $this->certificateStatus);

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();
        $slaClock = app(SlaClockService::class);

        return $query->latest()
            ->get()
            ->map(function ($item) use ($slaDvkh, $slaPtn, $slaClock) {
                $certificate = $item->reportCertificate;
                $step = $item->status === 'WAIT_DVKH' ? 'DVKH' : (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING'], true) ? 'PTN' : null);
                $minutes = $step ? $slaClock->elapsedMinutes($item, $step) : null;
                $slaStatus = $step && !($step === 'DVKH' ? $slaDvkh : $slaPtn) ? 'Chưa bật SLA ' . $step : ($step ? 'Bình thường' : 'Không ở bước theo dõi SLA');

                if ($item->status === 'WAIT_DVKH' && $slaDvkh && $minutes !== null) {
                    if ($minutes >= $slaDvkh->limit_minutes) {
                        $slaStatus = 'Quá hạn DVKH';
                    } elseif ($minutes >= $slaDvkh->warning_minutes) {
                        $slaStatus = 'Gần quá hạn DVKH';
                    }
                }

                if (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING'], true) && $slaPtn && $minutes !== null) {
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
                    'so_phieu' => $certificate?->certificate_no ?? '',
                    'trang_thai_yeu_cau' => $this->statusText($item->status),
                    'trang_thai_phieu' => $certificate ? $certificate->displayStatusMeta()['text'] : 'Chưa lập phiếu',
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
            'Số yêu cầu',
            'Ngày tạo',
            'Trung tâm',
            'Khách hàng',
            'Công trình',
            'Địa điểm công trình',
            'Ngày xuất hàng',
            'Số hóa đơn',
            'Yêu cầu ký tươi',
            'Số bản ký tươi',
            'Số phiếu',
            'Trạng thái yêu cầu',
            'Trạng thái phiếu',
            'Người tạo',
            'Thời gian chờ hiện tại (phút)',
            'Cảnh báo SLA',
            'Ghi chú',
        ];
    }

    private function statusText(?string $status): string
    {
        return match ($status) {
            'DRAFT' => 'Nháp',
            'WAIT_DVKH' => 'Chờ DVKH kiểm tra',
            'WAIT_PTN' => 'Chờ PTN lập phiếu',
            'PTN_PROCESSING' => 'Đã lập phiếu - Chờ Trưởng PTN ký',
            'SIGNED' => 'Đã ký số',
            'COMPLETED' => 'Hoàn tất',
            'CANCELLED' => 'Đã trả lại / hủy',
            default => $status ?? '',
        };
    }

}
