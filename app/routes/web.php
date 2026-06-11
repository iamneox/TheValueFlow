<?php

use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CreativeController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PostbackController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\TrackingDomainController;
use App\Http\Controllers\TrackingLinkController;
use App\Http\Controllers\TrafficSourceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::get('/signup/partner', [SignupController::class, 'partnerForm'])->name('signup.partner');
Route::post('/signup/partner', [SignupController::class, 'partnerStore']);
Route::get('/signup/client', [SignupController::class, 'clientForm'])->name('signup.client');
Route::post('/signup/client', [SignupController::class, 'clientStore']);

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:super_admin,affiliate_manager,sales_manager')->group(function () {
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
        Route::post('/clients', [ClientController::class, 'store']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);

        Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
        Route::post('/partners', [PartnerController::class, 'store']);
        Route::put('/partners/{partner}', [PartnerController::class, 'update']);
        Route::delete('/partners/{partner}', [PartnerController::class, 'destroy']);

        Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
        Route::post('/offers', [OfferController::class, 'store']);
        Route::put('/offers/{offer}', [OfferController::class, 'update']);
        Route::delete('/offers/{offer}', [OfferController::class, 'destroy']);

        Route::get('/tracking-domains', [TrackingDomainController::class, 'index'])->name('tracking-domains.index');
        Route::post('/tracking-domains', [TrackingDomainController::class, 'store']);
        Route::put('/tracking-domains/{trackingDomain}', [TrackingDomainController::class, 'update']);
        Route::post('/tracking-domains/{trackingDomain}/check', [TrackingDomainController::class, 'check'])->name('tracking-domains.check');
        Route::post('/tracking-domains/{trackingDomain}/reprovision', [TrackingDomainController::class, 'reprovision'])->name('tracking-domains.reprovision');
        Route::delete('/tracking-domains/{trackingDomain}', [TrackingDomainController::class, 'destroy']);

        Route::get('/tracking-links', [TrackingLinkController::class, 'index'])->name('tracking-links.index');
        Route::post('/tracking-links', [TrackingLinkController::class, 'store']);

        Route::get('/creatives', [CreativeController::class, 'index'])->name('creatives.index');
        Route::post('/creatives', [CreativeController::class, 'store']);
        Route::post('/creatives/{creative}/kit', [CreativeController::class, 'downloadKit'])->name('creatives.kit');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/sync', [ReportController::class, 'sync'])->name('reports.sync');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('/adjustments', [AdjustmentController::class, 'store']);
        Route::put('/adjustments/{adjustment}', [AdjustmentController::class, 'update']);

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoice}/recalculate', [InvoiceController::class, 'recalculate'])->name('invoices.recalculate');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::put('/invoices/{invoice}/lines/{line}', [InvoiceController::class, 'updateLine']);
        Route::post('/invoices/{invoice}/lines', [InvoiceController::class, 'addLine']);
        Route::delete('/invoices/{invoice}/lines/{line}', [InvoiceController::class, 'destroyLine']);
        Route::post('/invoices/{invoice}/sent', [InvoiceController::class, 'markSent']);
        Route::post('/invoices/{invoice}/paid', [InvoiceController::class, 'markPaid']);

        Route::get('/postbacks', [PostbackController::class, 'index'])->name('postbacks.index');
        Route::post('/postbacks', [PostbackController::class, 'store']);
        Route::put('/postbacks/{postback}', [PostbackController::class, 'update']);
        Route::delete('/postbacks/{postback}', [PostbackController::class, 'destroy']);

        Route::get('/traffic-sources', [TrafficSourceController::class, 'index'])->name('traffic-sources.index');
        Route::post('/traffic-sources', [TrafficSourceController::class, 'store']);
        Route::put('/traffic-sources/{trafficSource}', [TrafficSourceController::class, 'update']);
        Route::delete('/traffic-sources/{trafficSource}', [TrafficSourceController::class, 'destroy']);

        Route::get('/custom-settings', [CustomSettingsController::class, 'index'])->name('custom-settings.index');
        Route::post('/custom-settings/payouts', [CustomSettingsController::class, 'storePayout']);
        Route::post('/custom-settings/caps', [CustomSettingsController::class, 'storeCap']);
        Route::post('/custom-settings/access', [CustomSettingsController::class, 'storeAccess']);

        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [DocumentController::class, 'store']);
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        Route::get('/signup/admin', [SignupController::class, 'adminIndex'])->name('signup.admin');
        Route::post('/signup/partner/{signupRequest}/approve', [SignupController::class, 'approvePartner']);
        Route::post('/signup/client/{signupRequest}/approve', [SignupController::class, 'approveClient']);
    });

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
    });

    Route::middleware('role:partner')->group(function () {
        Route::get('/portal/partner', [PortalController::class, 'partnerDashboard'])->name('portal.partner');
    });

    Route::middleware('role:client')->group(function () {
        Route::get('/portal/client', [PortalController::class, 'clientDashboard'])->name('portal.client');
    });
});
