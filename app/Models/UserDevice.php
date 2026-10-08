<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'user_id',
        'device_uid',
        'device_name',
        'status',
        'ip_address',
        'user_agent',
        'requested_at',
        'approved_at',
        'approved_by',
        'blocked_at',
        'blocked_by',
        'last_used_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'blocked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function blockedBy()
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_BLOCKED => 'Đã khóa',
            default => 'Chờ duyệt',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'badge-success',
            self::STATUS_BLOCKED => 'badge-danger',
            default => 'badge-warning',
        };
    }
}
