<?php

namespace App\Services;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class WorkflowHistoryService
{
    public function forRequest(CertificateRequest $request, int $limit = 30): Collection
    {
        $certificateIds = QualityCertificate::query()
            ->where('certificate_request_id', $request->id)
            ->pluck('id');

        return Activity::query()
            ->with('causer')
            ->where(function ($query) use ($request, $certificateIds) {
                $query->where(function ($subjectQuery) use ($request) {
                    $subjectQuery
                        ->where('subject_type', CertificateRequest::class)
                        ->where('subject_id', $request->id);
                });

                if ($certificateIds->isNotEmpty()) {
                    $query->orWhere(function ($subjectQuery) use ($certificateIds) {
                        $subjectQuery
                            ->where('subject_type', QualityCertificate::class)
                            ->whereIn('subject_id', $certificateIds);
                    });
                }

                $query->orWhere('description', 'like', '%' . $request->request_no . '%');
            })
            ->latest()
            ->limit($limit)
            ->get();
    }
}
