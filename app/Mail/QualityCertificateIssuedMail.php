<?php

namespace App\Mail;

use App\Models\QualityCertificate;
use App\Services\SignedCertificatePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class QualityCertificateIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public QualityCertificate $certificate
    ) {
    }

    public function build()
    {
        $this->certificate->load([
            'request.distributionCenter',
            'request.customer',
            'details.product',
            'creator',
        ]);

        $mail = $this->subject('Phiếu Chứng nhận Chất lượng - ' . $this->certificate->certificate_no)
            ->view('emails.quality_certificate_issued');

        if (
            $this->certificate->signed_at
            && $this->certificate->pdf_path
            && Storage::disk('local')->exists($this->certificate->pdf_path)
        ) {
            return $mail->attachFromStorageDisk(
                'local',
                $this->certificate->pdf_path,
                $this->certificate->certificate_no . '.pdf',
                ['mime' => 'application/pdf']
            );
        }

        $pdfContent = app(SignedCertificatePdfService::class)->render($this->certificate);

        return $mail->attachData(
            $pdfContent,
            $this->certificate->certificate_no . '.pdf',
            [
                'mime' => 'application/pdf',
            ]
        );
    }

}
