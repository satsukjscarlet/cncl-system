<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HardCopySupplement extends Model
{
    public const LABELS = [
        'WAIT_DVKH' => 'Chờ DVKH xác nhận', 'RETURNED' => 'DVKH trả lại',
        'WAIT_PTN' => 'Chờ PTN hoàn tất', 'COMPLETED' => 'Đã hoàn tất', 'CANCELLED' => 'Đã hủy',
    ];
    protected $guarded = ['id'];
    protected $casts = ['quantity' => 'integer', 'page_count' => 'integer', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'completed_at' => 'datetime'];

    public function certificate() { return $this->belongsTo(QualityCertificate::class, 'quality_certificate_id')->withTrashed(); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function completer() { return $this->belongsTo(User::class, 'completed_by'); }
    public function events() { return $this->hasMany(HardCopySupplementEvent::class)->orderBy('id'); }
    public function printLogs() { return $this->hasMany(PrintLog::class); }
    public function getNumberAttribute(): string { return 'BSKT-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }
    public function getStatusLabelAttribute(): string { return self::LABELS[$this->status] ?? $this->status; }

    public function getStatusMetaAttribute(): array
    {
        return ['text' => $this->status_label, 'class' => match ($this->status) {
            'WAIT_DVKH' => 'badge-warning', 'RETURNED' => 'badge-danger', 'WAIT_PTN' => 'badge-info',
            'COMPLETED' => 'badge-success', default => 'badge-secondary',
        }];
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->hasRole('TrungTam')) {
            $query->whereHas('certificate.request', fn ($q) => $q->where('distribution_center_id', $user->distribution_center_id));
        }
        return $query;
    }
}
