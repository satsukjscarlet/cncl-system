<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QualityCertificate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'certificate_no',
        'certificate_request_id',
        'created_by',
        'signed_at',
        'signed_by',
        'pdf_path',
        'print_count',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(CertificateRequest::class, 'certificate_request_id');
    }

    public function details()
    {
        return $this->hasMany(QualityCertificateDetail::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function printLogs()
    {
        return $this->hasMany(PrintLog::class, 'quality_certificate_id');
    }
}