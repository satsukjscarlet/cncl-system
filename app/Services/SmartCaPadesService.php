<?php

namespace App\Services;

use App\Models\QualityCertificate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class SmartCaPadesService
{
    public function prepare(QualityCertificate $certificate, string $pdfContent, string $certBase64, ?array $chainData = null): array
    {
        $baseDir = 'quality-certificates/smartca/' . $certificate->id . '/pades-' . Str::uuid();
        $inputPath = $baseDir . '/unsigned.pdf';
        $preparedPath = $baseDir . '/prepared.pdf';
        $statePath = $baseDir . '/state.json';

        Storage::disk('local')->put($inputPath, $pdfContent);

        $result = $this->run([
            'prepare',
            '--input', Storage::disk('local')->path($inputPath),
            '--prepared', Storage::disk('local')->path($preparedPath),
            '--state', Storage::disk('local')->path($statePath),
            '--cert-base64', $certBase64,
            '--chain-json', json_encode($chainData ?? [], JSON_UNESCAPED_SLASHES),
            '--field-name', 'CNCLSignature',
            '--signer-name', $certificate->signed_by ?: 'VNPT SmartCA',
            '--reason', 'Ky so phieu CNCL ' . $certificate->certificate_no,
        ]);

        return [
            'input_pdf_path' => $inputPath,
            'prepared_pdf_path' => $preparedPath,
            'state_path' => $statePath,
            'second_hash_hex' => $result['second_hash_hex'],
            'second_hash_base64' => $result['second_hash_base64'],
            'document_digest' => $result['document_digest'],
        ];
    }

    public function finalize(QualityCertificate $certificate, string $signatureValue): string
    {
        if (!$certificate->pades_state_path || !$certificate->smartca_certificate_data) {
            throw new RuntimeException('Thieu du lieu PAdES presign de nhung chu ky vao PDF.');
        }

        $signedPath = 'quality-certificates/smartca/' . $certificate->id . '/' . $certificate->smartca_transaction_id . '_pades_signed.pdf';

        $this->run([
            'finalize',
            '--state', Storage::disk('local')->path($certificate->pades_state_path),
            '--output', Storage::disk('local')->path($signedPath),
            '--cert-base64', $certificate->smartca_certificate_data,
            '--chain-json', json_encode($certificate->smartca_chain_data ?? [], JSON_UNESCAPED_SLASHES),
            '--signature-value', $signatureValue,
        ]);

        return $signedPath;
    }

    private function run(array $arguments): array
    {
        $command = array_merge([
            config('services.smartca.python_bin', 'python'),
            base_path('scripts/smartca_pades.py'),
        ], $arguments);

        $process = new Process($command, base_path(), $this->pythonEnvironment(), null, 120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()) ?: 'Loi chay SmartCA PAdES helper.');
        }

        $json = json_decode(trim($process->getOutput()), true);

        if (!is_array($json)) {
            throw new RuntimeException('SmartCA PAdES helper tra ve du lieu khong hop le: ' . $process->getOutput());
        }

        return $json;
    }

    private function pythonEnvironment(): array
    {
        $systemRoot = getenv('SystemRoot') ?: getenv('WINDIR') ?: 'C:\\Windows';
        $path = getenv('PATH') ?: getenv('Path') ?: '';

        $pythonDir = dirname((string) config('services.smartca.python_bin', 'python'));
        if ($pythonDir !== '.' && $pythonDir !== '') {
            $path = $pythonDir . PATH_SEPARATOR . $path;
        }

        return [
            'SystemRoot' => $systemRoot,
            'WINDIR' => $systemRoot,
            'PATH' => $path,
            'Path' => $path,
        ];
    }
}
