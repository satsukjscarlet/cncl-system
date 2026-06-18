<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SlaConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'process_step',
        'warning_minutes',
        'limit_minutes',
        'description',
        'is_active',
    ];

    protected $casts = [
        'warning_minutes' => 'integer',
        'limit_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function processStepOptions(): array
    {
        return [
            'DVKH' => 'DVKH kiểm tra hồ sơ',
            'PTN' => 'PTN lập phiếu',
            'TOTAL' => 'Toàn trình',
        ];
    }

    public function getProcessStepNameAttribute(): string
    {
        return self::processStepOptions()[$this->process_step] ?? $this->process_step;
    }
}