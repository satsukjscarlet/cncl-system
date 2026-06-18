<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'request_no',
        'distribution_center_id',
        'customer_id',
        'delivery_date',
        'invoice_no',
        'require_hard_copy',
        'hard_copy_quantity',
        'note',
        'status',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'require_hard_copy' => 'boolean',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}