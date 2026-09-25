<?php

namespace Tests\Feature;

use App\Models\QualityCertificate;
use App\Services\StoredSignedCertificatePdf;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class StoredSignedCertificatePdfTest extends TestCase
{
    public function test_returns_original_bytes_without_rendering(): void
    {
        Storage::fake('local');
        $bytes = '%PDF-1.7 original /ByteRange [0 10 20 30]';
        Storage::disk('local')->put('signed.pdf', $bytes);
        $certificate = new QualityCertificate(['status' => 'ISSUED', 'pades_status' => 'SIGNED_PDF', 'pdf_path' => 'signed.pdf']);
        $this->assertSame($bytes, app(StoredSignedCertificatePdf::class)->read($certificate));
    }

    public function test_missing_signed_file_never_falls_back_to_rendering(): void
    {
        Storage::fake('local');
        $certificate = new QualityCertificate(['status' => 'ISSUED', 'pades_status' => 'SIGNED_PDF']);
        $this->expectException(RuntimeException::class);
        app(StoredSignedCertificatePdf::class)->read($certificate);
    }

    public function test_hash_only_certificate_is_not_treated_as_signed_pdf(): void
    {
        $certificate = new QualityCertificate(['status' => 'ISSUED', 'pades_status' => 'SIGNATURE_ONLY', 'pdf_path' => 'original.pdf']);
        $this->expectException(RuntimeException::class);
        app(StoredSignedCertificatePdf::class)->read($certificate);
    }
}
