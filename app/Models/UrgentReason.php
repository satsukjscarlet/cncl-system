<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UrgentReason extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class);
    }
}
