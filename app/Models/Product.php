<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_group_id',
        'quality_standard_id',
        'product_code',
        'product_name',
        'unit',
        'nominal_size',
        'technical_requirements',
        'certificate_type',
        'certificate_template',
        'note',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(ProductGroup::class, 'product_group_id')->withTrashed();
    }

    public function qualityStandard()
    {
        return $this->belongsTo(QualityStandard::class, 'quality_standard_id')->withTrashed();
    }
}
