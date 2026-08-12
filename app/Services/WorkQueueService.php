<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class WorkQueueService
{
    public function forUser(User $user): array
    {
        $role = $this->primaryRole($user);
        $items = $this->itemsForRole($role, $user);
        $total = collect($items)->sum('count');

        return [
            'role' => $role,
            'total' => $total,
            'items' => $items,
        ];
    }

    private function itemsForRole(string $role, User $user): array
    {
        $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());
        $requests = $this->requestScope($user);
        $certificates = $this->certificateScope($user);

        return match ($role) {
            'TrungTam' => [
                $this->item('Yêu cầu bị trả lại', (clone $requests)->where('status', 'CANCELLED')->count(), 'fas fa-reply', 'danger', route('certificate-requests.index', ['status' => 'CANCELLED'])),
                $this->item('Đang chờ DVKH', (clone $requests)->where('status', 'WAIT_DVKH')->count(), 'fas fa-user-check', 'warning', route('certificate-requests.index', ['status' => 'WAIT_DVKH'])),
                $this->item('Đang chờ PTN', (clone $requests)->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING'])->count(), 'fas fa-vials', 'info', route('certificate-requests.index', ['status' => 'WAIT_PTN'])),
            ],
            'DVKH' => [
                $this->item('Yêu cầu chờ kiểm tra', (clone $requests)->where('status', 'WAIT_DVKH')->count(), 'fas fa-user-check', 'warning', route('dvkh.requests.index', ['status' => 'WAIT_DVKH'])),
                $this->item('Yêu cầu gấp', (clone $requests)->where('status', 'WAIT_DVKH')->where('is_urgent', true)->count(), 'fas fa-bolt', 'danger', route('dvkh.requests.index', ['status' => 'WAIT_DVKH'])),
                $this->item('Trùng số hóa đơn', $this->duplicateInvoiceCount((clone $requests)->where('status', 'WAIT_DVKH')), 'fas fa-copy', 'warning', route('dvkh.requests.index', ['duplicate_invoice' => '1'])),
            ],
            'PTN' => [
                $this->item('Chờ PTN tiếp nhận', (clone $requests)->where('status', 'WAIT_PTN')->count(), 'fas fa-inbox', 'warning', route('ptn.requests.index', ['status' => 'WAIT_PTN'])),
                $this->item('PTN đang lập phiếu', (clone $requests)->where('status', 'PTN_PROCESSING')->count(), 'fas fa-vials', 'primary', route('ptn.requests.index', ['status' => 'PTN_PROCESSING'])),
                $this->item('Yêu cầu gấp', (clone $requests)->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING'])->where('is_urgent', true)->count(), 'fas fa-bolt', 'danger', route('ptn.requests.index')),
            ],
            'TruongPTN' => [
                $this->item('Phiếu sẵn sàng ký', $this->signReadyCount(clone $certificates), 'fas fa-pen-nib', 'primary', route('quality-certificates.signing-queue', ['status' => 'READY'])),
                $this->item('Đang chờ xác nhận app', $this->signPendingCount(clone $certificates, $expiredBefore), 'fas fa-mobile-alt', 'warning', route('quality-certificates.signing-queue', ['status' => 'PENDING'])),
                $this->item('Quá hạn ký cần xử lý', $this->signExpiredCount(clone $certificates, $expiredBefore), 'fas fa-hourglass-end', 'danger', route('quality-certificates.signing-queue', ['status' => 'EXPIRED'])),
            ],
            default => [
                $this->item('Chờ DVKH', (clone $requests)->where('status', 'WAIT_DVKH')->count(), 'fas fa-user-check', 'warning', route('certificate-requests.index', ['status' => 'WAIT_DVKH'])),
                $this->item('Chờ PTN', (clone $requests)->whereIn('status', ['WAIT_PTN', 'PTN_PROCESSING'])->count(), 'fas fa-vials', 'info', route('certificate-requests.index', ['status' => 'WAIT_PTN'])),
                $this->item('Phiếu chờ ký', $this->signReadyCount(clone $certificates), 'fas fa-file-signature', 'primary', route('quality-certificates.index', ['status' => 'UNSIGNED'])),
            ],
        };
    }

    private function requestScope(User $user): Builder
    {
        $query = CertificateRequest::query();

        if ($user->hasRole('TrungTam')) {
            $query->where('distribution_center_id', $user->distribution_center_id);
        }

        return $query;
    }

    private function certificateScope(User $user): Builder
    {
        $query = QualityCertificate::query();

        if ($user->hasRole('TrungTam')) {
            $query->whereHas('request', function ($q) use ($user) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            });
        }

        return $query;
    }

    private function signReadyCount(Builder $query): int
    {
        return $query
            ->whereNull('signed_at')
            ->where('status', 'DRAFT')
            ->where(function ($q) {
                $q->whereNull('smartca_status')
                    ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
            })
            ->count();
    }

    private function signPendingCount(Builder $query, $expiredBefore): int
    {
        return $query
            ->whereNull('signed_at')
            ->where('smartca_status', 'PENDING')
            ->where('smartca_requested_at', '>', $expiredBefore)
            ->count();
    }

    private function signExpiredCount(Builder $query, $expiredBefore): int
    {
        return $query
            ->whereNull('signed_at')
            ->where(function ($q) use ($expiredBefore) {
                $q->where('smartca_status', 'EXPIRED')
                    ->orWhere(function ($pending) use ($expiredBefore) {
                        $pending->where('smartca_status', 'PENDING')
                            ->where('smartca_requested_at', '<=', $expiredBefore);
                    });
            })
            ->count();
    }

    private function duplicateInvoiceCount(Builder $query): int
    {
        return $query
            ->whereNotNull('invoice_no_normalized')
            ->whereExists(function ($subQuery) {
                $subQuery
                    ->selectRaw(1)
                    ->from('certificate_requests as duplicate_requests')
                    ->whereColumn('duplicate_requests.invoice_no_normalized', 'certificate_requests.invoice_no_normalized')
                    ->whereColumn('duplicate_requests.id', '!=', 'certificate_requests.id')
                    ->whereNull('duplicate_requests.deleted_at');
            })
            ->count();
    }

    private function primaryRole(User $user): string
    {
        foreach (['Admin', 'LanhDao', 'TrungTam', 'DVKH', 'PTN', 'TruongPTN'] as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return 'Viewer';
    }

    private function item(string $label, int $count, string $icon, string $color, string $url): array
    {
        return compact('label', 'count', 'icon', 'color', 'url');
    }

    private function smartCaPendingTtlMinutes(): int
    {
        return max((int) config('services.smartca.pending_ttl_minutes', 5), 1);
    }
}
