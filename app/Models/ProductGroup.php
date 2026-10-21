<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'default_quality_standard_id',
        'is_active',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function defaultQualityStandard()
    {
        return $this->belongsTo(QualityStandard::class, 'default_quality_standard_id')->withTrashed();
    }
}
