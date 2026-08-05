<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistributionCenterController;
use App\Http\Controllers\ProductGroupController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QualityStandardController;
use App\Http\Controllers\UrgentReasonController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CertificateRequestController;
use App\Http\Controllers\DvkhRequestController;
use App\Http\Controllers\PtnRequestController;
use App\Http\Controllers\QualityCertificateController;
use App\Http\Controllers\PrintLogController;
use App\Http\Controllers\SlaConfigController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\RolePermissionController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::resource('distribution-centers', DistributionCenterController::class)
        ->only(['index'])
        ->middleware('permission:distribution_center.view');

    Route::resource('distribution-centers', DistributionCenterController::class)
        ->only(['create', 'store'])
        ->middleware('permission:distribution_center.create');

    Route::resource('distribution-centers', DistributionCenterController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:distribution_center.update');

    Route::resource('distribution-centers', DistributionCenterController::class)
        ->only(['destroy'])
        ->middleware('permission:distribution_center.delete');

    Route::get('product-groups-export', [ProductGroupController::class, 'export'])
        ->middleware('permission:product_group.export')
        ->name('product-groups.export');

    Route::post('product-groups-import', [ProductGroupController::class, 'import'])
        ->middleware('permission:product_group.import')
        ->name('product-groups.import');

    Route::get('product-groups-template', [ProductGroupController::class, 'template'])
        ->middleware('permission:product_group.import')
        ->name('product-groups.template');

    Route::resource('product-groups', ProductGroupController::class)
        ->only(['index'])
        ->middleware('permission:product_group.view');

    Route::resource('product-groups', ProductGroupController::class)
        ->only(['create', 'store'])
        ->middleware('permission:product_group.create');

    Route::resource('product-groups', ProductGroupController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:product_group.update');

    Route::resource('product-groups', ProductGroupController::class)
        ->only(['destroy'])
        ->middleware('permission:product_group.delete');

    Route::get('products-export', [ProductController::class, 'export'])
        ->middleware('permission:product.export')
        ->name('products.export');

    Route::post('products-import', [ProductController::class, 'import'])
        ->middleware('permission:product.import')
        ->name('products.import');

    Route::get('products-template', [ProductController::class, 'template'])
        ->middleware('permission:product.import')
        ->name('products.template');

    Route::resource('products', ProductController::class)
        ->only(['index'])
        ->middleware('permission:product.view');

    Route::resource('products', ProductController::class)
        ->only(['create', 'store'])
        ->middleware('permission:product.create');

    Route::resource('products', ProductController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:product.update');

    Route::resource('products', ProductController::class)
        ->only(['destroy'])
        ->middleware('permission:product.delete');

    Route::get('quality-standards-export', [QualityStandardController::class, 'export'])
        ->middleware('permission:quality_standard.export')
        ->name('quality-standards.export');

    Route::post('quality-standards-import', [QualityStandardController::class, 'import'])
        ->middleware('permission:quality_standard.import')
        ->name('quality-standards.import');

    Route::get('quality-standards-template', [QualityStandardController::class, 'template'])
        ->middleware('permission:quality_standard.import')
        ->name('quality-standards.template');

    Route::resource('quality-standards', QualityStandardController::class)
        ->only(['index'])
        ->middleware('permission:quality_standard.view');

    Route::resource('quality-standards', QualityStandardController::class)
        ->only(['create', 'store'])
        ->middleware('permission:quality_standard.create');

    Route::resource('quality-standards', QualityStandardController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:quality_standard.update');

    Route::resource('quality-standards', QualityStandardController::class)
        ->only(['destroy'])
        ->middleware('permission:quality_standard.delete');

    Route::resource('urgent-reasons', UrgentReasonController::class)
        ->only(['index'])
        ->middleware('permission:urgent_reason.view');

    Route::resource('urgent-reasons', UrgentReasonController::class)
        ->only(['create', 'store'])
        ->middleware('permission:urgent_reason.create');

    Route::resource('urgent-reasons', UrgentReasonController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:urgent_reason.update');

    Route::resource('urgent-reasons', UrgentReasonController::class)
        ->only(['destroy'])
        ->middleware('permission:urgent_reason.delete');

    Route::get('customers-export', [CustomerController::class, 'export'])
        ->middleware('permission:customer.export')
        ->name('customers.export');

    Route::post('customers-import', [CustomerController::class, 'import'])
        ->middleware('permission:customer.import')
        ->name('customers.import');

    Route::get('customers-template', [CustomerController::class, 'template'])
        ->middleware('permission:customer.import')
        ->name('customers.template');

    Route::resource('customers', CustomerController::class)
        ->only(['index'])
        ->middleware('permission:customer.view');

    Route::resource('customers', CustomerController::class)
        ->only(['create', 'store'])
        ->middleware('permission:customer.create');

    Route::resource('customers', CustomerController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:customer.update');

    Route::resource('customers', CustomerController::class)
        ->only(['destroy'])
        ->middleware('permission:customer.delete');

    Route::resource('certificate-requests', CertificateRequestController::class)
        ->only(['index'])
        ->middleware('permission:request.view');

    Route::resource('certificate-requests', CertificateRequestController::class)
        ->only(['create', 'store'])
        ->middleware('permission:request.create');

    Route::get('certificate-requests/check-invoice', [CertificateRequestController::class, 'checkInvoice'])
        ->middleware('permission:request.view|ptn.process')
        ->name('certificate-requests.check-invoice');

    Route::resource('certificate-requests', CertificateRequestController::class)
        ->only(['show'])
        ->middleware('permission:request.view');

    Route::resource('certificate-requests', CertificateRequestController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:request.update');

    Route::resource('certificate-requests', CertificateRequestController::class)
        ->only(['destroy'])
        ->middleware('permission:request.delete');

    Route::prefix('dvkh')
        ->name('dvkh.')
        ->middleware('permission:dvkh.process')
        ->group(function () {
            Route::get('requests', [DvkhRequestController::class, 'index'])
                ->name('requests.index');

            Route::get('requests/{certificateRequest}', [DvkhRequestController::class, 'show'])
                ->name('requests.show');

            Route::post('requests/{certificateRequest}/approve', [DvkhRequestController::class, 'approve'])
                ->name('requests.approve');

            Route::post('requests/{certificateRequest}/reject', [DvkhRequestController::class, 'reject'])
                ->name('requests.reject');
        });

    Route::prefix('ptn')
        ->name('ptn.')
        ->middleware('permission:ptn.process')
        ->group(function () {
            Route::get('requests', [PtnRequestController::class, 'index'])
                ->name('requests.index');

            Route::get('requests/direct-create', [PtnRequestController::class, 'directCreate'])
                ->name('requests.direct-create');

            Route::post('requests/direct-store', [PtnRequestController::class, 'directStore'])
                ->name('requests.direct-store');

            Route::get('requests/{certificateRequest}', [PtnRequestController::class, 'show'])
                ->name('requests.show');

            Route::post('requests/{certificateRequest}/receive', [PtnRequestController::class, 'receive'])
                ->name('requests.receive');

            Route::post('requests/{certificateRequest}/create-certificate', [PtnRequestController::class, 'createCertificate'])
                ->name('requests.create-certificate');
        });

    Route::get('quality-certificates', [QualityCertificateController::class, 'index'])
        ->middleware('permission:certificate.view')
        ->name('quality-certificates.index');

    Route::get('quality-certificates/{qualityCertificate}', [QualityCertificateController::class, 'show'])
        ->middleware('permission:certificate.view')
        ->name('quality-certificates.show');

    Route::get('quality-certificates/{qualityCertificate}/pdf', [QualityCertificateController::class, 'pdf'])
        ->middleware('permission:certificate.view')
        ->name('quality-certificates.pdf');

    Route::post('quality-certificates/{qualityCertificate}/sign', [QualityCertificateController::class, 'sign'])
        ->middleware('permission:certificate.sign')
        ->name('quality-certificates.sign');

    Route::post('quality-certificates/{qualityCertificate}/smartca-status', [QualityCertificateController::class, 'checkSmartCaStatus'])
        ->middleware('permission:certificate.sign')
        ->name('quality-certificates.smartca-status');

    Route::post('quality-certificates/{qualityCertificate}/print-hard-copy', [QualityCertificateController::class, 'printHardCopy'])
        ->middleware('permission:certificate.print')
        ->name('quality-certificates.print-hard-copy');

    Route::post('quality-certificates/{qualityCertificate}/resend-email', [QualityCertificateController::class, 'resendEmail'])
        ->middleware('permission:certificate.email')
        ->name('quality-certificates.resend-email');

    Route::post('quality-certificates/{qualityCertificate}/request-reissue', [QualityCertificateController::class, 'requestReissue'])
        ->middleware('permission:request.create')
        ->name('quality-certificates.request-reissue');

    Route::get('print-logs', [PrintLogController::class, 'index'])
        ->middleware('permission:certificate.print')
        ->name('print-logs.index');

    Route::get('sla-configs-export', [SlaConfigController::class, 'export'])
        ->middleware('permission:sla.export')
        ->name('sla-configs.export');

    Route::post('sla-configs-import', [SlaConfigController::class, 'import'])
        ->middleware('permission:sla.import')
        ->name('sla-configs.import');

    Route::get('sla-configs-template', [SlaConfigController::class, 'template'])
        ->middleware('permission:sla.import')
        ->name('sla-configs.template');

    Route::resource('sla-configs', SlaConfigController::class)
        ->only(['index'])
        ->middleware('permission:sla.view');

    Route::resource('sla-configs', SlaConfigController::class)
        ->only(['create', 'store'])
        ->middleware('permission:sla.create');

    Route::resource('sla-configs', SlaConfigController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:sla.update');

    Route::resource('sla-configs', SlaConfigController::class)
        ->only(['destroy'])
        ->middleware('permission:sla.delete');

    Route::get('reports/summary', [ReportController::class, 'summary'])
        ->middleware('permission:report.view')
        ->name('reports.summary');

    Route::get('reports/summary/export', [ReportController::class, 'exportSummary'])
        ->middleware('permission:report.export')
        ->name('reports.summary.export');

    Route::get('system-settings', [SystemSettingController::class, 'index'])
        ->middleware('permission:setting.view')
        ->name('system-settings.index');

    Route::post('system-settings', [SystemSettingController::class, 'update'])
        ->middleware('permission:setting.update')
        ->name('system-settings.update');

    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:user.reset_password')
        ->name('users.reset-password');

    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('permission:user.toggle_active')
        ->name('users.toggle-active');

    Route::resource('users', UserController::class)
        ->only(['index'])
        ->middleware('permission:user.view');

    Route::resource('users', UserController::class)
        ->only(['create', 'store'])
        ->middleware('permission:user.create');

    Route::resource('users', UserController::class)
        ->only(['show'])
        ->middleware('permission:user.view');

    Route::resource('users', UserController::class)
        ->only(['edit', 'update'])
        ->middleware('permission:user.update');

    Route::resource('users', UserController::class)
        ->only(['destroy'])
        ->middleware('permission:user.delete');

    Route::get('role-permissions', [RolePermissionController::class, 'index'])
        ->middleware('permission:role_permission.manage')
        ->name('role-permissions.index');

    Route::put('role-permissions/{role}', [RolePermissionController::class, 'update'])
        ->middleware('permission:role_permission.manage')
        ->name('role-permissions.update');

    // nhật ký hoạt động
    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:log.view')
        ->name('activity-logs.index');

    Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('permission:log.view')
        ->name('activity-logs.show');

    // nhật ký đăng nhập
    Route::get('login-logs', [LoginLogController::class, 'index'])
        ->middleware('permission:log.view')
        ->name('login-logs.index');
});

require __DIR__ . '/auth.php';
