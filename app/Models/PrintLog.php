<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintLog extends Model
{
    public function scopeInitialNormal($query)
    {
        return $query->where('print_logs.print_mode', 'normal')->whereNull('print_logs.hard_copy_supplement_id');
    }

    protected $fillable = [
        'quality_certificate_id',
        'user_id',
        'reason',
        'print_no',
        'print_template',
        'print_mode',
        'hard_copy_supplement_id',
    ];

    public function certificate()
    {
        return $this->belongsTo(QualityCertificate::class, 'quality_certificate_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
