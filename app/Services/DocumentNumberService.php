<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\DocumentSequence;
use App\Models\DistributionCenter;
use App\Models\QualityCertificate;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DocumentNumberService
{
    public function generateRequestNo(int $distributionCenterId, ?Carbon $date = null): string
    {
        $centerCode = $this->centerCode($distributionCenterId);
        $date ??= now();

        $year = (int) $date->format('Y');
        $prefix = 'YC-' . $date->format('Ymd') . '-';
        $suffix = '/' . $centerCode;

        return DB::transaction(function () use ($distributionCenterId, $centerCode, $year, $prefix, $suffix): string {
            $sequence = DocumentSequence::query()
                ->where('document_type', 'request')
                ->where('year', $year)
                ->where('distribution_center_id', $distributionCenterId)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                try {
                    DocumentSequence::create([
                        'document_type' => 'request',
                        'year' => $year,
                        'distribution_center_id' => $distributionCenterId,
                        'last_number' => $this->initialRequestLastNumber($distributionCenterId, $centerCode, $year),
                    ]);
                } catch (QueryException) {
                    // Neu co request khac vua tao dong dem cung luc, khoa va dung lai dong do.
                }

                $sequence = DocumentSequence::query()
                    ->where('document_type', 'request')
                    ->where('year', $year)
                    ->where('distribution_center_id', $distributionCenterId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            do {
                $sequence->last_number++;
                $requestNo = $prefix . str_pad((string) $sequence->last_number, 6, '0', STR_PAD_LEFT) . $suffix;
            } while (CertificateRequest::withTrashed()->where('request_no', $requestNo)->exists());

            $sequence->save();

            return $requestNo;
        });
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

    private function initialRequestLastNumber(int $distributionCenterId, string $centerCode, int $year): int
    {
        $prefix = 'YC-' . $year;
        $suffix = '/' . $centerCode;
        $pattern = '/^YC-' . $year . '\d{4}-(\d{4,6})' . preg_quote($suffix, '/') . '$/i';

        $numbers = CertificateRequest::withTrashed()
            ->where('distribution_center_id', $distributionCenterId)
            ->where('request_no', 'like', $prefix . '%' . $suffix)
            ->pluck('request_no');

        $maxSequence = $numbers
            ->map(fn (string $requestNo) => preg_match($pattern, $requestNo, $matches) ? (int) $matches[1] : 0)
            ->max() ?? 0;

        return max($numbers->count(), $maxSequence);
    }
}
