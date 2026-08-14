<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\QualityCertificate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class SmartCaService
{
    public function getCertificate(string $userId, ?string $serialNumber = null): array
    {
        $this->ensureConfigured();

        $transactionId = 'CERT-' . Str::upper((string) Str::uuid());

        $payload = array_filter([
            'sp_id' => config('services.smartca.sp_id'),
            'sp_password' => config('services.smartca.sp_password'),
            'user_id' => $userId,
            'serial_number' => $serialNumber,
            'transaction_id' => $transactionId,
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->post('/v1/credentials/get_certificate', $payload);
        $certificates = data_get($response, 'data.user_certificates', []);

        if (!is_array($certificates) || count($certificates) === 0) {
            throw new RuntimeException('VNPT SmartCA khong tra ve chung thu so phu hop.');
        }

        $certificate = $serialNumber
            ? collect($certificates)->firstWhere('serial_number', $serialNumber)
            : $certificates[0];

        if (!$certificate) {
            throw new RuntimeException('Khong tim thay chung thu so SmartCA theo serial da cau hinh.');
        }

        return [
            'certificate' => $certificate,
            'response' => $response,
            'request' => $this->maskPayload($payload),
            'endpoint' => $this->url('/v1/credentials/get_certificate'),
            'transaction_id' => $transactionId,
        ];
    }

    public function initiateSignature(QualityCertificate $certificate, string $pdfContent, string $userId, ?string $serialNumber = null): array
    {
        return $this->initiateHashSignature(
            $certificate,
            hash('sha256', $pdfContent),
            $userId,
            $serialNumber
        );
    }

    public function initiateHashSignature(QualityCertificate $certificate, string $dataHash, string $userId, ?string $serialNumber = null): array
    {
        $this->ensureConfigured();

        $transactionId = $this->makeTransactionId($certificate);
        $docId = $this->makeDocId($certificate);
        $signType = 'hash';

        // The current VNPT SmartCA demo credential only supports hash signing.
        $dataToBeSigned = $dataHash;

        $payload = array_filter([
            'sp_id' => config('services.smartca.sp_id'),
            'sp_password' => config('services.smartca.sp_password'),
            'user_id' => $userId,
            'transaction_id' => $transactionId,
            'transaction_desc' => 'Ky so phieu CNCL ' . $certificate->certificate_no,
            'serial_number' => $serialNumber,
            'time_stamp' => now('UTC')->format('YmdHis') . 'Z',
            'sign_files' => [
                [
                    'file_type' => 'pdf',
                    'data_to_be_signed' => $dataToBeSigned,
                    'doc_id' => $docId,
                    'sign_type' => $signType,
                ],
            ],
        ], fn ($value) => $value !== null && $value !== '');

        $response = $this->post('/v1/signatures/sign', $payload);

        return [
            'transaction_id' => $transactionId,
            'tran_code' => data_get($response, 'data.tran_code'),
            'doc_id' => $docId,
            'data_hash' => $dataHash,
            'sign_type' => $signType,
            'message' => data_get($response, 'message'),
            'response' => $response,
            'request' => $this->maskPayload($payload),
            'endpoint' => $this->url('/v1/signatures/sign'),
        ];
    }

    public function calculatePdfHash(QualityCertificate $certificate, string $pdfContent, string $signerCertificate): array
    {
        $this->ensureConfigured();

        $transactionId = 'HASH-' . $certificate->id . '-' . Str::upper((string) Str::uuid());
        $fileName = $certificate->certificate_no . '.pdf';
        $pageCount = $this->countPdfPages($pdfContent);
        $signatureOptions = $this->signatureOptions($certificate, $pageCount);

        $payload = [
            'transaction_id' => $transactionId,
            'sp_id' => config('services.smartca.sp_id'),
            'sp_password' => config('services.smartca.sp_password'),
            'signerCert' => str_replace(["\r", "\n"], '', $signerCertificate),
            'digestAlgorithm' => 'sha256',
            'sign_files' => [
                [
                    'storage_file_name' => '',
                    'name' => $fileName,
                    'pdfContent' => base64_encode($pdfContent),
                    'sigOptions' => $signatureOptions,
                ],
            ],
        ];

        $response = $this->postSignatureService('/calculateHash', $payload);
        $hashResponse = data_get($response, 'hashResps.0');

        if (data_get($hashResponse, 'code') !== 'sigSuccess') {
            throw new RuntimeException('VNPT calculateHash that bai: ' . (data_get($hashResponse, 'message') ?: data_get($response, 'message') ?: 'Khong ro loi'));
        }

        $hashBase64 = (string) data_get($hashResponse, 'hash');
        $hashBytes = base64_decode($hashBase64, true);

        if ($hashBytes === false || $hashBytes === '') {
            throw new RuntimeException('VNPT calculateHash khong tra ve hash hop le.');
        }

        return [
            'transaction_id' => (string) data_get($response, 'tranId', $transactionId),
            'file_id' => (string) data_get($hashResponse, 'fileID'),
            'hash_base64' => $hashBase64,
            'hash_hex' => bin2hex($hashBytes),
            'page_count' => $pageCount,
            'signature_options' => $signatureOptions,
            'response' => $response,
            'request' => $this->maskPayload($payload),
            'endpoint' => $this->signatureUrl('/calculateHash'),
        ];
    }

    public function externalizePdfSignature(string $hashTransactionId, string $fileId, string $signatureValue): array
    {
        $this->ensureConfigured();

        $payload = [
            'tranId' => $hashTransactionId,
            'sp_id' => config('services.smartca.sp_id'),
            'sp_password' => config('services.smartca.sp_password'),
            'signatures' => [
                [
                    'fileID' => $fileId,
                    'signature' => $signatureValue,
                ],
            ],
        ];

        $response = $this->postSignatureService('/signExternal', $payload);
        $signedData = (string) data_get($response, 'signResps.0.signedData');

        if (blank($signedData)) {
            throw new RuntimeException('VNPT signExternal khong tra ve signedData.');
        }

        $signedPdf = base64_decode($signedData, true);

        if ($signedPdf === false || !str_starts_with($signedPdf, '%PDF')) {
            throw new RuntimeException('VNPT signExternal tra ve signedData nhung khong phai PDF base64 hop le.');
        }

        return [
            'signed_pdf' => $signedPdf,
            'response' => $this->maskPayload($response),
            'request' => $this->maskPayload($payload),
            'endpoint' => $this->signatureUrl('/signExternal'),
        ];
    }

    public function signatureStatus(string $transactionId): array
    {
        $this->ensureConfigured();

        $path = '/v1/signatures/sign/' . rawurlencode($transactionId) . '/status';
        $payload = [];

        return [
            'endpoint' => $this->url($path),
            'request' => $this->maskPayload($payload),
            'response' => $this->post($path, $payload),
        ];
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::timeout((int) config('services.smartca.timeout', 30))
                ->acceptJson()
                ->asJson()
                ->post($this->url($path), $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Khong ket noi duoc VNPT SmartCA: ' . $exception->getMessage(), 0, $exception);
        }

        if (!$response->successful()) {
            throw new RuntimeException('VNPT SmartCA tra ve HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException('VNPT SmartCA tra ve du lieu khong phai JSON hop le.');
        }

        if ((int) data_get($json, 'status_code') !== 200) {
            throw new RuntimeException('VNPT SmartCA bao loi: ' . (data_get($json, 'message') ?: 'Khong ro loi'));
        }

        return $json;
    }

    private function postSignatureService(string $path, array $payload): array
    {
        try {
            $response = Http::timeout((int) config('services.smartca.timeout', 30))
                ->acceptJson()
                ->asJson()
                ->post($this->signatureUrl($path), $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Khong ket noi duoc VNPT Signature Service: ' . $exception->getMessage(), 0, $exception);
        }

        if (!$response->successful()) {
            throw new RuntimeException('VNPT Signature Service tra ve HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();

        if (!is_array($json)) {
            throw new RuntimeException('VNPT Signature Service tra ve du lieu khong phai JSON hop le.');
        }

        return $json;
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.smartca.base_url'), '/') . $path;
    }

    private function signatureUrl(string $path): string
    {
        return rtrim($this->signatureBaseUrl(), '/') . $path;
    }

    private function signatureBaseUrl(): string
    {
        $configured = config('services.smartca.signature_base_url');

        if (filled($configured)) {
            return (string) $configured;
        }

        $baseUrl = rtrim((string) config('services.smartca.base_url'), '/');

        return preg_replace('#/sca/sp\d+$#', '/rest/v2/signature', $baseUrl) ?: $baseUrl . '/rest/v2/signature';
    }

    private function signatureOptions(QualityCertificate $certificate, ?int $pageCount = null): array
    {
        $renderMode = (int) SystemSetting::getValue('smartca_signature_render_mode', 0);
        $imagePath = SystemSetting::getValue('smartca_signature_image_path');
        $hasImage = $imagePath && Storage::disk('public')->exists($imagePath);
        $pageMode = (string) SystemSetting::getValue('smartca_signature_page_mode', 'last');
        $page = $pageMode === 'fixed'
            ? (int) SystemSetting::getValue('smartca_signature_page', 1)
            : max(1, (int) ($pageCount ?: 1));

        if ($renderMode > 0 && !$hasImage) {
            $renderMode = 0;
        }

        $options = [
            'renderMode' => $renderMode,
            'fontSize' => (int) SystemSetting::getValue('smartca_signature_font_size', 11),
            'fontColor' => (string) SystemSetting::getValue('smartca_signature_font_color', '#000000'),
            'signatureText' => $this->signatureText($certificate),
            'signatures' => [
                [
                    'page' => $page,
                    'rectangle' => (string) SystemSetting::getValue('smartca_signature_rectangle', '315,150,565,220'),
                ],
            ],
        ];

        if ($renderMode > 0 && $hasImage) {
            $options['customImage'] = base64_encode(Storage::disk('public')->get($imagePath));
        }

        return $options;
    }

    private function countPdfPages(string $pdfContent): int
    {
        if (preg_match_all('/\/Type\s*\/Page\b/', $pdfContent, $matches)) {
            return max(1, count($matches[0]));
        }

        return 1;
    }

    private function signatureText(QualityCertificate $certificate): string
    {
        $template = (string) SystemSetting::getValue(
            'smartca_signature_text',
            "PHIEU DUOC KY DIEN TU\nPhieu CNCL: {certificate_no}\nNguoi ky: {signed_by}\nThoi gian ky: {signed_at}"
        );

        return strtr($template, [
            '{certificate_no}' => (string) $certificate->certificate_no,
            '{signed_by}' => (string) ($certificate->signed_by ?: 'CNCL NTP'),
            '{signed_at}' => now()->format('d/m/Y H:i:s'),
        ]);
    }

    private function maskPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if ($key === 'sp_password') {
                $payload[$key] = '***';
                continue;
            }

            if (is_array($value)) {
                $payload[$key] = $this->maskPayload($value);
                continue;
            }

            if (in_array($key, ['pdfContent', 'customImage', 'signedData', 'signature'], true) && is_string($value)) {
                $payload[$key] = '[' . $key . ':' . strlen($value) . '_chars]';
            }
        }

        return $payload;
    }

    private function ensureConfigured(): void
    {
        $envNames = [
            'base_url' => 'SMARTCA_BASE_URL',
            'sp_id' => 'SMARTCA_CLIENT_ID',
            'sp_password' => 'SMARTCA_CLIENT_SECRET',
        ];

        foreach ($envNames as $key => $envName) {
            if (!filled(config('services.smartca.' . $key))) {
                throw new RuntimeException('Thieu cau hinh ' . $envName . ' trong file .env.');
            }
        }
    }

    private function makeTransactionId(QualityCertificate $certificate): string
    {
        return 'CNCL-' . $certificate->id . '-' . Str::upper((string) Str::uuid());
    }

    private function makeDocId(QualityCertificate $certificate): string
    {
        return 'CNCL-' . $certificate->id . '-' . Str::slug($certificate->certificate_no);
    }
}

