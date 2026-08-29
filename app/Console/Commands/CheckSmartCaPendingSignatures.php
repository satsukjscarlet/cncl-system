<?php

namespace App\Console\Commands;

use App\Http\Controllers\QualityCertificateController;
use App\Models\QualityCertificate;
use App\Services\SmartCaPadesService;
use App\Services\SmartCaService;
use Illuminate\Console\Command;

class CheckSmartCaPendingSignatures extends Command
{
    protected $signature = 'smartca:check-pending-signatures {--limit=30 : So phieu toi da moi lan chay}';

    protected $description = 'Tu dong kiem tra ket qua ky VNPT SmartCA cho cac phieu dang cho xac nhan.';

    public function handle(SmartCaService $smartCaService, SmartCaPadesService $padesService): int
    {
        $limit = min(max((int) $this->option('limit'), 1), 50);

        $certificates = QualityCertificate::with([
            'request.distributionCenter',
            'request.customer',
            'request.creator.roles',
            'details.product',
        ])
            ->whereNull('signed_at')
            ->whereIn('smartca_status', ['PENDING', 'EXPIRED'])
            ->whereNotNull('smartca_transaction_id')
            ->oldest('smartca_requested_at')
            ->limit($limit)
            ->get();

        $summary = [
            'checked' => 0,
            'signed' => 0,
            'pending' => 0,
            'expired' => 0,
            'error' => 0,
        ];

        $controller = app(QualityCertificateController::class);

        foreach ($certificates as $certificate) {
            $summary['checked']++;

            $result = $controller->processSmartCaStatus($certificate, $smartCaService, $padesService);

            match ($result['status'] ?? null) {
                'SIGNED_EMAIL_SENT', 'SIGNED_NO_EMAIL', 'SIGNED_EMAIL_MISSING', 'SIGNED_EMAIL_FAILED' => $summary['signed']++,
                'PENDING' => $summary['pending']++,
                'EXPIRED' => $summary['expired']++,
                default => $summary['error']++,
            };
        }

        $this->info('Checked: ' . $summary['checked']
            . ', signed: ' . $summary['signed']
            . ', pending: ' . $summary['pending']
            . ', expired: ' . $summary['expired']
            . ', error: ' . $summary['error']);

        return self::SUCCESS;
    }
}
