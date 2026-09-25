<?php

namespace App\Mail;

use App\Models\QualityCertificate;
use App\Services\SignedCertificatePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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

        $storedPdf = app(\App\Services\StoredSignedCertificatePdf::class);
        if ($storedPdf->isSigned($this->certificate)) {
            return $mail->attachData(
                $storedPdf->read($this->certificate),
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
