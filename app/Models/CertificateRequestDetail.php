<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateRequestDetail extends Model
{
    protected $fillable = [
        'certificate_request_id',
        'product_id',
        'quantity',
    ];

    public function request()
    {
        return $this->belongsTo(CertificateRequest::class, 'certificate_request_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}