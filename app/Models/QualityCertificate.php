<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityCertificate extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_WAIT_PTN_MANAGER_APPROVAL = 'WAIT_PTN_MANAGER_APPROVAL';
    public const STATUS_READY_TO_SIGN = 'READY_TO_SIGN';
    public const STATUS_SIGN_PENDING = 'SIGN_PENDING';
    public const STATUS_SIGN_EXPIRED = 'SIGN_EXPIRED';
    public const STATUS_ISSUED = 'ISSUED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_REVOKED = 'REVOKED';

    protected $fillable = [
        'certificate_no',
        'status',
        'certificate_request_id',
        'replaces_certificate_id',
        'replaced_by_certificate_id',
        'created_by',
        'signed_at',
        'signed_by',
        'pdf_path',
        'print_count',
        'revoked_at',
        'revoked_by',
        'revoked_reason',
        'rejected_at',
        'rejected_by',
        'rejected_to',
        'rejected_reason',
        'smartca_status',
        'smartca_transaction_id',
        'smartca_tran_code',
        'smartca_doc_id',
        'smartca_data_hash',
        'smartca_certificate_data',
        'smartca_chain_data',
        'smartca_certificate_serial',
        'smartca_signature_value',
        'smartca_timestamp_signature',
        'smartca_response',
        'smartca_requested_at',
        'smartca_completed_at',
        'pades_status',
        'pades_prepared_pdf_path',
        'pades_state_path',
        'pades_error',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'revoked_at' => 'datetime',
        'rejected_at' => 'datetime',
        'smartca_chain_data' => 'array',
        'smartca_response' => 'array',
        'smartca_requested_at' => 'datetime',
        'smartca_completed_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(CertificateRequest::class, 'certificate_request_id')->withTrashed();
    }

    public function details()
    {
        return $this->hasMany(QualityCertificateDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function replacesCertificate()
    {
        return $this->belongsTo(self::class, 'replaces_certificate_id');
    }

    public function replacedByCertificate()
    {
        return $this->belongsTo(self::class, 'replaced_by_certificate_id');
    }

    public function replacementSourceCertificates()
    {
        return $this->hasMany(self::class, 'replaced_by_certificate_id');
    }

    public function reissueRequests()
    {
        return $this->belongsToMany(
            CertificateRequest::class,
            'certificate_request_reissue_certificates',
            'quality_certificate_id',
            'certificate_request_id'
        )->withTimestamps();
    }

    public function canRequestReissue(): bool
    {
        return $this->signed_at !== null
            && filled($this->pdf_path)
            && $this->status === 'ISSUED'
            && $this->replaced_by_certificate_id === null;
    }

    public function printLogs()
    {
        return $this->hasMany(PrintLog::class, 'quality_certificate_id');
    }

    public function displayStatusMeta(): array
    {
        if ($this->status === self::STATUS_REVOKED) {
            return ['class' => 'badge-danger', 'text' => 'Đã hủy / thu hồi', 'icon' => 'fas fa-ban'];
        }

        if ($this->status === self::STATUS_REJECTED) {
            return ['class' => 'badge-secondary', 'text' => 'Trưởng PTN trả lại', 'icon' => 'fas fa-undo'];
        }

        if ($this->signed_at || $this->status === self::STATUS_ISSUED) {
            return ['class' => 'badge-success', 'text' => 'Đã ký / phát hành', 'icon' => 'fas fa-check'];
        }

        if ($this->smartcaStatusExpired()) {
            return ['class' => 'badge-danger', 'text' => 'Quá hạn ký số', 'icon' => 'fas fa-hourglass-end'];
        }

        if ($this->smartca_status === 'PENDING') {
            return ['class' => 'badge-primary', 'text' => 'Đang chờ ký số', 'icon' => 'fas fa-mobile-alt'];
        }

        if ($this->status === self::STATUS_READY_TO_SIGN) {
            return ['class' => 'badge-warning', 'text' => 'Chờ gửi ký số', 'icon' => 'fas fa-paper-plane'];
        }

        if ($this->isAwaitingManagerApproval()) {
            return ['class' => 'badge-info', 'text' => 'Chờ Trưởng PTN duyệt', 'icon' => 'fas fa-user-check'];
        }

        if ($this->status === self::STATUS_SIGN_PENDING) {
            return ['class' => 'badge-primary', 'text' => 'Đang chờ ký số', 'icon' => 'fas fa-mobile-alt'];
        }

        if ($this->status === self::STATUS_SIGN_EXPIRED) {
            return ['class' => 'badge-danger', 'text' => 'Quá hạn ký số', 'icon' => 'fas fa-hourglass-end'];
        }

        return ['class' => 'badge-light', 'text' => $this->status ?: '-', 'icon' => 'fas fa-circle'];
    }

    public function isAwaitingManagerApproval(): bool
    {
        return in_array($this->status, [
            self::STATUS_WAIT_PTN_MANAGER_APPROVAL,
            self::STATUS_DRAFT, // Trạng thái cũ của dữ liệu trước khi tách bước duyệt.
        ], true)
            && !$this->signed_at
            && !in_array($this->smartca_status, ['PENDING', 'SIGNED'], true);
    }

    public function isReadyToSendSignature(): bool
    {
        return $this->status === self::STATUS_READY_TO_SIGN
            && !$this->signed_at
            && !in_array($this->smartca_status, ['PENDING', 'SIGNED'], true);
    }

    public function canSendSignatureRequest(): bool
    {
        return !$this->signed_at
            && !in_array($this->status, [self::STATUS_REJECTED, self::STATUS_REVOKED, self::STATUS_ISSUED], true)
            && (
                $this->isAwaitingManagerApproval()
                || $this->isReadyToSendSignature()
                || $this->smartcaStatusExpired()
            );
    }

    public function canApproveForSigningQueue(): bool
    {
        return $this->isAwaitingManagerApproval();
    }

    public function smartcaStatusExpired(): bool
    {
        if ($this->smartca_status === 'EXPIRED') {
            return true;
        }

        if ($this->smartca_status !== 'PENDING') {
            return false;
        }

        $requestedAt = $this->smartca_requested_at ?: $this->updated_at;

        if (!$requestedAt) {
            return false;
        }

        return $requestedAt->copy()
            ->addMinutes(max(1, (int) config('services.smartca.pending_ttl_minutes', 5)))
            ->lte(now());
    }
}
