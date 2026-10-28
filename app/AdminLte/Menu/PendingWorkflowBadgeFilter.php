<?php

namespace App\AdminLte\Menu;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use Illuminate\Support\Facades\Auth;
use JeroenNoten\LaravelAdminLte\Menu\Filters\FilterInterface;

class PendingWorkflowBadgeFilter implements FilterInterface
{
    public function transform($item)
    {
        $url = $item['url'] ?? '';
        $permission = match ($url) {
            'dvkh/requests' => 'dvkh.process',
            'ptn/requests' => 'ptn.process',
            'quality-certificates/signing-queue' => 'certificate.sign',
            default => null,
        };
        if (!$permission) return $item;
        $user = Auth::user();
        if (!$user || $user->hasRole('TrungTam') || !$user->can($permission)) return $item;
        $count = match ($url) {
            'dvkh/requests' => CertificateRequest::where('status', 'WAIT_DVKH')->count(),
            'ptn/requests' => CertificateRequest::waitingForPtnWork()->count(),
            default => QualityCertificate::waitingForManagerApproval()->count(),
        };
        if ($url === 'quality-certificates/signing-queue') {
            $item['url'] .= '?status=WAIT_APPROVAL';
        }
        if ($count > 0) {
            $item['label'] = $count > 99 ? '99+' : (string) $count;
            $item['label_color'] = 'warning';
        }
        return $item;
    }
}
