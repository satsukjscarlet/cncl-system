<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CertificateRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_no',
        'request_type',
        'reissue_of_certificate_id',
        'reissue_reason',
        'distribution_center_id',
        'customer_id',
        'delivery_date',
        'invoice_no',
        'invoice_no_normalized',
        'require_hard_copy',
        'hard_copy_quantity',
        'is_urgent',
        'urgent_reason_id',
        'requester_name',
        'customer_commitment_confirmed',
        'note',
        'last_returned_from',
        'last_returned_to',
        'last_return_reason',
        'last_returned_at',
        'last_returned_by',
        'status',
        'submitted_at',
        'submitted_by',
        'sent_to_ptn_at',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'require_hard_copy' => 'boolean',
        'is_urgent' => 'boolean',
        'customer_commitment_confirmed' => 'boolean',
        'last_returned_at' => 'datetime',
        'submitted_at' => 'datetime',
        'sent_to_ptn_at' => 'datetime',
    ];

    public function distributionCenter()
    {
        return $this->belongsTo(DistributionCenter::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function details()
    {
        return $this->hasMany(CertificateRequestDetail::class);
    }

    public function qualityCertificate()
    {
        return $this->hasOne(QualityCertificate::class, 'certificate_request_id')
            ->where('status', '!=', 'REJECTED')
            ->latestOfMany();
    }

    public function qualityCertificates()
    {
        return $this->hasMany(QualityCertificate::class, 'certificate_request_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function lastReturnedBy()
    {
        return $this->belongsTo(User::class, 'last_returned_by');
    }

    public function effectiveLastReturnedTo(): ?string
    {
        if (filled($this->last_returned_to)) {
            return $this->last_returned_to;
        }

        $note = (string) $this->note;

        if ($this->status === 'DRAFT' && str_contains($note, '[DVKH trả lại]')) {
            return 'TRUNG_TAM';
        }

        if ($this->status === 'WAIT_DVKH' && str_contains($note, 'trả lại DVKH]')) {
            return 'DVKH';
        }

        if ($this->status === 'PTN_PROCESSING' && str_contains($note, 'Trưởng PTN trả lại') && str_contains($note, 'PTN xử lý lại')) {
            return 'PTN';
        }

        return null;
    }

    public function effectiveLastReturnedFrom(): ?string
    {
        if (filled($this->last_returned_from)) {
            return $this->last_returned_from;
        }

        $target = $this->effectiveLastReturnedTo();
        $note = (string) $this->note;

        if ($target === 'TRUNG_TAM') {
            return 'DVKH';
        }

        if ($target === 'PTN') {
            return 'TRUONG_PTN';
        }

        if ($target === 'DVKH') {
            $ptnPosition = mb_strripos($note, '[PTN trả lại DVKH]');
            $managerPosition = mb_strripos($note, '[Trưởng PTN trả lại');

            if ($managerPosition !== false && ($ptnPosition === false || $managerPosition > $ptnPosition)) {
                return 'TRUONG_PTN';
            }

            return 'PTN';
        }

        return null;
    }

    public function urgentReason()
    {
        return $this->belongsTo(UrgentReason::class);
    }

    public function reissueOfCertificate()
    {
        return $this->belongsTo(QualityCertificate::class, 'reissue_of_certificate_id');
    }

    public function reissueCertificates()
    {
        return $this->belongsToMany(
            QualityCertificate::class,
            'certificate_request_reissue_certificates',
            'certificate_request_id',
            'quality_certificate_id'
        )->withTimestamps();
    }

    public function displayStatusMeta(): array
    {
        $certificate = $this->qualityCertificate;

        if ($certificate) {
            return $certificate->displayStatusMeta();
        }

        return self::statusMeta($this->status);
    }

    public static function statusMeta(?string $status): array
    {
        $map = [
            'DRAFT' => ['class' => 'badge-secondary', 'text' => 'Nháp'],
            'WAIT_DVKH' => ['class' => 'badge-warning', 'text' => 'Chờ DVKH kiểm tra'],
            'WAIT_PTN' => ['class' => 'badge-info', 'text' => 'Chờ PTN lập phiếu'],
            'PTN_PROCESSING' => ['class' => 'badge-primary', 'text' => 'Đã lập phiếu - Chờ Trưởng PTN ký'],
            'SIGNED' => ['class' => 'badge-success', 'text' => 'Đã ký số'],
            'COMPLETED' => ['class' => 'badge-success', 'text' => 'Hoàn tất'],
            'CANCELLED' => ['class' => 'badge-danger', 'text' => 'Đã trả lại / hủy'],
        ];

        return $map[$status] ?? ['class' => 'badge-light', 'text' => $status ?: '-'];
    }

    public function setInvoiceNoAttribute($value): void
    {
        $invoiceNo = blank($value) ? null : trim((string) $value);

        $this->attributes['invoice_no'] = $invoiceNo;
        $this->attributes['invoice_no_normalized'] = self::normalizeInvoiceNo($invoiceNo);
    }

    public static function normalizeInvoiceNo(?string $invoiceNo): ?string
    {
        if ($invoiceNo === null || trim($invoiceNo) === '') {
            return null;
        }

        return preg_replace('/\s+/', '', Str::upper(trim($invoiceNo)));
    }

    public static function duplicateInvoiceQuery(?string $invoiceNo, ?int $excludeId = null)
    {
        $normalized = self::normalizeInvoiceNo($invoiceNo);

        $query = self::with([
            'distributionCenter',
            'customer',
            'qualityCertificate',
        ])->where('invoice_no_normalized', $normalized);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($normalized === null) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }
}
