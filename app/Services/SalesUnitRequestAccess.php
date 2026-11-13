<?php

namespace App\Services;

use App\Helpers\ActivityLogger;
use App\Models\CertificateRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesUnitRequestAccess
{
    public function transition(CertificateRequest $item, User $user, string $action, ?string $reason = null, bool $commitment = false): CertificateRequest
    {
        $this->validateAccount($user);
        if ($action !== 'return') \App\Support\RequestSubmissionEmail::validate($user);
        $item = DB::transaction(function () use ($item, $user, $action, $reason, $commitment) {
            $item = CertificateRequest::whereKey($item->id)->lockForUpdate()->firstOrFail();
            abort_unless($item->request_source === 'SALES_UNIT' && $this->canEdit($user, $item), 409, 'Yêu cầu không còn ở bước bạn được xử lý.');
            $old = $item->toArray();
            if ($action === 'return') {
                abort_if($user->isSalesUnit(), 403);
                throw_if(blank($reason), ValidationException::withMessages(['reason' => 'Vui lòng nhập lý do trả lại.']));
                $item->forceFill(['status' => 'RETURNED_SALES_UNIT', 'submitted_at' => null, 'submitted_by' => null,
                    'center_reviewed_at' => null, 'center_reviewed_by' => null,
                    'last_returned_from' => 'TRUNG_TAM', 'last_returned_to' => 'DON_VI_BAN_HANG',
                    'last_return_reason' => $reason, 'last_returned_at' => now(), 'last_returned_by' => $user->id])->save();
            } else {
                $unit = $item->salesUnit;
                abort_unless($unit && ! $unit->trashed() && $unit->is_active
                    && (int) $unit->distribution_center_id === (int) $item->distribution_center_id, 422, 'Đơn vị bán hàng không còn hợp lệ với Trung tâm của yêu cầu.');
                abort_unless($item->details()->exists() && $item->delivery_date && ($commitment || $item->customer_commitment_confirmed)
                    && $item->details()->where(fn ($q) => $q->whereNull('quantity')->orWhere('quantity', '<', 1))->doesntExist(), 422, 'Yêu cầu thiếu thông tin, sản phẩm hoặc cam kết. Vui lòng chỉnh sửa trước khi gửi.');
                $item->forceFill($user->isSalesUnit()
                    ? ['status' => 'WAIT_CENTER', 'sales_unit_submitted_at' => now(), 'sales_unit_submitted_by' => $user->id]
                    : ['status' => 'WAIT_DVKH', 'submitted_at' => now(), 'submitted_by' => $user->id,
                        'center_reviewed_at' => now(), 'center_reviewed_by' => $user->id]);
                $item->forceFill(['customer_commitment_confirmed' => true, 'last_returned_from' => null, 'last_returned_to' => null, 'last_return_reason' => null,
                    'last_returned_at' => null, 'last_returned_by' => null])->save();
            }
            ActivityLogger::log('Yêu cầu cấp phiếu', $action === 'return' ? 'center_return_sales_unit' : 'submit',
                ($action === 'return' ? 'Trung tâm trả lại đơn vị bán hàng: ' : 'Gửi yêu cầu: ').$item->request_no,
                $old, $item->fresh()->toArray(), $item);

            return $item;
        });
        app(NotificationService::class)->notifySalesUnitRequest($item);
        if ($item->status === 'WAIT_DVKH') {
            app(NotificationService::class)->notifyRequestCreated($item);
        }

        return $item;
    }

    public function validateAccount(User $user): void
    {
        if (! $user->isSalesUnit()) {
            return;
        }
        $unit = $user->salesUnit;
        abort_unless($unit && $unit->is_active && $unit->distribution_center_id
            && (int) $unit->distribution_center_id === (int) $user->distribution_center_id
            && $unit->distributionCenter?->is_active, 403, 'Đơn vị bán hàng hoặc Trung tâm chưa được gán hợp lệ / đã đình chỉ.');
    }

    public function canEdit(User $user, CertificateRequest $item): bool
    {
        if ($item->request_source !== 'SALES_UNIT') {
            return ! $user->isSalesUnit() && $item->status === 'DRAFT';
        }
        if ($user->isSalesUnit()) {
            return (int) $item->sales_unit_id === (int) $user->sales_unit_id
                && (int) $item->distribution_center_id === (int) $user->distribution_center_id
                && ($item->status === 'RETURNED_SALES_UNIT' || ($item->status === 'DRAFT' && $item->last_returned_to !== 'TRUNG_TAM'));
        }

        return ($user->hasRole('Admin') || ($user->hasRole('TrungTam') && (int) $item->distribution_center_id === (int) $user->distribution_center_id))
            && ($item->status === 'WAIT_CENTER' || ($item->status === 'DRAFT' && $item->last_returned_to === 'TRUNG_TAM'));
    }
}
