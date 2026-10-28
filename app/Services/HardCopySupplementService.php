<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Models\HardCopySupplement;
use App\Models\QualityCertificate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HardCopySupplementService
{
    public function pendingQuery(User $user)
    {
        $statuses = [];
        if (!$user->hasRole('TrungTam')) {
            if ($user->hasRole('Admin') || $this->dvkh($user)) $statuses[] = 'WAIT_DVKH';
            if ($user->hasRole('Admin') || $this->ptn($user)) $statuses[] = 'WAIT_PTN';
        }
        return HardCopySupplement::visibleTo($user)->whereIn('hard_copy_supplements.status', $statuses)
            ->whereHas('certificate', fn ($q) => $q->hardCopySupplementEligible());
    }

    public function canView(User $user): bool
    {
        return $user->can('certificate.view') || (!$user->hasRole('TrungTam') && ($user->can('dvkh.process') || $user->can('ptn.process') || $user->can('report.view') || $user->can('report.export')));
    }
    public function center(User $user, QualityCertificate $certificate): bool
    {
        return $user->hasRole('TrungTam') && $user->can('request.create')
            && $user->distribution_center_id && (int) $user->distribution_center_id === (int) $certificate->request?->distribution_center_id;
    }
    public function dvkh(User $user): bool { return !$user->hasRole('TrungTam') && $user->can('dvkh.process'); }
    public function ptn(User $user): bool { return !$user->hasRole('TrungTam') && $user->can('ptn.process') && $user->can('certificate.print'); }
    public function requireValid(QualityCertificate $certificate): void
    {
        if (!$certificate->allowsHardCopySupplement()) {
            throw ValidationException::withMessages(['supplement' => 'Phiếu chưa phát hành hợp lệ, đã hủy/thu hồi hoặc đang có yêu cầu cấp lại. Không thể tiếp tục bổ sung bản ký tươi.']);
        }
    }

    public function create(User $user, QualityCertificate $certificate, array $data): HardCopySupplement
    {
        return DB::transaction(function () use ($user, $certificate, $data) {
            $certificate = QualityCertificate::lockForUpdate()->findOrFail($certificate->id);
            abort_unless($this->center($user, $certificate), 403);
            $this->requireValid($certificate);
            if ($existing = $certificate->hardCopySupplements()->whereNotNull('active_certificate_id')->first()) {
                throw ValidationException::withMessages(['supplement' => 'Đã có yêu cầu ' . $existing->number . ' chưa kết thúc. Vui lòng mở yêu cầu đó để theo dõi.']);
            }
            $item = HardCopySupplement::create($data + [
                'quality_certificate_id' => $certificate->id, 'active_certificate_id' => $certificate->id,
                'requested_by' => $user->id, 'submitted_at' => now(), 'status' => 'WAIT_DVKH',
            ]);
            $this->record($item, $user, 'created', null);
            return $item;
        });
    }

    public function transition(User $user, HardCopySupplement $item, string $action, array $data = []): HardCopySupplement
    {
        return DB::transaction(function () use ($user, $item, $action, $data) {
            // All mutations lock certificate first, then supplement, to serialize approval/edit/print.
            $certificate = QualityCertificate::lockForUpdate()->findOrFail($item->quality_certificate_id);
            $item = HardCopySupplement::lockForUpdate()->findOrFail($item->id);
            $from = $item->status;
            if ($action === 'cancel') {
                abort_unless(($this->center($user, $certificate) && in_array($from, ['WAIT_DVKH', 'RETURNED']))
                    || ($this->dvkh($user) && in_array($from, ['WAIT_DVKH', 'RETURNED', 'WAIT_PTN'])), 403);
                if ($from === 'WAIT_PTN' && $item->printLogs()->exists() && $certificate->allowsHardCopySupplement() && !($data['confirm_stop_printing'] ?? false)) {
                    throw ValidationException::withMessages(['supplement' => 'PTN đã mở file in. DVKH cần xác nhận đã phối hợp PTN dừng xử lý bản giấy trước khi hủy.']);
                }
                $item->fill(['status' => 'CANCELLED', 'active_certificate_id' => null]);
            } else {
                $this->requireValid($certificate);
                if ($action === 'resubmit') {
                    abort_unless($this->center($user, $certificate), 403);
                    $this->expect($from, ['WAIT_DVKH', 'RETURNED']);
                    $item->fill($data + ['status' => 'WAIT_DVKH', 'submitted_at' => $from === 'RETURNED' ? now() : $item->submitted_at]);
                } elseif (in_array($action, ['approve', 'return'])) {
                    abort_unless($this->dvkh($user), 403);
                    $this->expect($from, $action === 'approve' ? ['WAIT_DVKH'] : ['WAIT_DVKH', 'WAIT_PTN']);
                    if ($item->printLogs()->exists()) {
                        throw ValidationException::withMessages(['supplement' => 'PTN đã mở file in, không thể trả lại hoặc duyệt lại.']);
                    }
                    $item->fill($action === 'approve' ? ['status' => 'WAIT_PTN', 'approved_by' => $user->id, 'approved_at' => now()]
                        : ['status' => 'RETURNED', 'approved_by' => null, 'approved_at' => null]);
                } elseif ($action === 'complete') {
                    abort_unless($this->ptn($user), 403);
                    $this->expect($from, ['WAIT_PTN']);
                    if (!$item->print_template || !$item->page_count) {
                        throw ValidationException::withMessages(['supplement' => 'Vui lòng chọn In đơn hoặc In bộ và mở file in trước khi xác nhận hoàn tất.']);
                    }
                    $item->fill(['status' => 'COMPLETED', 'active_certificate_id' => null, 'completed_by' => $user->id,
                        'completed_at' => now(), 'completion_note' => $data['completion_note'] ?? null]);
                } else {
                    abort(404);
                }
            }
            $item->save();
            $this->record($item, $user, $action, $from, $data);
            return $item;
        });
    }

    public function expect(string $status, array $allowed): void
    {
        if (!in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['supplement' => 'Trạng thái yêu cầu đã thay đổi. Vui lòng tải lại trang trước khi thao tác.']);
        }
    }

    public function record(HardCopySupplement $item, User $user, string $action, ?string $from, array $data = []): void
    {
        $item->events()->create(['user_id' => $user->id, 'action' => $action, 'from_status' => $from,
            'to_status' => $item->status, 'data' => $data + ['quantity' => $item->quantity, 'reason' => $item->reason,
                'note' => $item->note, 'print_template' => $item->print_template, 'page_count' => $item->page_count]]);
        ActivityLogger::log('Bổ sung bản ký tươi', $action, $item->number . ': ' . $item->status_label, null, $item->toArray(), $item);
        if ($action === 'printed') return;
        $payload = ['type' => 'hard_copy_supplement', 'title' => $item->number . ': ' . $item->status_label,
            'message' => 'Phiếu ' . $item->certificate->certificate_no . ', bổ sung ' . $item->quantity . ' bản.',
            'url' => route('hard-copy-supplements.show', $item), 'data' => ['hard_copy_supplement_id' => $item->id]];
        $notifications = app(NotificationService::class);
        if ($item->status === 'WAIT_DVKH') $notifications->sendToPermission('dvkh.process', $payload);
        if ($item->status === 'WAIT_PTN') $notifications->sendToPermission('ptn.process', $payload);
        if ($action === 'cancel') $notifications->sendToPermission($from === 'WAIT_PTN' ? 'ptn.process' : 'dvkh.process', $payload);
        $notifications->sendToCenter($item->certificate->request->distribution_center_id, $payload);
    }
}
