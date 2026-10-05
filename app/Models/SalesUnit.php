<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'distribution_center_id',
        'code',
        'name',
        'address',
        'phone',
        'tax_code',
        'bank_account',
        'representative',
        'note',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function distributionCenter()
    {
        return $this->belongsTo(DistributionCenter::class);
    }

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class);
    }
}
