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
        'note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'require_hard_copy' => 'boolean',
        'is_urgent' => 'boolean',
    ];

    public function distributionCenter()
    {
        return $this->belongsTo(DistributionCenter::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

    public function urgentReason()
    {
        return $this->belongsTo(UrgentReason::class);
    }

    public function reissueOfCertificate()
    {
        return $this->belongsTo(QualityCertificate::class, 'reissue_of_certificate_id');
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
