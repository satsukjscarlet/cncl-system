<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\DistributionCenter;
use App\Models\QualityCertificate;
use Illuminate\Support\Carbon;
use RuntimeException;

class DocumentNumberService
{
    public function generateRequestNo(int $distributionCenterId, ?Carbon $date = null): string
    {
        $centerCode = $this->centerCode($distributionCenterId);
        $date ??= now();

        $prefix = 'YC-' . $date->format('Ymd') . '-';
        $suffix = '/' . $centerCode;
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d{4})' . preg_quote($suffix, '/') . '$/';

        $lastSequence = CertificateRequest::withTrashed()
            ->where('request_no', 'like', $prefix . '%' . $suffix)
            ->lockForUpdate()
            ->pluck('request_no')
            ->map(fn (string $requestNo) => preg_match($pattern, $requestNo, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        return $prefix . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT) . $suffix;
    }

    public function certificateNoFromRequestNo(string $requestNo): string
    {
        if (str_starts_with($requestNo, 'YC-')) {
            return 'CNCL-' . substr($requestNo, 3);
        }

        if (str_starts_with($requestNo, 'PTN-')) {
            return 'CNCL-' . substr($requestNo, 4);
        }

        return 'CNCL-' . $requestNo;
    }

    public function uniqueCertificateNoFromRequestNo(string $requestNo): string
    {
        $baseNo = $this->certificateNoFromRequestNo($requestNo);

        if (!QualityCertificate::withTrashed()->where('certificate_no', $baseNo)->exists()) {
            return $baseNo;
        }

        for ($index = 2; $index < 100; $index++) {
            $candidate = $baseNo . '-R' . $index;

            if (!QualityCertificate::withTrashed()->where('certificate_no', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new RuntimeException('Khong the tao so phieu CNCL khong trung.');
    }

    public function safeFileName(string $number, string $extension = 'pdf'): string
    {
        $name = preg_replace('/[\\\\\/:*?"<>|]+/', '_', trim($number));
        $name = preg_replace('/\s+/', '_', $name ?: 'document');

        return $extension === '' ? $name : $name . '.' . ltrim($extension, '.');
    }

    private function centerCode(int $distributionCenterId): string
    {
        $centerCode = DistributionCenter::query()
            ->whereKey($distributionCenterId)
            ->value('code');

        if (!$centerCode) {
            throw new RuntimeException('Khong tim thay ma trung tam phan phoi.');
        }

        return strtoupper(trim($centerCode));
    }
}
