<?php

namespace App\Http\Controllers;

use App\Helpers\ActivityLogger;
use App\Mail\QualityCertificateIssuedMail;
use App\Models\CertificateRequest;
use App\Models\PrintLog;
use App\Models\QualityCertificate;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\SmartCaPadesService;
use App\Services\SmartCaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class QualityCertificateController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = QualityCertificate::with([
            'request.distributionCenter',
            'request.customer',
            'creator',
            'replacesCertificate',
            'replacedByCertificate',
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
                $query->whereNotNull('signed_at')
                    ->where('status', 'ISSUED');
            }

            if ($request->status === 'UNSIGNED') {
                $query->whereNull('signed_at')
                    ->whereNotIn('status', ['REJECTED', 'REVOKED']);
            }

            if ($request->status === 'SIGN_READY') {
                $query->whereNull('signed_at')
                    ->where('status', 'DRAFT')
                    ->where(function ($q) {
                        $q->whereNull('smartca_status')
                            ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                    });
            }

            if ($request->status === 'SMARTCA_PENDING') {
                $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

                $query->whereNull('signed_at')
                    ->where('smartca_status', 'PENDING')
                    ->where('smartca_requested_at', '>', $expiredBefore);
            }

            if ($request->status === 'REVOKED') {
                $query->where('status', 'REVOKED');
            }

            if ($request->status === 'REJECTED') {
                $query->where('status', 'REJECTED');
            }

            if ($request->status === 'SMARTCA_EXPIRED') {
                $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

                $query->whereNull('signed_at')
                    ->where(function ($q) use ($expiredBefore) {
                        $q->where('smartca_status', 'EXPIRED')
                            ->orWhere(function ($pending) use ($expiredBefore) {
                                $pending->where('smartca_status', 'PENDING')
                                    ->where('smartca_requested_at', '<=', $expiredBefore);
                            });
                    });
            }
        }

        $certificates = $query
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quality_certificates.index', compact('certificates'));
    }

    public function signingQueue(Request $request)
    {
        $expiredBefore = now()->subMinutes($this->smartCaPendingTtlMinutes());

        $baseQuery = QualityCertificate::with([
            'request.distributionCenter',
            'request.customer',
            'request.urgentReason',
            'creator',
        ]);

        $metrics = [
            'ready' => (clone $baseQuery)
                ->whereNull('signed_at')
                ->where('status', 'DRAFT')
                ->where(function ($q) {
                    $q->whereNull('smartca_status')
                        ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                })
                ->count(),
            'pending' => (clone $baseQuery)
                ->whereNull('signed_at')
                ->where('smartca_status', 'PENDING')
                ->where('smartca_requested_at', '>', $expiredBefore)
                ->count(),
            'expired' => (clone $baseQuery)
                ->whereNull('signed_at')
                ->where(function ($q) use ($expiredBefore) {
                    $q->where('smartca_status', 'EXPIRED')
                        ->orWhere(function ($pending) use ($expiredBefore) {
                            $pending->where('smartca_status', 'PENDING')
                                ->where('smartca_requested_at', '<=', $expiredBefore);
                        });
                })
                ->count(),
            'rejected' => (clone $baseQuery)
                ->where('status', 'REJECTED')
                ->count(),
            'signed_today' => (clone $baseQuery)
                ->where('status', 'ISSUED')
                ->whereDate('signed_at', now()->toDateString())
                ->count(),
            'urgent' => (clone $baseQuery)
                ->whereNull('signed_at')
                ->whereHas('request', fn ($q) => $q->where('is_urgent', true))
                ->count(),
        ];

        $query = $baseQuery;

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

        match ($request->get('status', 'ACTIONABLE')) {
            'READY' => $query->whereNull('signed_at')
                ->where('status', 'DRAFT')
                ->where(function ($q) {
                    $q->whereNull('smartca_status')
                        ->orWhereNotIn('smartca_status', ['PENDING', 'SIGNED', 'EXPIRED']);
                }),
            'PENDING' => $query->whereNull('signed_at')
                ->where('smartca_status', 'PENDING')
                ->where('smartca_requested_at', '>', $expiredBefore),
            'EXPIRED' => $query->whereNull('signed_at')
                ->where(function ($q) use ($expiredBefore) {
                    $q->where('smartca_status', 'EXPIRED')
                        ->orWhere(function ($pending) use ($expiredBefore) {
                            $pending->where('smartca_status', 'PENDING')
                                ->where('smartca_requested_at', '<=', $expiredBefore);
                        });
                }),
            'REJECTED' => $query->where('status', 'REJECTED'),
            'SIGNED_TODAY' => $query->where('status', 'ISSUED')
                ->whereDate('signed_at', now()->toDateString()),
            'URGENT' => $query->whereNull('signed_at')
                ->whereHas('request', fn ($q) => $q->where('is_urgent', true)),
            default => $query->whereNull('signed_at')
                ->whereNotIn('status', ['ISSUED', 'REVOKED'])
                ->where(function ($q) use ($expiredBefore) {
                    $q->where('status', 'DRAFT')
                        ->orWhere('smartca_status', 'PENDING')
                        ->orWhere('smartca_status', 'EXPIRED')
                        ->orWhere(function ($pending) use ($expiredBefore) {
                            $pending->where('smartca_status', 'PENDING')
                                ->where('smartca_requested_at', '<=', $expiredBefore);
                        });
                }),
        };

        $certificates = $query
            ->orderByRaw(
                "CASE
                    WHEN smartca_status = 'EXPIRED' THEN 0
                    WHEN smartca_status = 'PENDING' AND smartca_requested_at <= ? THEN 0
                    WHEN smartca_status = 'PENDING' THEN 1
                    WHEN status = 'DRAFT' THEN 2
                    WHEN status = 'REJECTED' THEN 3
                    ELSE 4
                END",
                [$expiredBefore->toDateTimeString()]
            )
            ->oldest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('quality_certificates.signing_queue', compact('certificates', 'metrics'));
    }

    public function show(QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        $qualityCertificate->load([
            'request.distributionCenter',
            'request.customer',
            'request.creator',
            'request.reissueOfCertificate',
            'request.reissueCertificates',
            'details.product.group',
            'creator',
            'revokedBy',
            'rejectedBy',
            'replacesCertificate',
            'replacedByCertificate',
            'replacementSourceCertificates',
            'printLogs.user',
        ]);

        $historyKeywords = collect([
            $qualityCertificate->certificate_no,
            $qualityCertificate->request?->request_no,
            $qualityCertificate->replacesCertificate?->certificate_no,
            $qualityCertificate->replacedByCertificate?->certificate_no,
            $qualityCertificate->request?->reissueOfCertificate?->certificate_no,
            ...$qualityCertificate->request?->reissueCertificates?->pluck('certificate_no')->all() ?? [],
            ...$qualityCertificate->replacementSourceCertificates?->pluck('certificate_no')->all() ?? [],
        ])->filter()->unique()->values();

        $historyCertificateIds = collect([
            $qualityCertificate->id,
            $qualityCertificate->replaces_certificate_id,
            $qualityCertificate->replaced_by_certificate_id,
            $qualityCertificate->request?->reissue_of_certificate_id,
            ...$qualityCertificate->request?->reissueCertificates?->pluck('id')->all() ?? [],
            ...$qualityCertificate->replacementSourceCertificates?->pluck('id')->all() ?? [],
        ])->filter()->unique()->values();

        $historyRequestIds = collect([
            $qualityCertificate->certificate_request_id,
            $qualityCertificate->request?->id,
        ])->filter()->unique()->values();

        $certificateHistoryLogs = Activity::query()
            ->with('causer')
            ->where(function ($query) use ($historyCertificateIds, $historyRequestIds, $historyKeywords) {
                if ($historyCertificateIds->isNotEmpty()) {
                    $query->orWhere(function ($subjectQuery) use ($historyCertificateIds) {
                        $subjectQuery
                            ->where('subject_type', QualityCertificate::class)
                            ->whereIn('subject_id', $historyCertificateIds);
                    });
                }

                if ($historyRequestIds->isNotEmpty()) {
                    $query->orWhere(function ($subjectQuery) use ($historyRequestIds) {
                        $subjectQuery
                            ->where('subject_type', CertificateRequest::class)
                            ->whereIn('subject_id', $historyRequestIds);
                    });
                }

                if ($historyKeywords->isNotEmpty()) {
                    $query->orWhere(function ($logQuery) use ($historyKeywords) {
                        foreach ($historyKeywords as $keyword) {
                            $logQuery->orWhere('description', 'like', '%' . $keyword . '%');
                        }
                    });
                }
            })
            ->latest()
            ->limit(30)
            ->get();

        $certificateWorkflowSteps = $this->certificateWorkflowSteps($qualityCertificate, $certificateHistoryLogs);

        return view('quality_certificates.show', compact('qualityCertificate', 'certificateHistoryLogs', 'certificateWorkflowSteps'));
    }

    public function sign(
        Request $request,
        QualityCertificate $qualityCertificate,
        SmartCaService $smartCaService,
        SmartCaPadesService $padesService
    )
    {
        $this->authorizeCenter($qualityCertificate);

        if ($qualityCertificate->signed_at) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này đã được ký số/phát hành.');
        }

        if ($qualityCertificate->status === 'REJECTED') {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này đã bị Trưởng PTN trả lại, không thể gửi ký số.');
        }

        if ($qualityCertificate->smartca_status === 'PENDING' && !$this->smartCaPendingExpired($qualityCertificate)) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này đang chờ người ký xác nhận trên VNPT SmartCA. Vui lòng bấm kiểm tra kết quả ký.');
        }

        $smartCaUserId = data_get(Auth::user(), config('services.smartca.user_id_field', 'smartca_user_id'))
            ?: config('services.smartca.default_user_id');

        if (blank($smartCaUserId)) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Tài khoản đăng nhập chưa có SmartCA User ID. Vui lòng cập nhật người dùng hoặc cấu hình SMARTCA_DEFAULT_USER_ID trong .env.');
        }

        $qualityCertificate->load([
            'request.distributionCenter',
            'request.customer',
            'details.product',
            'creator',
        ]);

        $oldData = $qualityCertificate->toArray();
        $isResendAfterExpired = $qualityCertificate->smartca_status === 'PENDING'
            || $qualityCertificate->smartca_status === 'EXPIRED';

        if ($isResendAfterExpired && $qualityCertificate->smartca_transaction_id) {
            $statusResult = $this->processSmartCaStatus($qualityCertificate, $smartCaService, $padesService);

            if ($this->isSmartCaSignedResult($statusResult)) {
                return redirect()
                    ->route('quality-certificates.show', $qualityCertificate->fresh())
                    ->with('success', 'Giao dịch ký cũ đã được VNPT SmartCA xác nhận ký thành công. Hệ thống đã hoàn tất phiếu, không gửi lại yêu cầu ký mới. ' . $statusResult['message']);
            }

            $qualityCertificate->refresh();
            $oldData = $qualityCertificate->toArray();
        }

        if ($qualityCertificate->smartca_status === 'PENDING') {
            $this->markSmartCaExpired($qualityCertificate, $oldData);
        }

        try {
            $pdf = Pdf::loadView('quality_certificates.pdf', [
                'certificate' => $qualityCertificate,
                'hardCopy' => false,
            ])->setPaper('a4', 'portrait');

            $pdfContent = $pdf->output();
            $serialNumber = config('services.smartca.serial_number');
            $certificateResult = $smartCaService->getCertificate($smartCaUserId, $serialNumber);
            $smartCaCertificate = $certificateResult['certificate'];
            $chainData = data_get($smartCaCertificate, 'chain_data');
            $padesData = null;
            $dataHashToSign = hash('sha256', $pdfContent);
            $pdfPath = null;
            $calculateHashResult = null;
            $padesProvider = strtolower((string) config('services.smartca.pades_provider', 'vnpt'));

            if (config('services.smartca.pades_enabled') && $padesProvider === 'vnpt') {
                $calculateHashResult = $smartCaService->calculatePdfHash(
                    $qualityCertificate,
                    $pdfContent,
                    (string) data_get($smartCaCertificate, 'cert_data')
                );

                $dataHashToSign = $calculateHashResult['hash_hex'];
                $pdfPath = 'quality-certificates/smartca/' . $qualityCertificate->id . '/' . $calculateHashResult['transaction_id'] . '_original.pdf';
                Storage::disk('local')->put($pdfPath, $pdfContent);
            } elseif (config('services.smartca.pades_enabled')) {
                $padesData = $padesService->prepare(
                    $qualityCertificate,
                    $pdfContent,
                    data_get($smartCaCertificate, 'cert_data'),
                    is_array($chainData) ? $chainData : []
                );

                $dataHashToSign = config('services.smartca.pades_hash_encoding', 'hex') === 'base64'
                    ? $padesData['second_hash_base64']
                    : $padesData['second_hash_hex'];
                $pdfPath = $padesData['prepared_pdf_path'];
            }

            $smartCaResult = $smartCaService->initiateHashSignature(
                $qualityCertificate,
                $dataHashToSign,
                $smartCaUserId,
                $serialNumber
            );

            if (!$pdfPath) {
                $pdfPath = 'quality-certificates/smartca/' . $qualityCertificate->id . '/' . $smartCaResult['transaction_id'] . '.pdf';
                Storage::disk('local')->put($pdfPath, $pdfContent);
            }

            $smartCaResponse = [
                'get_certificate' => [
                    'endpoint' => $certificateResult['endpoint'],
                    'request' => $certificateResult['request'],
                    'response' => $certificateResult['response'],
                ],
                'sign' => [
                    'endpoint' => $smartCaResult['endpoint'],
                    'request' => $smartCaResult['request'],
                    'response' => $smartCaResult['response'],
                ],
            ];

            if ($calculateHashResult) {
                $smartCaResponse['calculate_hash'] = [
                    'endpoint' => $calculateHashResult['endpoint'],
                    'request' => $calculateHashResult['request'],
                    'response' => $calculateHashResult['response'],
                    'transaction_id' => $calculateHashResult['transaction_id'],
                    'file_id' => $calculateHashResult['file_id'],
                    'hash_base64' => $calculateHashResult['hash_base64'],
                    'hash_hex' => $calculateHashResult['hash_hex'],
                    'page_count' => $calculateHashResult['page_count'] ?? null,
                    'signature_options' => $calculateHashResult['signature_options'] ?? null,
                ];
            }

            $qualityCertificate->update([
                'pdf_path' => $pdfPath,
                'smartca_status' => 'PENDING',
                'smartca_transaction_id' => $smartCaResult['transaction_id'],
                'smartca_tran_code' => $smartCaResult['tran_code'],
                'smartca_doc_id' => $smartCaResult['doc_id'],
                'smartca_data_hash' => $smartCaResult['data_hash'],
                'smartca_certificate_data' => data_get($smartCaCertificate, 'cert_data'),
                'smartca_chain_data' => $chainData,
                'smartca_certificate_serial' => data_get($smartCaCertificate, 'serial_number'),
                'smartca_response' => $smartCaResponse,
                'smartca_requested_at' => now(),
                'smartca_signature_value' => null,
                'smartca_timestamp_signature' => null,
                'smartca_completed_at' => null,
                'pades_status' => config('services.smartca.pades_enabled') ? 'PENDING' : 'NOT_CONFIGURED',
                'pades_prepared_pdf_path' => $padesData['prepared_pdf_path'] ?? $pdfPath,
                'pades_state_path' => $padesData['state_path'] ?? null,
                'pades_error' => config('services.smartca.pades_enabled') ? null : 'Chua bat nhung PAdES cho chu ky hash SmartCA.',
            ]);

            ActivityLogger::log(
                'Phiếu CNCL',
                $isResendAfterExpired ? 'smartca_request_resend' : 'smartca_request',
                ($isResendAfterExpired ? 'Gửi lại yêu cầu ký VNPT SmartCA do giao dịch cũ hết hạn cho phiếu CNCL: ' : 'Gửi yêu cầu ký VNPT SmartCA cho phiếu CNCL: ') . $qualityCertificate->certificate_no,
                $oldData,
                $qualityCertificate->fresh()->toArray(),
                $qualityCertificate
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', ($isResendAfterExpired ? 'Đã gửi lại yêu cầu ký sang VNPT SmartCA. ' : 'Đã gửi yêu cầu ký sang VNPT SmartCA. ') . 'Người ký cần xác nhận trên app trong ' . $this->smartCaPendingTtlMinutes() . ' phút, sau đó bấm "Kiểm tra kết quả ký".');
        } catch (\Throwable $e) {
            ActivityLogger::log(
                'Phiếu CNCL',
                'smartca_request_failed',
                'Lỗi gửi yêu cầu ký VNPT SmartCA cho phiếu CNCL: ' . $qualityCertificate->certificate_no . '. Lỗi: ' . $e->getMessage()
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Gửi yêu cầu ký VNPT SmartCA thất bại: ' . $e->getMessage());
        }
    }

    public function checkSmartCaStatus(
        QualityCertificate $qualityCertificate,
        SmartCaService $smartCaService,
        SmartCaPadesService $padesService
    )
    {
        $this->authorizeCenter($qualityCertificate);

        if ($qualityCertificate->signed_at) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', 'Phiếu này đã được ký/phát hành.');
        }

        if ($qualityCertificate->status === 'REJECTED') {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này đã bị Trưởng PTN trả lại, không thể kiểm tra kết quả ký.');
        }

        if (!$qualityCertificate->smartca_transaction_id) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này chưa có giao dịch ký VNPT SmartCA.');
        }

        $result = $this->processSmartCaStatus($qualityCertificate, $smartCaService, $padesService);

        return redirect()
            ->route('quality-certificates.show', $qualityCertificate)
            ->with($result['level'], $result['message']);
    }

    public function bulkCheckSmartCaStatus(
        Request $request,
        SmartCaService $smartCaService,
        SmartCaPadesService $padesService
    )
    {
        $limit = min(max((int) $request->input('limit', 30), 1), 50);

        $certificates = QualityCertificate::with([
            'request.distributionCenter',
            'request.customer',
            'request.creator.roles',
            'details.product',
        ])
            ->whereNull('signed_at')
            ->whereIn('smartca_status', ['PENDING', 'EXPIRED'])
            ->whereNotNull('smartca_transaction_id')
            ->latest('smartca_requested_at')
            ->limit($limit)
            ->get();

        $summary = [
            'signed' => 0,
            'pending' => 0,
            'expired' => 0,
            'email_warning' => 0,
            'error' => 0,
            'skipped' => 0,
        ];
        $errors = [];

        foreach ($certificates as $certificate) {
            if ($certificate->status === 'REJECTED') {
                $summary['skipped']++;
                continue;
            }

            $result = $this->processSmartCaStatus($certificate, $smartCaService, $padesService);

            match ($result['status']) {
                'SIGNED_EMAIL_SENT', 'SIGNED_NO_EMAIL' => $summary['signed']++,
                'SIGNED_EMAIL_MISSING', 'SIGNED_EMAIL_FAILED' => $summary['email_warning']++,
                'EXPIRED' => $summary['expired']++,
                'PENDING' => $summary['pending']++,
                default => $summary['error']++,
            };

            if (($result['level'] ?? null) === 'error' && count($errors) < 5) {
                $errors[] = $certificate->certificate_no . ': ' . $result['message'];
            }
        }

        $message = 'Đã kiểm tra ' . $certificates->count() . ' phiếu đang chờ app. '
            . 'Ký thành công: ' . $summary['signed']
            . ', còn chờ: ' . $summary['pending']
            . ', hết hạn: ' . $summary['expired']
            . ', đã ký nhưng cần kiểm tra email: ' . $summary['email_warning']
            . ', lỗi: ' . $summary['error']
            . ', bỏ qua: ' . $summary['skipped'] . '.';

        if ($errors) {
            $message .= ' Lỗi đầu tiên: ' . implode(' | ', $errors);
        }

        ActivityLogger::log(
            'Phiếu CNCL',
            'bulk_smartca_status',
            $message,
            null,
            $summary
        );

        return redirect()
            ->route('quality-certificates.signing-queue', ['status' => 'ACTIONABLE'])
            ->with($summary['error'] > 0 ? 'error' : 'success', $message);
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

        if (
            $qualityCertificate->signed_at
            && $qualityCertificate->pdf_path
            && Storage::disk('local')->exists($qualityCertificate->pdf_path)
        ) {
            return response(Storage::disk('local')->get($qualityCertificate->pdf_path), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $qualityCertificate->certificate_no . '.pdf"',
            ]);
        }

        $pdf = Pdf::loadView('quality_certificates.pdf', [
            'certificate' => $qualityCertificate,
            'hardCopy' => false,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($qualityCertificate->certificate_no . '.pdf');
    }

    public function requestReissue(Request $request, QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        if (!$qualityCertificate->canRequestReissue()) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Chỉ phiếu đã ký số/phát hành thành công và chưa bị hủy mới được yêu cầu cấp lại.');
        }

        $data = $request->validate([
            'reissue_reason' => ['required', 'string', 'max:2000'],
        ]);

        $existingReissue = $this->activeReissueRequestForCertificate($qualityCertificate);

        if ($existingReissue) {
            return redirect()
                ->route($existingReissue->status === 'WAIT_DVKH' ? 'certificate-requests.edit' : 'certificate-requests.show', $existingReissue)
                ->with('error', 'Phiếu này đã có yêu cầu cấp lại đang xử lý.');
        }

        $qualityCertificate->load([
            'request.customer',
            'request.distributionCenter',
            'request.details',
            'details',
        ]);

        DB::beginTransaction();

        try {
            $oldData = $qualityCertificate->toArray();
            $oldRequest = $qualityCertificate->request;

            $newRequest = CertificateRequest::create([
                'request_no' => $this->generateRequestNo(),
                'request_type' => 'REISSUE',
                'reissue_of_certificate_id' => $qualityCertificate->id,
                'reissue_reason' => $data['reissue_reason'],
                'distribution_center_id' => $oldRequest->distribution_center_id,
                'customer_id' => $oldRequest->customer_id,
                'delivery_date' => $oldRequest->delivery_date,
                'invoice_no' => $oldRequest->invoice_no,
                'require_hard_copy' => $oldRequest->require_hard_copy,
                'hard_copy_quantity' => $oldRequest->hard_copy_quantity,
                'is_urgent' => $oldRequest->is_urgent,
                'urgent_reason_id' => $oldRequest->urgent_reason_id,
                'requester_name' => $oldRequest->requester_name,
                'note' => trim(($oldRequest->note ? $oldRequest->note . "\n" : '') . '[Yêu cầu cấp lại từ phiếu ' . $qualityCertificate->certificate_no . ']: ' . $data['reissue_reason']),
                'status' => 'WAIT_DVKH',
                'created_by' => Auth::id(),
            ]);

            $newRequest->reissueCertificates()->syncWithoutDetaching([$qualityCertificate->id]);

            foreach ($oldRequest->details as $detail) {
                $newRequest->details()->create([
                    'product_id' => $detail->product_id,
                    'quantity' => $detail->quantity,
                ]);
            }

            ActivityLogger::log(
                'Phiếu CNCL',
                'request_reissue',
                'Tạo yêu cầu cấp lại cho phiếu CNCL: ' . $qualityCertificate->certificate_no . '. Yêu cầu mới: ' . $newRequest->request_no,
                $oldData,
                $newRequest->load('details')->toArray(),
                $qualityCertificate,
                ['new_request_id' => $newRequest->id, 'new_request_no' => $newRequest->request_no]
            );

            DB::commit();

            return redirect()
                ->route('certificate-requests.edit', $newRequest)
                ->with('success', 'Đã tạo yêu cầu cấp lại từ phiếu cũ. Vui lòng kiểm tra, chỉnh sửa dữ liệu nếu cần rồi lưu để DVKH xác nhận.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Không thể tạo yêu cầu cấp lại: ' . $e->getMessage());
        }
    }

    public function bulkRequestReissue(Request $request)
    {
        $data = $request->validate([
            'certificate_ids' => ['required', 'array', 'min:2'],
            'certificate_ids.*' => ['integer', 'distinct', 'exists:quality_certificates,id'],
            'reissue_reason' => ['required', 'string', 'max:2000'],
        ]);

        $certificates = QualityCertificate::with([
            'request.customer',
            'request.distributionCenter',
            'request.details',
            'details',
        ])
            ->whereIn('id', $data['certificate_ids'])
            ->get();

        if ($certificates->count() !== count($data['certificate_ids'])) {
            return redirect()
                ->route('quality-certificates.index')
                ->with('error', 'Danh sách phiếu chọn không hợp lệ.');
        }

        foreach ($certificates as $certificate) {
            $this->authorizeCenter($certificate);

            if (!$certificate->canRequestReissue()) {
                return redirect()
                    ->route('quality-certificates.index')
                    ->with('error', 'Chỉ phiếu đã ký/phát hành, chưa bị hủy và chưa có phiếu thay thế mới được gom cấp lại.');
            }

            if ($existingReissue = $this->activeReissueRequestForCertificate($certificate)) {
                return redirect()
                    ->route($existingReissue->status === 'WAIT_DVKH' ? 'certificate-requests.edit' : 'certificate-requests.show', $existingReissue)
                    ->with('error', 'Phiếu ' . $certificate->certificate_no . ' đã có yêu cầu cấp lại đang xử lý.');
            }
        }

        $centerIds = $certificates
            ->map(fn ($certificate) => $certificate->request?->distribution_center_id)
            ->filter()
            ->unique();

        if ($centerIds->count() !== 1) {
            return redirect()
                ->route('quality-certificates.index')
                ->with('error', 'Chỉ được gom cấp lại các phiếu thuộc cùng một trung tâm phân phối.');
        }

        DB::beginTransaction();

        try {
            $newRequest = $this->createReissueRequestFromCertificates($certificates, $data['reissue_reason']);

            ActivityLogger::log(
                'Phiếu CNCL',
                'bulk_request_reissue',
                'Tạo yêu cầu cấp lại gom nhiều phiếu CNCL: ' . $certificates->pluck('certificate_no')->implode(', ') . '. Yêu cầu mới: ' . $newRequest->request_no,
                $certificates->map->toArray()->all(),
                $newRequest->load(['details', 'reissueCertificates'])->toArray(),
                $newRequest,
                [
                    'old_certificate_ids' => $certificates->pluck('id')->values()->all(),
                    'old_certificate_nos' => $certificates->pluck('certificate_no')->values()->all(),
                ]
            );

            DB::commit();

            return redirect()
                ->route('certificate-requests.edit', $newRequest)
                ->with('success', 'Đã tạo yêu cầu cấp lại gom ' . $certificates->count() . ' phiếu cũ. Vui lòng kiểm tra và chỉnh sửa dữ liệu trước khi DVKH xác nhận.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('quality-certificates.index')
                ->with('error', 'Không thể tạo yêu cầu cấp lại nhiều phiếu: ' . $e->getMessage());
        }
    }

    public function rejectSignature(Request $request, QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        if ($qualityCertificate->signed_at || in_array($qualityCertificate->status, ['ISSUED', 'REVOKED'])) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Chỉ được trả lại phiếu chưa ký/phát hành.');
        }

        if ($qualityCertificate->status === 'REJECTED') {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu này đã được trả lại trước đó.');
        }

        if ($qualityCertificate->smartca_status === 'PENDING') {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Phiếu đang chờ xác nhận ký trên VNPT SmartCA, không thể trả lại ở thời điểm này.');
        }

        $data = $request->validate([
            'reject_to' => ['required', 'in:PTN,DVKH'],
            'rejected_reason' => ['required', 'string', 'max:2000'],
        ]);

        $qualityCertificate->loadMissing('request');

        if (!$qualityCertificate->request) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Không tìm thấy yêu cầu gốc của phiếu để trả lại.');
        }

        $newRequestStatus = $data['reject_to'] === 'DVKH' ? 'WAIT_DVKH' : 'PTN_PROCESSING';
        $targetLabel = $data['reject_to'] === 'DVKH' ? 'DVKH xác nhận lại' : 'PTN xử lý lại';
        $noteLine = '[Trưởng PTN trả lại ' . $targetLabel . ' phiếu ' . $qualityCertificate->certificate_no . ']: ' . $data['rejected_reason'];

        DB::beginTransaction();

        try {
            $oldData = [
                'certificate' => $qualityCertificate->toArray(),
                'request' => $qualityCertificate->request->toArray(),
            ];

            $qualityCertificate->update([
                'status' => 'REJECTED',
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'rejected_to' => $data['reject_to'],
                'rejected_reason' => $data['rejected_reason'],
                'smartca_status' => null,
            ]);

            $qualityCertificate->request->update([
                'status' => $newRequestStatus,
                'note' => trim(($qualityCertificate->request->note ? $qualityCertificate->request->note . "\n" : '') . $noteLine),
            ]);

            ActivityLogger::log(
                'Phiếu CNCL',
                'reject_signature',
                'Trưởng PTN trả lại phiếu CNCL: ' . $qualityCertificate->certificate_no . ' về ' . $targetLabel . '. Lý do: ' . $data['rejected_reason'],
                $oldData,
                [
                    'certificate' => $qualityCertificate->fresh()->toArray(),
                    'request' => $qualityCertificate->request->fresh()->toArray(),
                ],
                $qualityCertificate,
                ['request_id' => $qualityCertificate->request->id ?? null, 'request_no' => $qualityCertificate->request->request_no ?? null]
            );

            DB::commit();

            app(NotificationService::class)->notifyCertificateReturned(
                $qualityCertificate->fresh(['request.distributionCenter', 'request.customer']),
                $data['reject_to']
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', 'Đã trả lại phiếu về bước ' . $targetLabel . '.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Không thể trả lại phiếu: ' . $e->getMessage());
        }
    }

    public function printHardCopy(Request $request, QualityCertificate $qualityCertificate)
    {
        $this->authorizeCenter($qualityCertificate);

        if (!$qualityCertificate->signed_at) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Chỉ được in ký tươi khi phiếu đã ký số/phát hành.');
        }

        if ($qualityCertificate->status === 'REVOKED') {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Không được in ký tươi phiếu đã hủy/thu hồi.');
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
            $qualityCertificate->fresh()->toArray(),
            $qualityCertificate
        );

        $pdf = Pdf::loadView('quality_certificates.hard_copy_print', [
            'certificate' => $qualityCertificate->fresh()->load([
                'request.distributionCenter',
                'request.customer',
                'details.product',
                'creator',
            ]),
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

        if ($qualityCertificate->status === 'REVOKED') {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Không được gửi lại email phiếu đã hủy/thu hồi.');
        }

        $qualityCertificate->load([
            'request.distributionCenter',
            'request.customer',
            'details.product',
        ]);

        $recipients = $this->qualityCertificateMailRecipients($qualityCertificate);

        if (!$recipients['to']) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Tài khoản Trung tâm phân phối tạo yêu cầu chưa có email nhận phiếu.');
        }

        try {
            Mail::to($recipients['to'])
                ->cc($recipients['cc'])
                ->send(new QualityCertificateIssuedMail($qualityCertificate));

            ActivityLogger::log(
                'Phiếu CNCL',
                'resend_email',
                'Gửi lại email phiếu CNCL: ' . $qualityCertificate->certificate_no . ' tới ' . $recipients['to'] . ($recipients['cc'] ? '. CC: ' . implode(', ', $recipients['cc']) : '')
            );

            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('success', 'Đã gửi lại email phiếu CNCL cho Trung tâm phân phối thành công.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('quality-certificates.show', $qualityCertificate)
                ->with('error', 'Gửi lại email thất bại: ' . $e->getMessage());
        }
    }

    private function qualityCertificateMailRecipients(QualityCertificate $qualityCertificate): array
    {
        $qualityCertificate->loadMissing([
            'request.distributionCenter',
            'request.customer',
            'request.creator.roles',
        ]);

        $to = $this->requestingDistributionCenterUserEmail($qualityCertificate);

        $configuredCc = collect($this->configuredCertificateMailCc())
            ->map(fn ($email) => $this->normalizeEmail($email))
            ->filter();

        $customerCc = [];

        if (SystemSetting::getValue('certificate_mail_cc_customer_email', config('certificate_mail.quality_certificate.cc_customer_email', true))) {
            $customerEmail = $this->normalizeEmail($qualityCertificate->request?->customer?->email);

            if ($customerEmail) {
                $customerCc[] = $customerEmail;
            }
        }

        $cc = $configuredCc
            ->merge($customerCc)
            ->filter(fn ($email) => $email !== $to)
            ->unique()
            ->values()
            ->all();

        return [
            'to' => $to,
            'cc' => $cc,
        ];
    }

    private function configuredCertificateMailCc(): array
    {
        $keys = [
            'certificate_mail_cc_dvkh',
            'certificate_mail_cc_ptn',
            'certificate_mail_cc_extra',
        ];

        $hasDatabaseSettings = SystemSetting::whereIn('key', $keys)->exists();

        $settings = collect($keys)
            ->map(fn ($key) => SystemSetting::getValue($key, ''))
            ->all();

        $configured = collect($settings)
            ->flatMap(fn ($value) => preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY))
            ->filter()
            ->values();

        if ($hasDatabaseSettings) {
            return $configured->all();
        }

        return collect(config('certificate_mail.quality_certificate.cc', []))
            ->flatten()
            ->filter()
            ->values()
            ->all();
    }

    private function certificateWorkflowSteps(QualityCertificate $qualityCertificate, $logs): array
    {
        $request = $qualityCertificate->request;
        $logTime = function (array $actions) use ($logs) {
            $log = $logs->first(fn ($item) => in_array($item->properties['action'] ?? null, $actions, true));

            return $log?->created_at;
        };

        $steps = [
            [
                'title' => 'Trung tâm tạo yêu cầu',
                'status' => 'done',
                'icon' => 'fas fa-file-alt',
                'time' => $request?->created_at ?: $qualityCertificate->created_at,
                'description' => $request ? 'Yêu cầu ' . $request->request_no . ' được khởi tạo.' : 'Phiếu được PTN lập trực tiếp.',
            ],
            [
                'title' => 'DVKH kiểm tra',
                'status' => 'pending',
                'icon' => 'fas fa-user-check',
                'time' => $logTime(['approve']),
                'description' => 'Chờ DVKH xác nhận thông tin yêu cầu.',
            ],
            [
                'title' => 'PTN lập phiếu',
                'status' => 'pending',
                'icon' => 'fas fa-vials',
                'time' => $qualityCertificate->created_at,
                'description' => 'Chờ PTN lập phiếu CNCL.',
            ],
            [
                'title' => 'Trưởng PTN ký số',
                'status' => 'pending',
                'icon' => 'fas fa-file-signature',
                'time' => $qualityCertificate->smartca_requested_at ?: $logTime(['smartca_request', 'smartca_request_resend']),
                'description' => 'Chờ gửi yêu cầu ký VNPT SmartCA.',
            ],
            [
                'title' => 'Phát hành / thu hồi',
                'status' => 'pending',
                'icon' => 'fas fa-paper-plane',
                'time' => $qualityCertificate->signed_at ?: $qualityCertificate->revoked_at,
                'description' => 'Chờ ký số thành công và phát hành phiếu.',
            ],
        ];

        if (!$request) {
            $steps[1]['status'] = 'skipped';
            $steps[1]['description'] = 'Không qua bước DVKH do PTN lập trực tiếp.';
        } elseif ($request->status === 'WAIT_DVKH') {
            $steps[1]['status'] = 'current';
        } elseif ($request->status === 'CANCELLED') {
            $steps[1]['status'] = 'danger';
            $steps[1]['description'] = 'Yêu cầu đã bị trả lại / hủy.';
        } else {
            $steps[1]['status'] = 'done';
            $steps[1]['description'] = 'DVKH đã xác nhận và chuyển yêu cầu sang PTN.';
        }

        if ($qualityCertificate->status === 'REJECTED') {
            $steps[2]['status'] = 'danger';
            $steps[2]['description'] = 'Phiếu đã bị Trưởng PTN trả lại.';
        } else {
            $steps[2]['status'] = 'done';
            $steps[2]['description'] = 'PTN đã lập phiếu ' . $qualityCertificate->certificate_no . '.';
        }

        if ($qualityCertificate->status === 'REVOKED') {
            $steps[3]['status'] = 'done';
            $steps[3]['description'] = 'Phiếu cũ đã được ký số trước khi bị thu hồi.';
        } elseif ($qualityCertificate->status === 'REJECTED') {
            $steps[3]['status'] = 'danger';
            $steps[3]['time'] = $qualityCertificate->rejected_at;
            $steps[3]['description'] = 'Trưởng PTN đã trả lại phiếu: ' . ($qualityCertificate->rejected_reason ?: '-');
        } elseif ($qualityCertificate->signed_at) {
            $steps[3]['status'] = 'done';
            $steps[3]['time'] = $qualityCertificate->signed_at;
            $steps[3]['description'] = 'Phiếu đã ký số thành công.';
        } elseif ($qualityCertificate->smartcaStatusExpired()) {
            $steps[3]['status'] = 'danger';
            $steps[3]['description'] = 'Yêu cầu ký đã quá hạn, cần kiểm tra kết quả cũ hoặc gửi lại yêu cầu ký.';
        } elseif ($qualityCertificate->smartca_status === 'PENDING') {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Đang chờ Trưởng PTN xác nhận trên app VNPT SmartCA.';
        } else {
            $steps[3]['status'] = 'current';
            $steps[3]['description'] = 'Chờ Trưởng PTN kiểm tra và gửi yêu cầu ký số.';
        }

        if ($qualityCertificate->status === 'REVOKED') {
            $steps[4]['status'] = 'danger';
            $steps[4]['description'] = 'Phiếu đã hủy / thu hồi. Lý do: ' . ($qualityCertificate->revoked_reason ?: '-');
        } elseif ($qualityCertificate->signed_at || $qualityCertificate->status === 'ISSUED') {
            $steps[4]['status'] = 'done';
            $steps[4]['time'] = $qualityCertificate->signed_at;
            $steps[4]['description'] = 'Phiếu đã phát hành. Hệ thống gửi email theo cấu hình hiện tại.';
        } elseif ($qualityCertificate->status === 'REJECTED') {
            $steps[4]['status'] = 'skipped';
            $steps[4]['description'] = 'Chưa phát hành vì phiếu đã bị trả lại.';
        }

        return $steps;
    }

    private function processSmartCaStatus(
        QualityCertificate $qualityCertificate,
        SmartCaService $smartCaService,
        SmartCaPadesService $padesService
    ): array {
        $qualityCertificate->loadMissing([
            'request.distributionCenter',
            'request.customer',
            'request.creator.roles',
            'details.product',
        ]);

        $oldData = $qualityCertificate->toArray();

        try {
            $statusResult = $smartCaService->signatureStatus($qualityCertificate->smartca_transaction_id);
            $statusResponse = $statusResult['response'];
            $signatures = collect(data_get($statusResponse, 'data.signatures', []));
            $signature = $signatures->firstWhere('doc_id', $qualityCertificate->smartca_doc_id) ?: $signatures->first();

            if (!$signature || blank(data_get($signature, 'signature_value'))) {
                $expired = $qualityCertificate->smartca_status === 'EXPIRED'
                    || $this->smartCaPendingExpired($qualityCertificate);

                $qualityCertificate->update([
                    'smartca_status' => $expired ? 'EXPIRED' : 'PENDING',
                    'pades_status' => $expired && $qualityCertificate->pades_status === 'PENDING'
                        ? 'EXPIRED'
                        : $qualityCertificate->pades_status,
                    'smartca_response' => array_merge($qualityCertificate->smartca_response ?? [], [
                        'status' => [
                            'endpoint' => $statusResult['endpoint'],
                            'request' => $statusResult['request'],
                            'response' => $statusResponse,
                        ],
                    ]),
                ]);

                if ($expired) {
                    ActivityLogger::log(
                        'Phiếu CNCL',
                        'smartca_request_expired',
                        'Yêu cầu ký VNPT SmartCA đã hết hạn cho phiếu CNCL: ' . $qualityCertificate->certificate_no,
                        $oldData,
                        $qualityCertificate->fresh()->toArray(),
                        $qualityCertificate
                    );
                }

                return [
                    'status' => $expired ? 'EXPIRED' : 'PENDING',
                    'level' => 'error',
                    'message' => $expired
                        ? 'Yêu cầu ký VNPT SmartCA đã quá ' . $this->smartCaPendingTtlMinutes() . ' phút và chưa có chữ ký. Vui lòng bấm gửi lại yêu cầu ký.'
                        : 'VNPT SmartCA chưa trả chữ ký. Vui lòng xác nhận trên app rồi kiểm tra lại.',
                ];
            }

            $signedPdfPath = $qualityCertificate->pdf_path;
            $signType = strtolower((string) config('services.smartca.sign_type', 'hash'));
            $padesStatus = 'SIGNATURE_ONLY';
            $padesError = 'SmartCA da tra chu ky hash nhung he thong chua nhung PAdES vao PDF.';
            $storedStatusResponse = array_merge($qualityCertificate->smartca_response ?? [], [
                'status' => [
                    'endpoint' => $statusResult['endpoint'],
                    'request' => $statusResult['request'],
                    'response' => $statusResponse,
                ],
            ]);

            if ($signType === 'file') {
                $signedPdfContent = $this->extractSignedPdfContent((string) data_get($signature, 'signature_value'));
                $signedPdfPath = 'quality-certificates/smartca/' . $qualityCertificate->id . '/' . $qualityCertificate->smartca_transaction_id . '_signed.pdf';

                Storage::disk('local')->put($signedPdfPath, $signedPdfContent);
                $padesStatus = 'SIGNED_PDF';
                $padesError = null;

                $storedStatusResponse['status']['response']['data']['signatures'] = collect(data_get($statusResponse, 'data.signatures', []))
                    ->map(function ($item) use ($qualityCertificate, $signedPdfPath) {
                        if (data_get($item, 'doc_id') === $qualityCertificate->smartca_doc_id) {
                            $item['signature_value'] = '[signed_pdf_stored:' . $signedPdfPath . ']';
                        }

                        return $item;
                    })
                    ->values()
                    ->all();
            }

            if ($signType === 'hash' && config('services.smartca.pades_enabled') && strtolower((string) config('services.smartca.pades_provider', 'vnpt')) === 'vnpt') {
                $hashTransactionId = data_get($qualityCertificate->smartca_response, 'calculate_hash.transaction_id');
                $fileId = data_get($qualityCertificate->smartca_response, 'calculate_hash.file_id');

                if (blank($hashTransactionId) || blank($fileId)) {
                    throw new \RuntimeException('Thieu tranId/fileID cua buoc VNPT calculateHash nen khong the goi signExternal.');
                }

                $signExternalResult = $smartCaService->externalizePdfSignature(
                    (string) $hashTransactionId,
                    (string) $fileId,
                    (string) data_get($signature, 'signature_value')
                );

                $signedPdfPath = 'quality-certificates/smartca/' . $qualityCertificate->id . '/' . $qualityCertificate->smartca_transaction_id . '_signed.pdf';
                Storage::disk('local')->put($signedPdfPath, $signExternalResult['signed_pdf']);

                $storedStatusResponse['sign_external'] = [
                    'endpoint' => $signExternalResult['endpoint'],
                    'request' => $signExternalResult['request'],
                    'response' => $signExternalResult['response'],
                    'signed_pdf_path' => $signedPdfPath,
                ];

                $padesStatus = 'SIGNED_PDF';
                $padesError = null;
            } elseif ($signType === 'hash' && config('services.smartca.pades_enabled')) {
                $signedPdfPath = $padesService->finalize($qualityCertificate, (string) data_get($signature, 'signature_value'));
                $padesStatus = 'SIGNED_PDF';
                $padesError = null;
            }

            $qualityCertificate->update([
                'signed_at' => now(),
                'status' => 'ISSUED',
                'signed_by' => Auth::user()->name,
                'pdf_path' => $signedPdfPath,
                'smartca_status' => 'SIGNED',
                'smartca_signature_value' => $signType === 'file' ? null : data_get($signature, 'signature_value'),
                'smartca_timestamp_signature' => data_get($signature, 'timestamp_signature'),
                'smartca_response' => $storedStatusResponse,
                'smartca_completed_at' => now(),
                'pades_status' => $padesStatus,
                'pades_error' => $padesError,
            ]);

            if ($qualityCertificate->request) {
                $qualityCertificate->request->update([
                    'status' => 'COMPLETED',
                ]);
            }

            app(NotificationService::class)->notifyCertificateSigned(
                $qualityCertificate->fresh(['request.distributionCenter', 'request.customer'])
            );
        } catch (\Throwable $e) {
            ActivityLogger::log(
                'Phiếu CNCL',
                'smartca_status_failed',
                'Lỗi kiểm tra kết quả ký VNPT SmartCA cho phiếu CNCL: ' . $qualityCertificate->certificate_no . '. Lỗi: ' . $e->getMessage()
            );

            return [
                'status' => 'ERROR',
                'level' => 'error',
                'message' => 'Kiểm tra kết quả ký VNPT SmartCA thất bại: ' . $e->getMessage(),
            ];
        }

        $autoSendEmail = SystemSetting::getValue('auto_send_email_after_sign', true);

        if (!$autoSendEmail) {
            ActivityLogger::log(
                'Phiếu CNCL',
                'smartca_signed_without_email',
                'Hoàn tất ký VNPT SmartCA phiếu CNCL không gửi email tự động: ' . $qualityCertificate->certificate_no,
                $oldData,
                $qualityCertificate->fresh()->load('request')->toArray(),
                $qualityCertificate
            );

            return [
                'status' => 'SIGNED_NO_EMAIL',
                'level' => 'success',
                'message' => 'Đã hoàn tất ký VNPT SmartCA. Hệ thống đang tắt tự động gửi email.',
            ];
        }

        try {
            $recipients = $this->qualityCertificateMailRecipients($qualityCertificate->fresh());

            if (!$recipients['to']) {
                return [
                    'status' => 'SIGNED_EMAIL_MISSING',
                    'level' => 'error',
                    'message' => 'Phiếu đã ký VNPT SmartCA nhưng tài khoản Trung tâm phân phối tạo yêu cầu chưa có email nhận phiếu.',
                ];
            }

            Mail::to($recipients['to'])
                ->cc($recipients['cc'])
                ->send(new QualityCertificateIssuedMail($qualityCertificate->fresh()));

            ActivityLogger::log(
                'Phiếu CNCL',
                'smartca_signed_and_send_email',
                'Hoàn tất ký VNPT SmartCA và gửi email phiếu CNCL: ' . $qualityCertificate->certificate_no . ' tới ' . $recipients['to'] . ($recipients['cc'] ? '. CC: ' . implode(', ', $recipients['cc']) : ''),
                $oldData,
                $qualityCertificate->fresh()->load('request')->toArray(),
                $qualityCertificate
            );

            return [
                'status' => 'SIGNED_EMAIL_SENT',
                'level' => 'success',
                'message' => 'Đã hoàn tất ký VNPT SmartCA và gửi email phiếu CNCL cho Trung tâm phân phối thành công.',
            ];
        } catch (\Throwable $e) {
            ActivityLogger::log(
                'Phiếu CNCL',
                'send_email_failed',
                'Lỗi gửi email phiếu CNCL: ' . $qualityCertificate->certificate_no . '. Lỗi: ' . $e->getMessage()
            );

            return [
                'status' => 'SIGNED_EMAIL_FAILED',
                'level' => 'error',
                'message' => 'Phiếu đã ký VNPT SmartCA nhưng gửi email thất bại: ' . $e->getMessage(),
            ];
        }
    }

    private function isSmartCaSignedResult(array $result): bool
    {
        return in_array($result['status'] ?? null, [
            'SIGNED_EMAIL_SENT',
            'SIGNED_NO_EMAIL',
            'SIGNED_EMAIL_MISSING',
            'SIGNED_EMAIL_FAILED',
        ], true);
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    private function requestingDistributionCenterUserEmail(QualityCertificate $qualityCertificate): ?string
    {
        $request = $qualityCertificate->request;
        $creator = $request?->creator;

        if ($creator && $creator->hasRole('TrungTam')) {
            return $this->normalizeEmail($creator->email);
        }

        if (!$request?->distribution_center_id) {
            return null;
        }

        $centerUser = User::role('TrungTam')
            ->where('distribution_center_id', $request->distribution_center_id)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderBy('id')
            ->first();

        return $this->normalizeEmail($centerUser?->email);
    }

    private function smartCaPendingTtlMinutes(): int
    {
        return max(1, (int) config('services.smartca.pending_ttl_minutes', 5));
    }

    private function smartCaPendingExpired(QualityCertificate $qualityCertificate): bool
    {
        if ($qualityCertificate->smartca_status !== 'PENDING') {
            return false;
        }

        $requestedAt = $qualityCertificate->smartca_requested_at ?: $qualityCertificate->updated_at;

        if (!$requestedAt) {
            return false;
        }

        return $requestedAt->copy()
            ->addMinutes($this->smartCaPendingTtlMinutes())
            ->lte(now());
    }

    private function markSmartCaExpired(QualityCertificate $qualityCertificate, array $oldData): void
    {
        $qualityCertificate->update([
            'smartca_status' => 'EXPIRED',
            'pades_status' => $qualityCertificate->pades_status === 'PENDING'
                ? 'EXPIRED'
                : $qualityCertificate->pades_status,
        ]);

        ActivityLogger::log(
            'Phiếu CNCL',
            'smartca_request_expired',
            'Yêu cầu ký VNPT SmartCA đã hết hạn cho phiếu CNCL: ' . $qualityCertificate->certificate_no,
            $oldData,
            $qualityCertificate->fresh()->toArray(),
            $qualityCertificate
        );
    }

    private function extractSignedPdfContent(string $signatureValue): string
    {
        $decoded = base64_decode($signatureValue, true);

        if ($decoded === false || !str_starts_with($decoded, '%PDF')) {
            if ((bool) config('services.smartca.require_signed_pdf', true)) {
                throw new \RuntimeException('VNPT SmartCA đã trả chữ ký nhưng không phải file PDF đã ký. Vui lòng kiểm tra gói API có hỗ trợ sign_type=file/PDF hay không.');
            }

            return $signatureValue;
        }

        return $decoded;
    }

    private function activeReissueRequestForCertificate(QualityCertificate $qualityCertificate): ?CertificateRequest
    {
        return CertificateRequest::where('request_type', 'REISSUE')
            ->whereNotIn('status', ['CANCELLED', 'COMPLETED'])
            ->where(function ($query) use ($qualityCertificate) {
                $query->where('reissue_of_certificate_id', $qualityCertificate->id)
                    ->orWhereHas('reissueCertificates', function ($certificateQuery) use ($qualityCertificate) {
                        $certificateQuery->where('quality_certificates.id', $qualityCertificate->id);
                    });
            })
            ->first();
    }

    private function createReissueRequestFromCertificates($certificates, string $reason): CertificateRequest
    {
        $certificates = collect($certificates)->values();
        $primaryCertificate = $certificates->first();
        $oldRequest = $primaryCertificate->request;

        $certificateNos = $certificates->pluck('certificate_no')->implode(', ');

        $newRequest = CertificateRequest::create([
            'request_no' => $this->generateRequestNo(),
            'request_type' => 'REISSUE',
            'reissue_of_certificate_id' => $primaryCertificate->id,
            'reissue_reason' => $reason,
            'distribution_center_id' => $oldRequest->distribution_center_id,
            'customer_id' => $oldRequest->customer_id,
            'delivery_date' => $oldRequest->delivery_date,
            'invoice_no' => $oldRequest->invoice_no,
            'require_hard_copy' => $oldRequest->require_hard_copy,
            'hard_copy_quantity' => $oldRequest->hard_copy_quantity,
            'is_urgent' => $oldRequest->is_urgent,
            'urgent_reason_id' => $oldRequest->urgent_reason_id,
            'requester_name' => $oldRequest->requester_name,
            'note' => trim(($oldRequest->note ? $oldRequest->note . "\n" : '') . '[Yêu cầu cấp lại từ phiếu ' . $certificateNos . ']: ' . $reason),
            'status' => 'WAIT_DVKH',
            'created_by' => Auth::id(),
        ]);

        $newRequest->reissueCertificates()->syncWithoutDetaching($certificates->pluck('id')->all());

        $mergedDetails = $certificates
            ->flatMap(function ($certificate) {
                return $certificate->request?->details ?: collect();
            })
            ->groupBy('product_id')
            ->map(fn ($details, $productId) => [
                'product_id' => $productId,
                'quantity' => $details->sum('quantity'),
            ]);

        foreach ($mergedDetails as $detail) {
            $newRequest->details()->create($detail);
        }

        return $newRequest;
    }

    private function generateRequestNo(): string
    {
        $prefix = 'YC-' . date('Ymd') . '-';

        $count = CertificateRequest::whereDate('created_at', now()->toDateString())->count() + 1;

        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
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
