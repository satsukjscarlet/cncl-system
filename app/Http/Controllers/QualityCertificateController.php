<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Mail\QualityCertificateIssuedMail;
use App\Models\PrintLog;
use App\Models\QualityCertificate;
use App\Models\SystemSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class QualityCertificateController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = QualityCertificate::with([
            'request.distributionCenter',
            'request.customer',
            'creator',
        ]);

        if ($user->hasRole('TrungTam')) {
            $query->whereHas('request', function ($q) use ($user) {
                $q->where('distribution_center_id', $user->distribution_center_id);
            });
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('certificate_no', 'like', '%' . $request->keyword . '%')
                    ->orWhereHas('request', function ($r) use ($request) {
                        $r->where('request_no', 'like', '%' . $request->keyword . '%')
                            ->orWhere('invoice_no', 'like', '%' . $request->keyword . '%');
                    })
                    ->orWhereHas('request.customer', function ($c) use ($request) {
                        $c->where('customer_name', 'like', '%' . $request->keyword . '%')
                            ->orWhere('project_name', 'like', '%' . $request->keyword . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'SIGNED') {
                $query->whereNotNull('signed_at');
            }

            if ($request->status === 'UNSIGNED') {
                $query->whereNull('signed_at');
            }
        }

        $certificates = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quality_certificates.index', compact('certificates'));
    }

    public function show(QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        $qualityCertificate->load([
            'request.distributionCenter',
            'request.customer',
            'request.creator',
            'details.product.group',
            'creator',
            'printLogs.user',
        ]);

        return view('quality_certificates.show', compact('qualityCertificate'));
    }

    public function sign(QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        if ($qualityCertificate->signed_at) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này đã được ký số/phát hành.');
        }

        $qualityCertificate->load([
            'request.customer',
            'request.distributionCenter',
            'details.product',
        ]);

        $autoSendEmail = SystemSetting::getValue('auto_send_email_after_sign', true);

        if ($autoSendEmail) {
            $customerEmail = $qualityCertificate->request->customer->email ?? null;

            if (!$customerEmail) {
                return redirect()
                    ->route('quality-certificates.show', $qualityCertificate)
                    ->with('error', 'Khách hàng chưa có email nhận phiếu. Vui lòng cập nhật email hoặc tắt cấu hình tự động gửi email trước khi phát hành.');
            }
        }

        $oldData = $qualityCertificate->toArray();

        $qualityCertificate->update([
            'signed_at' => now(),
            'signed_by' => Auth::user()->name,
            'pdf_path' => null,
        ]);

        if ($qualityCertificate->request) {
            $qualityCertificate->request->update([
                'status' => 'COMPLETED',
            ]);
        }

        if (!$autoSendEmail) {
            ActivityLogger::log(
                'Phiếu CNCL',
                'sign_without_email',
                'Ký số/phát hành phiếu CNCL không gửi email tự động: ' . $qualityCertificate->certificate_no,
                $oldData,
                $qualityCertificate->fresh()->load('request')->toArray()
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', 'Đã ký số/phát hành phiếu CNCL. Hệ thống đang tắt tự động gửi email.');
        }

        try {
            $customerEmail = $qualityCertificate->request->customer->email;

            Mail::to($customerEmail)
                ->send(new QualityCertificateIssuedMail($qualityCertificate->fresh()));

            ActivityLogger::log(
                'Phiếu CNCL',
                'sign_and_send_email',
                'Ký số/phát hành và gửi email phiếu CNCL: ' . $qualityCertificate->certificate_no . ' tới ' . $customerEmail,
                $oldData,
                $qualityCertificate->fresh()->load('request')->toArray()
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', 'Đã ký số/phát hành và gửi email phiếu CNCL thành công.');
        } catch (\Throwable $e) {
            ActivityLogger::log(
                'Phiếu CNCL',
                'send_email_failed',
                'Lỗi gửi email phiếu CNCL: ' . $qualityCertificate->certificate_no . '. Lỗi: ' . $e->getMessage()
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu đã được ký/phát hành nhưng gửi email thất bại: ' . $e->getMessage());
        }
    }

    public function pdf(QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        $qualityCertificate->load([
            'request.distributionCenter',
            'request.customer',
            'details.product',
            'creator',
        ]);

        ActivityLogger::log(
            'Phiếu CNCL',
            'export_pdf',
            'Xuất PDF phiếu CNCL: ' . $qualityCertificate->certificate_no
        );

        $pdf = Pdf::loadView('quality_certificates.pdf', [
            'certificate' => $qualityCertificate,
            'hardCopy' => false,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($qualityCertificate->certificate_no . '.pdf');
    }

    public function printHardCopy(Request $request, QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        if (!$qualityCertificate->signed_at) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Chỉ được in ký tươi khi phiếu đã ký số/phát hành.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $qualityCertificate->load([
            'request.distributionCenter',
            'request.customer',
            'details.product',
            'creator',
        ]);

        $oldData = $qualityCertificate->toArray();

        $printNo = $qualityCertificate->print_count + 1;

        PrintLog::create([
            'quality_certificate_id' => $qualityCertificate->id,
            'user_id' => Auth::id(),
            'reason' => $data['reason'],
            'print_no' => $printNo,
        ]);

        $qualityCertificate->update([
            'print_count' => $printNo,
        ]);

        ActivityLogger::log(
            'Phiếu CNCL',
            'print_hard_copy',
            'In phiếu ký tươi: ' . $qualityCertificate->certificate_no . '. Lý do: ' . $data['reason'],
            $oldData,
            $qualityCertificate->fresh()->toArray()
        );

        $pdf = Pdf::loadView('quality_certificates.pdf', [
            'certificate' => $qualityCertificate->fresh()->load([
                'request.distributionCenter',
                'request.customer',
                'details.product',
                'creator',
            ]),
            'hardCopy' => true,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($qualityCertificate->certificate_no . '_ky_tuoi_lan_' . $printNo . '.pdf');
    }

    public function resendEmail(QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        if (!$qualityCertificate->signed_at) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Chỉ gửi lại email khi phiếu đã được ký/phát hành.');
        }

        $qualityCertificate->load([
            'request.customer',
            'details.product',
        ]);

        $customerEmail = $qualityCertificate->request->customer->email ?? null;

        if (!$customerEmail) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Khách hàng chưa có email nhận phiếu.');
        }

        try {
            Mail::to($customerEmail)
                ->send(new QualityCertificateIssuedMail($qualityCertificate));

            ActivityLogger::log(
                'Phiếu CNCL',
                'resend_email',
                'Gửi lại email phiếu CNCL: ' . $qualityCertificate->certificate_no . ' tới ' . $customerEmail
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', 'Đã gửi lại email phiếu CNCL thành công.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Gửi lại email thất bại: ' . $e->getMessage());
        }
    }

    private function authorizeCenter(QualityCertificate $qualityCertificate): void
    {
        $qualityCertificate->loadMissing('request');

        if (
            Auth::user()->hasRole('TrungTam')
            && $qualityCertificate->request
            && $qualityCertificate->request->distribution_center_id != Auth::user()->distribution_center_id
        ) {
            abort(403, 'Anh không có quyền xem phiếu của trung tâm khác.');
        }
    }
}