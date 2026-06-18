<?php

namespace App\Mail;

use App\Models\QualityCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $pdf = Pdf::loadView('quality_certificates.pdf', [
            'certificate' => $this->certificate,
            'hardCopy' => false,
        ])->setPaper('a4', 'portrait');

        return $this->subject('Phiếu Chứng nhận Chất lượng - ' . $this->certificate->certificate_no)
            ->view('emails.quality_certificate_issued')
            ->attachData(
                $pdf->output(),
                $this->certificate->certificate_no . '.pdf',
                [
                    'mime' => 'application/pdf',
                ]
            );
    }
}