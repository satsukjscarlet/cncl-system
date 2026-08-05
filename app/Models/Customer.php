<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'distribution_center_id',
        'customer_code',
        'customer_name',
        'customer_address',
        'tax_code',
        'contact_person',
        'phone',
        'email',
        'project_name',
        'project_address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function distributionCenter()
    {
        return $this->belongsTo(DistributionCenter::class);
    }
}
