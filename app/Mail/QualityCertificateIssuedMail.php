<?php

namespace App\Mail;

use App\Models\QualityCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $pdf = Pdf::loadView('quality_certificates.pdf', [
            'certificate' => $this->certificate,
            'hardCopy' => false,
        ])->setPaper('a4', 'portrait');

        return $mail->attachData(
            $pdf->output(),
            $this->certificate->certificate_no . '.pdf',
            [
                'mime' => 'application/pdf',
            ]
        );
    }

}
