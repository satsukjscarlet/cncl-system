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
        'smartca_status',
        'smartca_transaction_id',
        'smartca_tran_code',
        'smartca_doc_id',
        'smartca_data_hash',
        'smartca_certificate_data',
        'smartca_chain_data',
        'smartca_certificate_serial',
        'smartca_signature_value',
        'smartca_timestamp_signature',
        'smartca_response',
        'smartca_requested_at',
        'smartca_completed_at',
        'pades_status',
        'pades_prepared_pdf_path',
        'pades_state_path',
        'pades_error',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'smartca_chain_data' => 'array',
        'smartca_response' => 'array',
        'smartca_requested_at' => 'datetime',
        'smartca_completed_at' => 'datetime',
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
