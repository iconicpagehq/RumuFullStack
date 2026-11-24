<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RealtorController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\Admin\Billing\SubscriptionController;
use App\Http\Controllers\Admin\Billing\PaymentController;
use App\Http\Controllers\Admin\Billing\InvoiceController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WebhookController;

// Root route for API domain
Route::get('/', function () {
    if (request()->wantsJson()) {
        return response()->json([
            'status' => 'online',
            'app' => config('app.name'),
            'api_version' => 'v1',
            'frontend' => config('app.frontend_url', 'https://app.rumu.in'),
        ]);
    }

    $frontendUrl = env('FRONTEND_URL', 'https://app.rumu.in');
    return redirect()->away($frontendUrl);
});

// Checkout Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/apply-coupon', [CheckoutController::class, 'applyCoupon'])->name('checkout.apply-coupon');
    Route::post('/checkout/remove-coupon', [CheckoutController::class, 'removeCoupon'])->name('checkout.remove-coupon');
    Route::post('/checkout/verify', [CheckoutController::class, 'verify'])->name('checkout.verify');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/failure', [CheckoutController::class, 'failure'])->name('checkout.failure');
});

// MCP OAuth 2.0 (ChatGPT / remote MCP clients). Token + registration are
// machine-to-machine endpoints (CSRF-exempt in bootstrap/app.php, throttled here).
Route::get('/oauth/authorize', [\App\Http\Controllers\OAuth\AuthorizationController::class, 'show'])->name('oauth.authorize');
Route::post('/oauth/authorize', [\App\Http\Controllers\OAuth\AuthorizationController::class, 'approve'])
    ->middleware('throttle:30,1')
    ->name('oauth.authorize.approve');
Route::post('/oauth/token', [\App\Http\Controllers\OAuth\TokenController::class, 'token'])
    ->middleware('throttle:60,1')
    ->name('oauth.token');
Route::post('/oauth/register', [\App\Http\Controllers\OAuth\ClientRegistrationController::class, 'register'])
    ->middleware('throttle:30,1')
    ->name('oauth.register');

// Webhooks
Route::post('/webhooks/razorpay', [WebhookController::class, 'handleRazorpay'])->name('webhooks.razorpay');

// Legacy freelancer URLs
Route::redirect('/freelancer', '/realtor/dashboard');
Route::get('/freelancer/{path}', function (string $path) {
    return redirect('/realtor/' . $path);
})->where('path', '.*');

// Legal pages (public)
Route::view('/privacy-policy', 'legal.privacy-policy')->name('privacy-policy');
Route::view('/terms-of-service', 'legal.terms-of-service')->name('terms-of-service');
Route::view('/data-deletion', 'legal.data-deletion')->name('data-deletion');

// API docs (public)
Route::get('/api-docs', function () {
    $path = base_path('docs/REALTOR_API.md');

    if (!file_exists($path)) {
        abort(404, 'API docs not found.');
    }

    return response()->file($path, [
        'Content-Type' => 'text/markdown; charset=UTF-8',
    ]);
})->name('api-docs');

// Guest routes (login & register)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    // Social login
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
    Route::get('/auth/facebook', [AuthController::class, 'redirectToFacebookLogin'])->name('auth.facebook.redirect');
    Route::get('/auth/facebook/callback', [AuthController::class, 'handleFacebookLoginCallback'])->name('auth.facebook.callback');
});


// Meta OAuth Public Callback bridge for React SPA & local development
Route::get('/connect/facebook/callback', function (\Illuminate\Http\Request $request) {
    $frontendBase = (app()->environment('local') || str_contains($request->root(), 'ngrok') || str_contains($request->root(), 'localhost'))
        ? 'http://localhost:5173/realtor/social/accounts'
        : (config('app.frontend_url', 'https://app.rumu.in') . '/realtor/social/accounts');

    $query = $request->getQueryString() ? '?' . $request->getQueryString() : '';
    return redirect()->away($frontendBase . $query);
});

Route::get('/realtor/social/accounts', function (\Illuminate\Http\Request $request) {
    $frontendBase = 'http://localhost:5173/realtor/social/accounts';
    $query = $request->getQueryString() ? '?' . $request->getQueryString() : '';
    return redirect()->away($frontendBase . $query);
});

// Authenticated routes
Route::middleware(['auth', 'subscription.active'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Social account linking (while logged in)
    Route::get('/connect/google', [AuthController::class, 'connectGoogle'])->name('connect.google.redirect');
    Route::get('/connect/google/callback', [AuthController::class, 'connectGoogleCallback'])->name('connect.google.callback');
    Route::post('/connect/google/unlink', [AuthController::class, 'disconnectGoogle'])->name('connect.google.unlink');
    Route::get('/connect/facebook', [AuthController::class, 'connectFacebook'])->name('connect.facebook.redirect');
    // Old connect.facebook.callback replaced by Meta SPA OAuth bridge below
    Route::post('/connect/facebook/unlink', [AuthController::class, 'disconnectFacebook'])->name('connect.facebook.unlink');

    // Campaign routes (accessible by both admin and Realtor)
    Route::resource('campaigns', CampaignController::class);

    // Lead Routes
    Route::get('campaigns/{campaign}/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('campaigns/{campaign}/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::post('campaigns/{campaign}/import', [LeadController::class, 'importCsv'])->name('leads.import');
    Route::get('campaigns/{campaign}/import/sample', [LeadController::class, 'downloadSampleCsv'])->name('leads.import.sample');
    Route::get('campaigns/{campaign}/leads/import/meta', [LeadController::class, 'showMetaImport'])->name('leads.import.meta.page');
    Route::post('campaigns/{campaign}/import/meta', [LeadController::class, 'importMetaCsv'])->name('leads.import.meta');
    Route::get('campaigns/{campaign}/import/meta/sample', [LeadController::class, 'downloadMetaSampleCsv'])->name('leads.import.meta.sample');
    Route::get('campaigns/{campaign}/meta/forms', [LeadController::class, 'listMetaForms'])->name('leads.meta.forms');
    Route::post('campaigns/{campaign}/meta/sync', [LeadController::class, 'syncMetaLeads'])->name('leads.meta.sync');

    Route::get('campaigns/{campaign}/meta-integration', [App\Http\Controllers\MetaIntegrationController::class, 'show'])->name('campaigns.meta-integration');
    Route::post('campaigns/{campaign}/meta-integration/disconnect-form', [App\Http\Controllers\MetaIntegrationController::class, 'disconnectForm'])->name('campaigns.meta-integration.disconnect-form');
    Route::post('campaigns/{campaign}/meta-integration/capi', [App\Http\Controllers\MetaIntegrationController::class, 'saveCapiSettings'])->name('campaigns.meta-integration.capi');

    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
    Route::get('leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::post('leads/{lead}/notes', [LeadController::class, 'addNote'])->name('leads.notes.store');
    Route::put('leads/{lead}/status', [LeadController::class, 'updateStatus'])->name('leads.status.update');
    Route::post('leads/{lead}/meta-capi/resend', [LeadController::class, 'resendMetaCapi'])->name('leads.meta-capi.resend');

    // Inventory routes
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('projects', [App\Http\Controllers\Inventory\ProjectController::class, 'index'])->name('projects.index');
        Route::get('projects/create', [App\Http\Controllers\Inventory\ProjectController::class, 'create'])->name('projects.create');
        Route::post('projects', [App\Http\Controllers\Inventory\ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{project}', [App\Http\Controllers\Inventory\ProjectController::class, 'show'])->name('projects.show');
        Route::get('projects/{project}/edit', [App\Http\Controllers\Inventory\ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('projects/{project}', [App\Http\Controllers\Inventory\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{project}', [App\Http\Controllers\Inventory\ProjectController::class, 'destroy'])->name('projects.destroy');

        Route::post('projects/{project}/towers', [App\Http\Controllers\Inventory\TowerController::class, 'store'])->name('towers.store');
        Route::put('towers/{tower}', [App\Http\Controllers\Inventory\TowerController::class, 'update'])->name('towers.update');
        Route::delete('towers/{tower}', [App\Http\Controllers\Inventory\TowerController::class, 'destroy'])->name('towers.destroy');

        Route::post('towers/{tower}/wings', [App\Http\Controllers\Inventory\WingController::class, 'store'])->name('wings.store');
        Route::put('wings/{wing}', [App\Http\Controllers\Inventory\WingController::class, 'update'])->name('wings.update');
        Route::delete('wings/{wing}', [App\Http\Controllers\Inventory\WingController::class, 'destroy'])->name('wings.destroy');

        Route::post('towers/{tower}/floors', [App\Http\Controllers\Inventory\FloorController::class, 'storeOnTower'])->name('floors.store');
        Route::post('wings/{wing}/floors', [App\Http\Controllers\Inventory\FloorController::class, 'storeOnWing'])->name('wings.floors.store');
        Route::put('floors/{floor}', [App\Http\Controllers\Inventory\FloorController::class, 'update'])->name('floors.update');
        Route::delete('floors/{floor}', [App\Http\Controllers\Inventory\FloorController::class, 'destroy'])->name('floors.destroy');

        Route::post('floors/{floor}/units', [App\Http\Controllers\Inventory\UnitController::class, 'store'])->name('units.store');
        Route::post('floors/{floor}/units/bulk', [App\Http\Controllers\Inventory\UnitController::class, 'bulkStore'])->name('units.bulk-store');
        Route::put('units/{unit}', [App\Http\Controllers\Inventory\UnitController::class, 'update'])->name('units.update');
        Route::patch('units/{unit}/status', [App\Http\Controllers\Inventory\UnitController::class, 'updateStatus'])->name('units.update-status');
        Route::delete('units/{unit}', [App\Http\Controllers\Inventory\UnitController::class, 'destroy'])->name('units.destroy');
        Route::get('units/search', [App\Http\Controllers\Inventory\UnitController::class, 'search'])->name('units.search');
    });
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Billing Management
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/subscriptions', [\App\Http\Controllers\Admin\Analytics\SubscriptionController::class, 'index'])->name('subscriptions');
        Route::put('/subscriptions/{subscription}', [\App\Http\Controllers\Admin\Analytics\SubscriptionController::class, 'update'])->name('subscriptions.update');
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
        Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('/invoices/{invoice}/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
        Route::post('/invoices/{invoice}/resend', [InvoiceController::class, 'resend'])->name('invoices.resend');
        
        // Coupons & Referrals
        Route::resource('coupons', \App\Http\Controllers\Admin\Billing\CouponController::class);
        
        // Billing Notifications
        Route::resource('notifications/templates', \App\Http\Controllers\Admin\Billing\BillingNotificationTemplateController::class)->except(['show', 'destroy']);
        Route::get('/notifications', [\App\Http\Controllers\Admin\Billing\BillingNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/resend', [\App\Http\Controllers\Admin\Billing\BillingNotificationController::class, 'resend'])->name('notifications.resend');
    });

    // Analytics Module
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Admin\Analytics\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/revenue', [\App\Http\Controllers\Admin\Analytics\RevenueController::class, 'index'])->name('revenue');
        Route::get('/payments', [\App\Http\Controllers\Admin\Analytics\PaymentController::class, 'index'])->name('payments');
        Route::get('/reports', [\App\Http\Controllers\Admin\Analytics\ReportController::class, 'index'])->name('reports');
        Route::post('/reports/export', [\App\Http\Controllers\Admin\Analytics\ReportController::class, 'export'])->name('reports.export');
    });

    // Realtor Management
    Route::resource('realtors', App\Http\Controllers\Admin\RealtorController::class);
    Route::get('/realtors/{realtor}/billing', [SubscriptionController::class, 'customerBilling'])->name('realtors.billing');
    Route::post('/realtors/{realtor}/assign-plan', [SubscriptionController::class, 'storeCustomerPlan'])->name('realtors.assign-plan');
    Route::post('/realtors/{realtor}/suspend-subscription', [App\Http\Controllers\Admin\RealtorController::class, 'suspendSubscription'])->name('realtors.suspend-subscription');
    Route::post('/realtors/{realtor}/resume-subscription', [App\Http\Controllers\Admin\RealtorController::class, 'resumeSubscription'])->name('realtors.resume-subscription');

    // Leads Management
    Route::get('/leads', [AdminController::class, 'leads'])->name('leads.index');

    // Settings
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings.index');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::post('/settings/appearance', [AdminController::class, 'updateAppearance'])->name('settings.appearance');
    Route::post('/settings/meta-capi', [AdminController::class, 'updateMetaCapi'])->name('settings.meta-capi');

    // Users Management
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');

    // Plans Management
    Route::get('/plans', [AdminController::class, 'plans'])->name('plans.index');
    Route::post('/plans/reorder', [AdminController::class, 'reorderPlans'])->name('plans.reorder');
    Route::get('/plans/create', [AdminController::class, 'createPlan'])->name('plans.create');
    Route::get('/plans/{plan}/edit', [AdminController::class, 'editPlan'])->name('plans.edit');
    Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
    Route::put('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
    Route::patch('/plans/{plan}/toggle-status', [AdminController::class, 'togglePlanStatus'])->name('plans.toggle-status');
    
    // Plan Upgrade Requests
    Route::get('/upgrade-requests', [AdminController::class, 'upgradeRequests'])->name('upgrade_requests.index');
    Route::post('/upgrade-requests/{upgradeRequest}/approve', [AdminController::class, 'approveUpgrade'])->name('upgrade_requests.approve');
    Route::post('/upgrade-requests/{upgradeRequest}/reject', [AdminController::class, 'rejectUpgrade'])->name('upgrade_requests.reject');
    
    // Notifications
    Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [AdminController::class, 'markNotificationRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [AdminController::class, 'markAllNotificationsRead'])->name('notifications.mark-all-read');

    // Billing Settings
    Route::get('/settings/billing', [\App\Http\Controllers\Admin\BillingSettingsController::class, 'index'])->name('settings.billing');
    Route::post('/settings/billing', [\App\Http\Controllers\Admin\BillingSettingsController::class, 'update'])->name('settings.billing.update');
});

// Realtor routes
Route::middleware(['auth', 'role:realtor'])->prefix('realtor')->name('realtor.')->group(function () {
    Route::get('/dashboard', [RealtorController::class, 'dashboard'])->name('dashboard');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers');
    Route::get('/plans', [RealtorController::class, 'plans'])->name('plans');
    Route::post('/plans/upgrade', [RealtorController::class, 'requestUpgrade'])->name('plans.upgrade');
    
    // Profile routes
    Route::get('/profile', [RealtorController::class, 'profile'])->name('profile');
    Route::put('/profile', [RealtorController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [RealtorController::class, 'updatePassword'])->name('profile.password');
    
    // Settings routes
    Route::get('/settings', [RealtorController::class, 'settings'])->name('settings');
    Route::put('/settings/notifications', [RealtorController::class, 'updateNotifications'])->name('settings.notifications');
    Route::put('/settings/privacy', [RealtorController::class, 'updatePrivacy'])->name('settings.privacy');
    Route::put('/settings/appearance', [RealtorController::class, 'updateAppearance'])->name('settings.appearance');
    Route::delete('/settings/account', [RealtorController::class, 'deleteAccount'])->name('settings.account.delete');
    
    // Realtor Billing
    Route::get('/billing', [\App\Http\Controllers\Realtor\BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/invoices/{invoice}', [\App\Http\Controllers\Realtor\BillingController::class, 'showInvoice'])->name('billing.invoice.show');
    Route::get('/billing/invoices/{invoice}/download', [\App\Http\Controllers\Realtor\BillingController::class, 'downloadInvoice'])->name('billing.invoice.download');
    
    // Realtor Billing Notifications
    Route::get('/billing/notifications', [\App\Http\Controllers\Realtor\BillingNotificationController::class, 'index'])->name('billing.notifications.index');
    Route::post('/billing/notifications/{notification}/read', [\App\Http\Controllers\Realtor\BillingNotificationController::class, 'markAsRead'])->name('billing.notifications.read');
});

// Image serving route (fallback for broken storage symlink)
Route::get('/image/avatars/{filename}', [App\Http\Controllers\ImageController::class, 'serve'])
    ->name('image.avatar')
    ->where('filename', '.*');

// Meta Webhook routes (public, exempt from CSRF)
Route::get('/meta/webhook', [App\Http\Controllers\MetaWebhookController::class, 'verify']);
Route::post('/meta/webhook', [App\Http\Controllers\MetaWebhookController::class, 'handle']);

