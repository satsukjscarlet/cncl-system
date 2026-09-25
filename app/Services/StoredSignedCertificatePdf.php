<?php

namespace App\Services;

use App\Models\QualityCertificate;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StoredSignedCertificatePdf
{
    public function isSigned(QualityCertificate $certificate): bool
    {
        return $certificate->signed_at !== null
            || $certificate->smartca_status === 'SIGNED'
            || $certificate->pades_status === 'SIGNED_PDF'
            || $certificate->status === 'ISSUED';
    }

    public function read(QualityCertificate $certificate): string
    {
        if (!$certificate->pdf_path || $certificate->pades_status !== 'SIGNED_PDF') {
            throw new RuntimeException('Phiếu chưa có file PDF đã nhúng chữ ký được lưu. Không tạo PDF thay thế.');
        }

        $content = Storage::disk('local')->get($certificate->pdf_path);
        if (!is_string($content) || !str_starts_with($content, '%PDF') || !str_contains($content, '/ByteRange')) {
            throw new RuntimeException('Không đọc được bản PDF ký số gốc. Vui lòng kiểm tra file và quyền truy cập.');
        }

        return $content;
    }
}
