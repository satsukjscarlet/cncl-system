<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributionCenter extends Model
{
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'contact_person',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function certificateRequests()
    {
        return $this->hasMany(CertificateRequest::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
