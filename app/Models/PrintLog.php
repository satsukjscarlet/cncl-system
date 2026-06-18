<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintLog extends Model
{
    protected $fillable = [
        'quality_certificate_id',
        'user_id',
        'reason',
        'print_no',
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