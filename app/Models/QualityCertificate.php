<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityCertificate extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(CertificateRequest::class, 'certificate_request_id');
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
        if ($this->status === 'REVOKED') {
            return ['class' => 'badge-danger', 'text' => 'Đã hủy / thu hồi', 'icon' => 'fas fa-ban'];
        }

        if ($this->status === 'REJECTED') {
            return ['class' => 'badge-secondary', 'text' => 'Trưởng PTN trả lại', 'icon' => 'fas fa-undo'];
        }

        if ($this->signed_at || $this->status === 'ISSUED') {
            return ['class' => 'badge-success', 'text' => 'Đã ký / phát hành', 'icon' => 'fas fa-check'];
        }

        if ($this->smartcaStatusExpired()) {
            return ['class' => 'badge-danger', 'text' => 'Quá hạn ký số', 'icon' => 'fas fa-hourglass-end'];
        }

        if ($this->smartca_status === 'PENDING') {
            return ['class' => 'badge-primary', 'text' => 'Đang chờ ký số', 'icon' => 'fas fa-mobile-alt'];
        }

        if ($this->status === 'DRAFT') {
            return ['class' => 'badge-warning', 'text' => 'Chờ Trưởng PTN ký', 'icon' => 'fas fa-pen-nib'];
        }

        return ['class' => 'badge-light', 'text' => $this->status ?: '-', 'icon' => 'fas fa-circle'];
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
