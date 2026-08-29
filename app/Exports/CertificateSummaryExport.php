<?php

namespace App\Exports;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
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
        private readonly ?int $distributionCenterId = null,
        private readonly ?string $certificateStatus = null
    ) {
    }

    public function collection(): Collection
    {
        $query = CertificateRequest::with([
            'distributionCenter',
            'customer',
            'creator',
            'qualityCertificates',
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

        if ($this->certificateStatus) {
            $this->applyCertificateStatusFilter($query, $this->certificateStatus);
        }

        $slaDvkh = SlaConfig::where('code', 'SLA_DVKH')->where('is_active', true)->first();
        $slaPtn = SlaConfig::where('code', 'SLA_PTN')->where('is_active', true)->first();

        return $query->latest()
            ->get()
            ->map(function ($item) use ($slaDvkh, $slaPtn) {
                $certificate = $item->qualityCertificates
                    ->sortByDesc(fn ($certificate) => optional($certificate->created_at)->timestamp ?? 0)
                    ->first();
                $minutes = Carbon::parse($item->created_at)->diffInMinutes(now());
                $slaStatus = 'Bình thường';

                if ($item->status === 'WAIT_DVKH' && $slaDvkh) {
                    if ($minutes >= $slaDvkh->limit_minutes) {
                        $slaStatus = 'Quá hạn DVKH';
                    } elseif ($minutes >= $slaDvkh->warning_minutes) {
                        $slaStatus = 'Gần quá hạn DVKH';
                    }
                }

                if (in_array($item->status, ['WAIT_PTN', 'PTN_PROCESSING'], true) && $slaPtn) {
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

    private function applyCertificateStatusFilter($query, string $status): void
    {
        if ($status === 'NO_CERTIFICATE') {
            $query->whereDoesntHave('qualityCertificates');

            return;
        }

        $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

        if ($status === 'WAIT_PTN_MANAGER_APPROVAL') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->whereNull('signed_at')
                    ->whereIn('status', [
                        QualityCertificate::STATUS_DRAFT,
                        QualityCertificate::STATUS_WAIT_PTN_MANAGER_APPROVAL,
                    ])
                    ->where(function ($q) {
                        $q->whereNull('smartca_status')
                            ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                    });
            });

            return;
        }

        if ($status === 'READY_TO_SIGN') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->whereNull('signed_at')
                    ->where('status', QualityCertificate::STATUS_READY_TO_SIGN)
                    ->where(function ($q) {
                        $q->whereNull('smartca_status')
                            ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                    });
            });

            return;
        }

        if ($status === 'SIGN_PENDING') {
            $query->whereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                $certificate
                    ->whereNull('signed_at')
                    ->where('smartca_status', 'PENDING')
                    ->where('smartca_requested_at', '>', $expiredBefore);
            });

            return;
        }

        if ($status === 'SIGN_EXPIRED') {
            $query->whereHas('qualityCertificates', function ($certificate) use ($expiredBefore) {
                $certificate
                    ->whereNull('signed_at')
                    ->where(function ($q) use ($expiredBefore) {
                        $q->where('status', QualityCertificate::STATUS_SIGN_EXPIRED)
                            ->orWhere('smartca_status', 'EXPIRED')
                            ->orWhere(function ($pending) use ($expiredBefore) {
                                $pending->where('smartca_status', 'PENDING')
                                    ->where('smartca_requested_at', '<=', $expiredBefore);
                            });
                    });
            });

            return;
        }

        if ($status === 'ISSUED') {
            $query->whereHas('qualityCertificates', function ($certificate) {
                $certificate
                    ->where('status', QualityCertificate::STATUS_ISSUED)
                    ->whereNotNull('signed_at');
            });

            return;
        }

        if (in_array($status, [
            QualityCertificate::STATUS_REJECTED,
            QualityCertificate::STATUS_REVOKED,
        ], true)) {
            $query->whereHas('qualityCertificates', fn ($certificate) => $certificate->where('status', $status));
        }
    }

    private function smartCaPendingTtlMinutes(): int
    {
        return max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
    }
}
