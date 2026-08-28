<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QualityCertificateDetail extends Model
{
    protected $fillable = [
        'quality_certificate_id',
        'product_id',
        'quantity',
        'nominal_size',
        'technical_requirements',
        'quality_standard',
    ];

    public function certificate()
    {
        return $this->belongsTo(QualityCertificate::class, 'quality_certificate_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
