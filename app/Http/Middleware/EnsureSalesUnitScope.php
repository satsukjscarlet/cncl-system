<?php

namespace App\Http\Middleware;

use App\Models\CertificateRequest;
use App\Models\QualityCertificate;
use App\Services\SalesUnitRequestAccess;
use Closure;
use Illuminate\Http\Request;

class EnsureSalesUnitScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }
        if ($request->routeIs('logout', 'login')) {
            return $next($request);
        }
        $item = $request->route('certificateRequest') ?? $request->route('certificate_request');
        if ($item instanceof CertificateRequest && ! $user->hasRole('Admin')) {
            abort_unless(CertificateRequest::visibleTo($user)->whereKey($item->id)->exists(), 403);
        }
        if (! $user->isSalesUnit()) {
            return $next($request);
        }
        app(SalesUnitRequestAccess::class)->validateAccount($user);
        $allowed = ['login', 'logout', 'dashboard', 'profile.edit', 'profile.update', 'password.update',
            'password.confirm', 'notifications.*', 'work-queue.feed', 'certificate-requests.*',
            'quality-certificates.index', 'quality-certificates.show', 'quality-certificates.pdf'];
        abort_unless($request->routeIs(...$allowed), 403);
        // New internal request routes are not automatically available to sales units.
        if ($request->routeIs('certificate-requests.*')) {
            abort_unless($request->routeIs('certificate-requests.index', 'certificate-requests.create', 'certificate-requests.store',
                'certificate-requests.show', 'certificate-requests.edit', 'certificate-requests.update', 'certificate-requests.destroy',
                'certificate-requests.submit-draft', 'certificate-requests.product-options', 'certificate-requests.customer-options',
                'certificate-requests.check-invoice', 'certificate-requests.products-template', 'certificate-requests.import-products',
                'certificate-requests.paste-products'), 403);
        }
        $certificate = $request->route('qualityCertificate') ?? $request->route('quality_certificate');
        if ($certificate instanceof QualityCertificate) {
            abort_unless($certificate->signed_at && $certificate->status === 'ISSUED'
                && $certificate->request && CertificateRequest::visibleTo($user)->whereKey($certificate->certificate_request_id)->exists(), 403);
        }

        return $next($request);
    }
}
