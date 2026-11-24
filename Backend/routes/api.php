<?php

use App\Http\Controllers\Api\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Api\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Api\Admin\Billing\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\Billing\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Api\Admin\Billing\NotificationController as AdminBillingNotificationController;
use App\Http\Controllers\Api\Admin\Billing\NotificationTemplateController as AdminNotificationTemplateController;
use App\Http\Controllers\Api\Admin\Billing\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\Billing\ReferralController as AdminReferralController;
use App\Http\Controllers\Api\Admin\Billing\SettingsController as AdminBillingSettingsController;
use App\Http\Controllers\Api\Admin\Billing\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\BroadcastController as AdminBroadcastController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\EnterpriseController as AdminEnterpriseController;
use App\Http\Controllers\Api\Admin\LeadsController as AdminLeadsController;
use App\Http\Controllers\Api\Admin\NotificationsController as AdminNotificationsController;
use App\Http\Controllers\Api\Admin\PlansController as AdminPlansController;
use App\Http\Controllers\Api\Admin\RealtorController as AdminRealtorController;
use App\Http\Controllers\Api\Admin\SeatRequestsController as AdminSeatRequestsController;
use App\Http\Controllers\Api\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Api\Admin\UpgradeRequestsController as AdminUpgradeRequestsController;
use App\Http\Controllers\Api\Admin\UsersController as AdminUsersController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Calls\TelephonyWebhookController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\Enterprise\CallController as EnterpriseCallController;
use App\Http\Controllers\Api\Enterprise\EnterpriseAuditLogController;
use App\Http\Controllers\Api\Enterprise\EnterpriseBillingController;
use App\Http\Controllers\Api\Enterprise\EnterpriseCampaignController;
use App\Http\Controllers\Api\Enterprise\EnterpriseCustomerController;
use App\Http\Controllers\Api\Enterprise\EnterpriseDashboardController;
use App\Http\Controllers\Api\Enterprise\EnterpriseHierarchyController;
use App\Http\Controllers\Api\Enterprise\EnterpriseInventoryController;
use App\Http\Controllers\Api\Enterprise\EnterpriseInvitationAcceptController;
use App\Http\Controllers\Api\Enterprise\EnterpriseInvitationController;
use App\Http\Controllers\Api\Enterprise\EnterpriseLeadController;
use App\Http\Controllers\Api\Enterprise\EnterpriseMetaController;
use App\Http\Controllers\Api\Enterprise\EnterpriseNotificationController;
use App\Http\Controllers\Api\Enterprise\EnterpriseReferenceDataController;
use App\Http\Controllers\Api\Enterprise\EnterpriseRegistrationController;
use App\Http\Controllers\Api\Enterprise\EnterpriseReportController;
use App\Http\Controllers\Api\Enterprise\EnterpriseSettingsController;
use App\Http\Controllers\Api\Enterprise\EnterpriseSiteVisitController;
use App\Http\Controllers\Api\Enterprise\EnterpriseTargetController;
use App\Http\Controllers\Api\Enterprise\EnterpriseTaskController;
use App\Http\Controllers\Api\Enterprise\EnterpriseTeamController;
use App\Http\Controllers\Api\Enterprise\EnterpriseUserController;
use App\Http\Controllers\Api\Mobile\MobileBootstrapController;
use App\Http\Controllers\Api\Mobile\MobileDeviceController;
use App\Http\Controllers\Api\Realtor\BillingController as RealtorBillingController;
use App\Http\Controllers\Api\Realtor\BillingNotificationController as RealtorBillingNotificationController;
use App\Http\Controllers\Api\Realtor\CallController as RealtorCallController;
use App\Http\Controllers\Api\Realtor\CampaignController;
use App\Http\Controllers\Api\Realtor\CustomerController;
use App\Http\Controllers\Api\Realtor\DashboardController;
use App\Http\Controllers\Api\Realtor\InventoryController;
use App\Http\Controllers\Api\Realtor\LeadController;
use App\Http\Controllers\Api\Realtor\LeadImportController;
use App\Http\Controllers\Api\Realtor\MetaConnectionController;
use App\Http\Controllers\Api\Realtor\MetaIntegrationController;
use App\Http\Controllers\Api\Realtor\PlanController;
use App\Http\Controllers\Api\Realtor\ProfileController;
use App\Http\Controllers\Api\Realtor\SettingsController as RealtorSettingsController;
use App\Http\Controllers\Api\Realtor\SiteVisitController as RealtorSiteVisitController;
use App\Http\Controllers\Api\Realtor\TaskController as RealtorTaskController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\Social\SocialAccountController;
use App\Http\Controllers\Api\Social\SocialOAuthController;
use App\Http\Controllers\Api\Social\SocialPostController;
use App\Http\Controllers\Api\Social\SocialCommentController;
use App\Http\Controllers\Api\Social\SocialInboxController;
use App\Http\Controllers\Api\Social\SocialMentionController;
use App\Http\Controllers\Api\Social\SocialAnalyticsController;
use App\Http\Controllers\Api\Social\SocialWebhookController;
use App\Http\Controllers\Api\WhatsApp\WhatsAppAccountController;
use App\Http\Controllers\Api\WhatsApp\WhatsAppConversationController;
use App\Http\Controllers\Api\WhatsApp\WhatsAppMessageController;
use App\Http\Controllers\Api\WhatsApp\WhatsAppTemplateController;
use App\Http\Controllers\Api\WhatsApp\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// WhatsApp Automation Module Routes (/api/whatsapp/...)
// =========================================================================
Route::prefix('whatsapp')->group(function () {
    // Public Meta Webhook Verification & Ingestion
    Route::get('webhook', [WhatsAppWebhookController::class, 'verify']);
    Route::post('webhook', [WhatsAppWebhookController::class, 'handle']);

    // Multi-tenant Protected WhatsApp Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('messages/send', [WhatsAppMessageController::class, 'send']);
        Route::get('conversations', [WhatsAppConversationController::class, 'index']);
        Route::get('conversations/{id}/messages', [WhatsAppConversationController::class, 'messages']);
        Route::get('templates', [WhatsAppTemplateController::class, 'index']);
        Route::get('accounts', [WhatsAppAccountController::class, 'index']);
    });
});

Route::prefix('v1')->group(function () {
    // Also available via /api/v1/whatsapp/...
    Route::prefix('whatsapp')->group(function () {
        Route::get('webhook', [WhatsAppWebhookController::class, 'verify']);
        Route::post('webhook', [WhatsAppWebhookController::class, 'handle']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('messages/send', [WhatsAppMessageController::class, 'send']);
            Route::get('conversations', [WhatsAppConversationController::class, 'index']);
            Route::get('conversations/{id}/messages', [WhatsAppConversationController::class, 'messages']);
            Route::get('templates', [WhatsAppTemplateController::class, 'index']);
            Route::get('accounts', [WhatsAppAccountController::class, 'index']);
        });
    });
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password/send-otp', [AuthController::class, 'sendPasswordResetOtp'])
            ->middleware('throttle:6,1');
        Route::post('forgot-password/verify-otp', [AuthController::class, 'verifyPasswordResetOtp'])
            ->middleware('throttle:10,1');
        Route::post('forgot-password/reset', [AuthController::class, 'resetPasswordWithOtp'])
            ->middleware('throttle:6,1');
        Route::get('social/{provider}/url', [SocialAuthController::class, 'redirectUrl']);
        Route::post('social/{provider}', [SocialAuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
            Route::post('social/{provider}/link', [SocialAuthController::class, 'link']);
            Route::delete('social/{provider}/unlink', [SocialAuthController::class, 'unlink']);
        });
    });

    Route::get('checkout/plans', [CheckoutController::class, 'plans']);

    Route::post('webhooks/telephony/{provider}', TelephonyWebhookController::class)
        ->middleware('throttle:120,1');

    // Meta Webhooks & Compliance (Public)
    Route::get('social/meta/webhook', [SocialWebhookController::class, 'verify']);
    Route::post('social/meta/webhook', [SocialWebhookController::class, 'handle']);
    Route::post('social/meta/data-deletion', [\App\Http\Controllers\Api\Social\SocialOAuthController::class, 'dataDeletionCallback']);

    // Social Media Integration (Multi-tenant Facebook & Instagram)
    Route::middleware('auth:sanctum')->prefix('social')->group(function () {
        // Accounts & Connection
        Route::get('accounts', [SocialAccountController::class, 'index']);
        Route::delete('accounts/{id}', [SocialAccountController::class, 'destroy']);
        Route::post('accounts/{id}/sync', [SocialAccountController::class, 'sync']);

        // OAuth Connect & Callback
        Route::get('connect/meta', [SocialOAuthController::class, 'connect']);
        Route::get('connect/meta/callback', [SocialOAuthController::class, 'callback']);

        // Posts
        Route::get('posts', [SocialPostController::class, 'index']);
        Route::get('posts/{id}', [SocialPostController::class, 'show']);

        // Comments & Moderation
        Route::get('posts/{id}/comments', [SocialCommentController::class, 'index']);
        Route::post('posts/{id}/sync-comments', [SocialCommentController::class, 'sync']);
        Route::post('comments/{id}/reply', [SocialCommentController::class, 'reply']);
        Route::patch('comments/{id}', [SocialCommentController::class, 'update']);
        Route::delete('comments/{id}', [SocialCommentController::class, 'destroy']);

        // Inbox & Conversations
        Route::get('inbox/conversations', [SocialInboxController::class, 'conversations']);
        Route::get('inbox/conversations/{id}/messages', [SocialInboxController::class, 'messages']);
        Route::post('inbox/conversations/{id}/messages', [SocialInboxController::class, 'sendMessage']);
        Route::patch('inbox/conversations/{id}', [SocialInboxController::class, 'update']);

        // Mentions
        Route::get('mentions', [SocialMentionController::class, 'index']);

        // Analytics
        Route::get('analytics/overview', [SocialAnalyticsController::class, 'overview']);
        Route::get('analytics/posts/{id}', [SocialAnalyticsController::class, 'postAnalytics']);
    });

    Route::middleware('auth:sanctum')->prefix('mobile')->group(function () {
        Route::get('bootstrap', [MobileBootstrapController::class, 'show'])
            ->middleware('throttle:120,1');
        Route::post('devices', [MobileDeviceController::class, 'store'])
            ->middleware('throttle:120,1');
    });

    Route::middleware('auth:sanctum')->prefix('checkout')->group(function () {
        Route::get('summary', [CheckoutController::class, 'summary']);
        Route::post('coupon', [CheckoutController::class, 'applyCoupon']);
        Route::delete('coupon', [CheckoutController::class, 'removeCoupon']);
        Route::post('order', [CheckoutController::class, 'createOrder']);
        Route::post('verify', [CheckoutController::class, 'verify']);
    });

    Route::middleware(['auth:sanctum', 'admin.api'])
        ->prefix('admin')
        ->group(function () {
            Route::get('dashboard', [AdminDashboardController::class, 'index']);

            Route::get('leads', [AdminLeadsController::class, 'index']);
            Route::get('users', [AdminUsersController::class, 'index']);
            Route::get('users/{user}', [AdminUsersController::class, 'show']);

            Route::get('plans', [AdminPlansController::class, 'index']);
            Route::post('plans', [AdminPlansController::class, 'store']);
            Route::put('plans/{plan}', [AdminPlansController::class, 'update']);
            Route::post('plans/reorder', [AdminPlansController::class, 'reorder']);
            Route::patch('plans/{plan}/toggle-status', [AdminPlansController::class, 'toggleStatus']);

            Route::get('upgrade-requests', [AdminUpgradeRequestsController::class, 'index']);
            Route::post('upgrade-requests/{upgradeRequest}/approve', [AdminUpgradeRequestsController::class, 'approve']);
            Route::post('upgrade-requests/{upgradeRequest}/reject', [AdminUpgradeRequestsController::class, 'reject']);

            Route::get('enterprises', [AdminEnterpriseController::class, 'index']);
            Route::get('enterprises/{enterprise}', [AdminEnterpriseController::class, 'show']);
            Route::post('enterprises/{enterprise}/suspend', [AdminEnterpriseController::class, 'suspend']);
            Route::post('enterprises/{enterprise}/resume', [AdminEnterpriseController::class, 'resume']);

            Route::get('seat-requests', [AdminSeatRequestsController::class, 'index']);
            Route::post('seat-requests/{seatRequest}/approve', [AdminSeatRequestsController::class, 'approve']);
            Route::post('seat-requests/{seatRequest}/reject', [AdminSeatRequestsController::class, 'reject']);

            Route::get('broadcasts', [AdminBroadcastController::class, 'index']);
            Route::post('broadcasts/preview', [AdminBroadcastController::class, 'preview']);
            Route::post('broadcasts', [AdminBroadcastController::class, 'store']);

            Route::get('audit-logs', [AdminAuditLogController::class, 'index']);

            Route::get('notifications', [AdminNotificationsController::class, 'index']);
            Route::get('notifications/unread-count', [AdminNotificationsController::class, 'unreadCount']);
            Route::patch('notifications/{notification}/read', [AdminNotificationsController::class, 'markRead']);
            Route::post('notifications/mark-all-read', [AdminNotificationsController::class, 'markAllRead']);

            Route::get('settings', [AdminSettingsController::class, 'index']);
            Route::put('settings', [AdminSettingsController::class, 'updateSettings']);
            Route::put('settings/appearance', [AdminSettingsController::class, 'updateAppearance']);
            Route::put('settings/meta-capi', [AdminSettingsController::class, 'updateMetaCapi']);

            Route::prefix('billing')->group(function () {
                Route::get('subscriptions', [AdminSubscriptionController::class, 'index']);
                Route::get('subscriptions/{subscription}', [AdminSubscriptionController::class, 'show']);
                Route::put('subscriptions/{subscription}', [AdminSubscriptionController::class, 'update']);
                Route::post('subscriptions/{subscription}/extend', [AdminSubscriptionController::class, 'extend']);
                Route::post('subscriptions/{subscription}/cancel', [AdminSubscriptionController::class, 'cancel']);

                Route::get('payments', [AdminPaymentController::class, 'index']);
                Route::get('payments/{payment}', [AdminPaymentController::class, 'show']);

                Route::get('invoices', [AdminInvoiceController::class, 'index']);
                Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show']);
                Route::get('invoices/{invoice}/download', [AdminInvoiceController::class, 'download']);
                Route::post('invoices/{invoice}/resend', [AdminInvoiceController::class, 'resend']);

                Route::get('coupons', [AdminCouponController::class, 'index']);
                Route::post('coupons', [AdminCouponController::class, 'store']);
                Route::get('coupons/{coupon}', [AdminCouponController::class, 'show']);
                Route::put('coupons/{coupon}', [AdminCouponController::class, 'update']);
                Route::delete('coupons/{coupon}', [AdminCouponController::class, 'destroy']);

                Route::get('referrals', [AdminReferralController::class, 'index']);

                Route::get('notification-templates', [AdminNotificationTemplateController::class, 'index']);
                Route::post('notification-templates', [AdminNotificationTemplateController::class, 'store']);
                Route::get('notification-templates/{template}', [AdminNotificationTemplateController::class, 'show']);
                Route::put('notification-templates/{template}', [AdminNotificationTemplateController::class, 'update']);
                Route::delete('notification-templates/{template}', [AdminNotificationTemplateController::class, 'destroy']);

                Route::get('notifications', [AdminBillingNotificationController::class, 'index']);
                Route::post('notifications/{notification}/resend', [AdminBillingNotificationController::class, 'resend']);

                Route::get('settings', [AdminBillingSettingsController::class, 'index']);
                Route::post('settings', [AdminBillingSettingsController::class, 'update']);
            });

            Route::prefix('analytics')->group(function () {
                Route::get('dashboard', [AdminAnalyticsController::class, 'dashboard']);
                Route::get('revenue', [AdminAnalyticsController::class, 'revenue']);
                Route::get('payments', [AdminAnalyticsController::class, 'payments']);
                Route::get('customers', [AdminAnalyticsController::class, 'customers']);
                Route::get('enterprises', [AdminAnalyticsController::class, 'enterprises']);
                Route::get('reports', [AdminAnalyticsController::class, 'reports']);
                Route::post('reports/export', [AdminAnalyticsController::class, 'export']);
            });

            Route::get('realtors', [AdminRealtorController::class, 'index']);
            Route::post('realtors', [AdminRealtorController::class, 'store']);
            Route::get('realtors/{realtor}', [AdminRealtorController::class, 'show']);
            Route::put('realtors/{realtor}', [AdminRealtorController::class, 'update']);
            Route::delete('realtors/{realtor}', [AdminRealtorController::class, 'destroy']);
            Route::get('realtors/{realtor}/billing', [AdminRealtorController::class, 'billing']);
            Route::post('realtors/{realtor}/assign-plan', [AdminRealtorController::class, 'assignPlan']);
            Route::post('realtors/{realtor}/suspend', [AdminRealtorController::class, 'suspend']);
            Route::post('realtors/{realtor}/resume', [AdminRealtorController::class, 'resume']);
        });

    Route::middleware(['auth:sanctum', 'realtor.api'])
        ->prefix('realtor')
        ->group(function () {
            // Account management remains available even when a subscription is suspended.
            Route::get('profile', [ProfileController::class, 'show']);
            Route::put('profile', [ProfileController::class, 'update']);
            Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
            Route::put('profile/password', [ProfileController::class, 'updatePassword']);

            Route::get('settings', [RealtorSettingsController::class, 'index']);
            Route::put('settings/notifications', [RealtorSettingsController::class, 'updateNotifications']);
            Route::put('settings/privacy', [RealtorSettingsController::class, 'updatePrivacy']);
            Route::put('settings/appearance', [RealtorSettingsController::class, 'updateAppearance']);
            Route::delete('settings/account', [RealtorSettingsController::class, 'deleteAccount']);

            Route::get('billing', [RealtorBillingController::class, 'index']);
            Route::get('billing/invoices/{id}', [RealtorBillingController::class, 'showInvoice']);
            Route::get('billing/invoices/{id}/download', [RealtorBillingController::class, 'downloadInvoice']);

            Route::get('billing/notifications', [RealtorBillingNotificationController::class, 'index']);
            Route::get('billing/notifications/unread-count', [RealtorBillingNotificationController::class, 'unreadCount']);
            Route::post('billing/notifications/{notification}/read', [RealtorBillingNotificationController::class, 'markAsRead']);
            Route::post('billing/notifications/mark-all-read', [RealtorBillingNotificationController::class, 'markAllRead']);

            Route::get('meta/pages', [MetaConnectionController::class, 'pages']);
            Route::get('meta/connect-url', [MetaConnectionController::class, 'connectUrl']);
            Route::post('meta/connect', [MetaConnectionController::class, 'connect']);
            Route::post('meta/disconnect', [MetaConnectionController::class, 'disconnect']);

            Route::get('plans', [PlanController::class, 'index']);
            Route::post('plans/upgrade', [PlanController::class, 'requestUpgrade']);

            Route::middleware('subscription.active.api')->group(function () {
                Route::get('dashboard', [DashboardController::class, 'index']);

                Route::prefix('calls')->group(function () {
                    Route::get('/', [RealtorCallController::class, 'index']);
                    Route::get('summary', [RealtorCallController::class, 'summary']);
                    Route::post('initiate', [RealtorCallController::class, 'initiate']);
                    Route::post('app', [RealtorCallController::class, 'appCall'])
                        ->middleware('throttle:120,1');
                    Route::post('import', [RealtorCallController::class, 'importRecording']);
                    Route::get('profile', [RealtorCallController::class, 'profile']);
                    Route::put('profile', [RealtorCallController::class, 'updateProfile']);
                    Route::get('{call}', [RealtorCallController::class, 'show'])->whereNumber('call');
                    Route::patch('{call}/disposition', [RealtorCallController::class, 'updateDisposition'])->whereNumber('call');
                    Route::post('{call}/recording', [RealtorCallController::class, 'uploadAppRecording'])
                        ->whereNumber('call')
                        ->middleware('throttle:120,1');
                    Route::get('{call}/recording', [RealtorCallController::class, 'recording'])->whereNumber('call');
                    Route::delete('{call}/recording', [RealtorCallController::class, 'deleteRecording'])->whereNumber('call');
                });

                Route::get('campaigns', [CampaignController::class, 'index']);
                Route::post('campaigns', [CampaignController::class, 'store']);
                Route::get('campaigns/{campaign}', [CampaignController::class, 'show']);
                Route::put('campaigns/{campaign}', [CampaignController::class, 'update']);
                Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy']);

                Route::get('campaigns/{campaign}/leads', [LeadController::class, 'index']);
                Route::post('campaigns/{campaign}/leads', [LeadController::class, 'store']);
                Route::post('campaigns/{campaign}/import/csv', [LeadImportController::class, 'importCsv']);
                Route::get('campaigns/{campaign}/import/sample', [LeadImportController::class, 'downloadSampleCsv']);
                Route::post('campaigns/{campaign}/import/meta/csv', [LeadImportController::class, 'importMetaCsv']);
                Route::get('campaigns/{campaign}/import/meta/sample', [LeadImportController::class, 'downloadMetaSampleCsv']);
                Route::get('campaigns/{campaign}/meta/forms', [LeadImportController::class, 'listMetaForms']);
                Route::post('campaigns/{campaign}/meta/sync', [LeadImportController::class, 'syncMetaLeads']);

                Route::get('campaigns/{campaign}/meta-integration', [MetaIntegrationController::class, 'show']);
                Route::post('campaigns/{campaign}/meta-integration/disconnect-form', [MetaIntegrationController::class, 'disconnectForm']);
                Route::post('campaigns/{campaign}/meta-integration/capi', [MetaIntegrationController::class, 'saveCapiSettings']);

                Route::get('leads', [LeadController::class, 'all']);
                Route::get('leads/export', [LeadController::class, 'export']);
                Route::get('leads/{lead}', [LeadController::class, 'show']);
                Route::put('leads/{lead}', [LeadController::class, 'update']);
                Route::delete('leads/{lead}', [LeadController::class, 'destroy']);
                Route::patch('leads/{lead}/status', [LeadController::class, 'updateStatus']);
                Route::post('leads/{lead}/notes', [LeadController::class, 'addNote']);
                Route::post('leads/{lead}/meta-capi/resend', [LeadController::class, 'resendMetaCapi']);

                Route::get('tasks', [RealtorTaskController::class, 'index']);
                Route::post('tasks', [RealtorTaskController::class, 'store']);
                Route::get('tasks/{task}', [RealtorTaskController::class, 'show']);
                Route::put('tasks/{task}', [RealtorTaskController::class, 'update']);
                Route::patch('tasks/{task}/status', [RealtorTaskController::class, 'updateStatus']);
                Route::delete('tasks/{task}', [RealtorTaskController::class, 'destroy']);

                Route::get('site-visits', [RealtorSiteVisitController::class, 'index']);
                Route::post('site-visits', [RealtorSiteVisitController::class, 'store']);
                Route::get('site-visits/{siteVisit}', [RealtorSiteVisitController::class, 'show']);
                Route::put('site-visits/{siteVisit}', [RealtorSiteVisitController::class, 'update']);
                Route::patch('site-visits/{siteVisit}/status', [RealtorSiteVisitController::class, 'updateStatus']);
                Route::delete('site-visits/{siteVisit}', [RealtorSiteVisitController::class, 'destroy']);

                Route::get('customers', [CustomerController::class, 'index']);

                Route::get('inventory/projects', [InventoryController::class, 'projects']);
                Route::post('inventory/projects', [InventoryController::class, 'storeProject']);
                Route::get('inventory/projects/{project}', [InventoryController::class, 'showProject']);
                Route::put('inventory/projects/{project}', [InventoryController::class, 'updateProject']);
                Route::delete('inventory/projects/{project}', [InventoryController::class, 'destroyProject']);

                Route::post('inventory/projects/{project}/towers', [InventoryController::class, 'storeTower']);
                Route::put('inventory/towers/{tower}', [InventoryController::class, 'updateTower']);
                Route::delete('inventory/towers/{tower}', [InventoryController::class, 'destroyTower']);

                Route::post('inventory/towers/{tower}/wings', [InventoryController::class, 'storeWing']);
                Route::put('inventory/wings/{wing}', [InventoryController::class, 'updateWing']);
                Route::delete('inventory/wings/{wing}', [InventoryController::class, 'destroyWing']);

                Route::post('inventory/towers/{tower}/floors', [InventoryController::class, 'storeFloorOnTower']);
                Route::post('inventory/wings/{wing}/floors', [InventoryController::class, 'storeFloorOnWing']);
                Route::put('inventory/floors/{floor}', [InventoryController::class, 'updateFloor']);
                Route::delete('inventory/floors/{floor}', [InventoryController::class, 'destroyFloor']);

                Route::get('inventory/floors/{floor}/units', [InventoryController::class, 'floorUnits']);
                Route::post('inventory/floors/{floor}/units', [InventoryController::class, 'storeUnit']);
                Route::post('inventory/floors/{floor}/units/bulk', [InventoryController::class, 'bulkStoreUnits']);
                Route::put('inventory/units/{unit}', [InventoryController::class, 'updateUnit']);
                Route::patch('inventory/units/{unit}/status', [InventoryController::class, 'updateUnitStatus']);
                Route::patch('inventory/units/{unit}/assign-lead', [InventoryController::class, 'assignUnitLead']);
                Route::delete('inventory/units/{unit}', [InventoryController::class, 'destroyUnit']);
                Route::get('inventory/units/search', [InventoryController::class, 'searchUnits']);
            });
        });

    Route::prefix('enterprise')->group(function () {
        Route::get('plans', [EnterpriseRegistrationController::class, 'plans']);
        Route::post('register', [EnterpriseRegistrationController::class, 'register']);

        Route::get('invitations/{token}', [EnterpriseInvitationAcceptController::class, 'show']);
        Route::post('invitations/{token}/accept', [EnterpriseInvitationAcceptController::class, 'accept']);

        Route::middleware(['auth:sanctum', 'enterprise.api'])->group(function () {
            Route::get('me', [EnterpriseSettingsController::class, 'context']);
            Route::get('dashboard', [EnterpriseDashboardController::class, 'index']);
            Route::get('reference-data', [EnterpriseReferenceDataController::class, 'index']);

            Route::prefix('calls')->group(function () {
                Route::get('/', [EnterpriseCallController::class, 'index']);
                Route::get('summary', [EnterpriseCallController::class, 'summary']);
                Route::post('initiate', [EnterpriseCallController::class, 'initiate']);
                Route::post('app', [EnterpriseCallController::class, 'appCall'])
                    ->middleware('throttle:120,1');
                Route::post('import', [EnterpriseCallController::class, 'importRecording']);
                Route::get('profile', [EnterpriseCallController::class, 'profile']);
                Route::put('profile', [EnterpriseCallController::class, 'updateProfile']);
                Route::get('{call}', [EnterpriseCallController::class, 'show'])->whereNumber('call');
                Route::patch('{call}/disposition', [EnterpriseCallController::class, 'updateDisposition'])->whereNumber('call');
                Route::post('{call}/recording', [EnterpriseCallController::class, 'uploadAppRecording'])
                    ->whereNumber('call')
                    ->middleware('throttle:120,1');
                Route::get('{call}/recording', [EnterpriseCallController::class, 'recording'])->whereNumber('call');
                Route::delete('{call}/recording', [EnterpriseCallController::class, 'deleteRecording'])->whereNumber('call');
            });

            Route::get('leads', [EnterpriseLeadController::class, 'index']);
            Route::middleware('enterprise.role:admin,manager')->group(function () {
                Route::post('leads/import', [EnterpriseLeadController::class, 'importCsv']);
                Route::get('leads/import/sample', [EnterpriseLeadController::class, 'downloadSampleCsv']);
            });
            Route::post('leads', [EnterpriseLeadController::class, 'store']);
            Route::get('leads/{lead}', [EnterpriseLeadController::class, 'show']);
            Route::put('leads/{lead}', [EnterpriseLeadController::class, 'update']);
            Route::delete('leads/{lead}', [EnterpriseLeadController::class, 'destroy']);
            Route::patch('leads/{lead}/stage', [EnterpriseLeadController::class, 'updateStage']);
            Route::post('leads/{lead}/notes', [EnterpriseLeadController::class, 'addNote']);
            Route::middleware('enterprise.role:admin,manager')->group(function () {
                Route::post('leads/distribute', [EnterpriseLeadController::class, 'distribute']);
                Route::post('leads/{lead}/round-robin', [EnterpriseLeadController::class, 'roundRobin']);
            });
            Route::post('leads/{lead}/assign', [EnterpriseLeadController::class, 'assign']);

            Route::get('users', [EnterpriseUserController::class, 'index']);
            Route::get('users/{membership}', [EnterpriseUserController::class, 'show']);
            Route::get('users/{membership}/history', [EnterpriseUserController::class, 'history']);
            Route::get('users/{membership}/reports', [EnterpriseHierarchyController::class, 'reports']);
            Route::get('organization/tree', [EnterpriseHierarchyController::class, 'tree']);
            Route::post('users/{membership}/transfer', [EnterpriseUserController::class, 'transfer'])
                ->middleware('enterprise.role:admin,manager');
            Route::middleware('enterprise.role:admin')->group(function () {
                Route::post('users', [EnterpriseUserController::class, 'store']);
                Route::put('users/{membership}', [EnterpriseUserController::class, 'update']);
                Route::patch('users/{membership}/manager', [EnterpriseHierarchyController::class, 'changeManager']);
                Route::post('users/{membership}/suspend', [EnterpriseUserController::class, 'suspend']);
                Route::post('users/{membership}/resume', [EnterpriseUserController::class, 'resume']);
                Route::post('users/{membership}/remove', [EnterpriseUserController::class, 'remove']);

                Route::get('invitations', [EnterpriseInvitationController::class, 'index']);
                Route::post('invitations', [EnterpriseInvitationController::class, 'store']);
                Route::post('invitations/{invitation}/resend', [EnterpriseInvitationController::class, 'resend']);
                Route::post('invitations/{invitation}/revoke', [EnterpriseInvitationController::class, 'revoke']);
            });

            Route::get('teams', [EnterpriseTeamController::class, 'index']);
            Route::get('teams/{team}', [EnterpriseTeamController::class, 'show']);
            Route::middleware('enterprise.role:admin')->group(function () {
                Route::post('teams', [EnterpriseTeamController::class, 'store']);
                Route::put('teams/{team}', [EnterpriseTeamController::class, 'update']);
                Route::delete('teams/{team}', [EnterpriseTeamController::class, 'destroy']);
                Route::post('teams/{team}/members', [EnterpriseTeamController::class, 'addMember']);
                Route::delete('teams/{team}/members/{user}', [EnterpriseTeamController::class, 'removeMember']);
            });

            Route::get('tasks', [EnterpriseTaskController::class, 'index']);
            Route::post('tasks', [EnterpriseTaskController::class, 'store']);
            Route::get('tasks/{task}', [EnterpriseTaskController::class, 'show']);
            Route::put('tasks/{task}', [EnterpriseTaskController::class, 'update']);
            Route::patch('tasks/{task}/status', [EnterpriseTaskController::class, 'updateStatus']);
            Route::delete('tasks/{task}', [EnterpriseTaskController::class, 'destroy']);

            Route::get('site-visits', [EnterpriseSiteVisitController::class, 'index']);
            Route::post('site-visits', [EnterpriseSiteVisitController::class, 'store']);
            Route::get('site-visits/{siteVisit}', [EnterpriseSiteVisitController::class, 'show']);
            Route::put('site-visits/{siteVisit}', [EnterpriseSiteVisitController::class, 'update']);
            Route::patch('site-visits/{siteVisit}/status', [EnterpriseSiteVisitController::class, 'updateStatus']);
            Route::delete('site-visits/{siteVisit}', [EnterpriseSiteVisitController::class, 'destroy']);

            Route::get('inventory/projects', [EnterpriseInventoryController::class, 'projects']);
            Route::get('inventory/projects/{project}', [EnterpriseInventoryController::class, 'showProject']);
            Route::get('inventory/units/search', [EnterpriseInventoryController::class, 'searchUnits']);
            Route::get('inventory/floors/{floor}/units', [EnterpriseInventoryController::class, 'floorUnits']);

            Route::middleware('enterprise.role:admin')->group(function () {
                Route::post('inventory/projects', [EnterpriseInventoryController::class, 'storeProject']);
                Route::put('inventory/projects/{project}', [EnterpriseInventoryController::class, 'updateProject']);
                Route::delete('inventory/projects/{project}', [EnterpriseInventoryController::class, 'destroyProject']);
                Route::post('inventory/projects/{project}/towers', [EnterpriseInventoryController::class, 'storeTower']);
                Route::put('inventory/towers/{tower}', [EnterpriseInventoryController::class, 'updateTower']);
                Route::delete('inventory/towers/{tower}', [EnterpriseInventoryController::class, 'destroyTower']);
                Route::post('inventory/towers/{tower}/wings', [EnterpriseInventoryController::class, 'storeWing']);
                Route::put('inventory/wings/{wing}', [EnterpriseInventoryController::class, 'updateWing']);
                Route::delete('inventory/wings/{wing}', [EnterpriseInventoryController::class, 'destroyWing']);
                Route::post('inventory/towers/{tower}/floors', [EnterpriseInventoryController::class, 'storeFloor']);
                Route::put('inventory/floors/{floor}', [EnterpriseInventoryController::class, 'updateFloor']);
                Route::delete('inventory/floors/{floor}', [EnterpriseInventoryController::class, 'destroyFloor']);
                Route::post('inventory/floors/{floor}/units', [EnterpriseInventoryController::class, 'storeUnit']);
                Route::post('inventory/floors/{floor}/units/bulk', [EnterpriseInventoryController::class, 'bulkStoreUnits']);
                Route::put('inventory/units/{unit}', [EnterpriseInventoryController::class, 'updateUnit']);
                Route::delete('inventory/units/{unit}', [EnterpriseInventoryController::class, 'destroyUnit']);
            });

            Route::patch('inventory/units/{unit}/status', [EnterpriseInventoryController::class, 'updateUnitStatus']);

            Route::get('targets', [EnterpriseTargetController::class, 'index']);
            Route::middleware('enterprise.role:admin,manager')->group(function () {
                Route::post('targets', [EnterpriseTargetController::class, 'store']);
                Route::put('targets/{target}', [EnterpriseTargetController::class, 'update']);
                Route::delete('targets/{target}', [EnterpriseTargetController::class, 'destroy']);
            });

            Route::get('customers', [EnterpriseCustomerController::class, 'index']);

            Route::get('campaigns', [EnterpriseCampaignController::class, 'index']);
            Route::get('campaigns/{campaign}', [EnterpriseCampaignController::class, 'show']);
            Route::middleware('enterprise.role:admin,manager')->group(function () {
                Route::post('campaigns', [EnterpriseCampaignController::class, 'store']);
                Route::put('campaigns/{campaign}', [EnterpriseCampaignController::class, 'update']);
            });
            Route::delete('campaigns/{campaign}', [EnterpriseCampaignController::class, 'destroy'])
                ->middleware('enterprise.role:admin');

            Route::get('meta/pages', [EnterpriseMetaController::class, 'pages'])
                ->middleware('enterprise.role:admin,manager');
            Route::get('meta/connect-url', [EnterpriseMetaController::class, 'connectUrl'])
                ->middleware('enterprise.role:admin');
            Route::post('meta/connect', [EnterpriseMetaController::class, 'connect'])
                ->middleware('enterprise.role:admin');
            Route::post('meta/disconnect', [EnterpriseMetaController::class, 'disconnect'])
                ->middleware('enterprise.role:admin');

            Route::middleware('enterprise.role:admin,manager')->group(function () {
                Route::get('campaigns/{campaign}/meta-integration', [EnterpriseMetaController::class, 'show']);
                Route::get('campaigns/{campaign}/meta/forms', [EnterpriseMetaController::class, 'forms']);
                Route::post('campaigns/{campaign}/meta/mapping', [EnterpriseMetaController::class, 'saveMapping']);
                Route::post('campaigns/{campaign}/meta-integration/capi', [EnterpriseMetaController::class, 'saveCapi']);
                Route::post('campaigns/{campaign}/meta-integration/disconnect-form', [EnterpriseMetaController::class, 'disconnectForm']);
                Route::post('campaigns/{campaign}/meta/sync', [EnterpriseMetaController::class, 'sync']);
            });

            Route::get('reports', [EnterpriseReportController::class, 'index']);
            Route::get('reports/targets', [EnterpriseReportController::class, 'targets']);
            Route::get('reports/export', [EnterpriseReportController::class, 'export'])
                ->middleware('enterprise.role:admin,manager');

            Route::get('notifications', [EnterpriseNotificationController::class, 'index']);
            Route::get('notifications/unread-count', [EnterpriseNotificationController::class, 'unreadCount']);
            Route::patch('notifications/{notification}/read', [EnterpriseNotificationController::class, 'markRead']);
            Route::post('notifications/mark-all-read', [EnterpriseNotificationController::class, 'markAllRead']);

            Route::put('settings/profile', [EnterpriseSettingsController::class, 'updateProfile']);
            Route::put('settings/password', [EnterpriseSettingsController::class, 'updatePassword']);
            Route::middleware('enterprise.role:admin')->group(function () {
                Route::get('settings', [EnterpriseSettingsController::class, 'show']);
                Route::put('settings', [EnterpriseSettingsController::class, 'update']);
                Route::post('settings/logo', [EnterpriseSettingsController::class, 'uploadLogo']);
                Route::delete('settings/logo', [EnterpriseSettingsController::class, 'removeLogo']);

                Route::get('billing', [EnterpriseBillingController::class, 'index']);
                Route::get('billing/invoices/{invoice}/download', [EnterpriseBillingController::class, 'downloadInvoice']);
                Route::post('billing/upgrade', [EnterpriseBillingController::class, 'requestUpgrade']);
                Route::post('billing/seat-requests', [EnterpriseBillingController::class, 'requestSeatAddon']);
            });

            Route::get('audit-logs', [EnterpriseAuditLogController::class, 'index'])
                ->middleware('enterprise.role:admin');
        });
    });
});
