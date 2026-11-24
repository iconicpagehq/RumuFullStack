# Detailed Code-Backed Implementation Audit

## Controllers Audit

## Services Audit

## Models Audit

## Routes Audit

| Route | Method | Action | Middleware |
|---|---|---|---|
| `api/v1/admin/settings` | `PUT` | `App\Http\Controllers\Api\Admin\SettingsController@updateSettings` | `api, auth:sanctum, admin.api` |
| `api/v1/admin/settings/appearance` | `PUT` | `App\Http\Controllers\Api\Admin\SettingsController@updateAppearance` | `api, auth:sanctum, admin.api` |
| `api/v1/admin/settings/meta-capi` | `PUT` | `App\Http\Controllers\Api\Admin\SettingsController@updateMetaCapi` | `api, auth:sanctum, admin.api` |
| `api/v1/realtor/settings/notifications` | `PUT` | `App\Http\Controllers\Api\Realtor\ProfileController@updateNotifications` | `api, auth:sanctum, realtor.api, subscription.active.api` |
| `checkout/apply-coupon` | `POST` | `App\Http\Controllers\CheckoutController@applyCoupon` | `web, auth` |
| `checkout/remove-coupon` | `POST` | `App\Http\Controllers\CheckoutController@removeCoupon` | `web, auth` |
| `admin/billing/dashboard` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\DashboardController@index` | `web, auth, role:admin` |
| `admin/billing/dashboard/export` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\DashboardController@export` | `web, auth, role:admin` |
| `admin/billing/subscriptions` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\SubscriptionController@index` | `web, auth, role:admin` |
| `admin/billing/subscriptions/{subscription}/extend` | `POST` | `App\Http\Controllers\Admin\Billing\SubscriptionController@extend` | `web, auth, role:admin` |
| `admin/billing/subscriptions/{subscription}/cancel` | `POST` | `App\Http\Controllers\Admin\Billing\SubscriptionController@cancel` | `web, auth, role:admin` |
| `admin/billing/payments` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\PaymentController@index` | `web, auth, role:admin` |
| `admin/billing/payments/{payment}` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\PaymentController@show` | `web, auth, role:admin` |
| `admin/billing/invoices` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\InvoiceController@index` | `web, auth, role:admin` |
| `admin/billing/invoices/{invoice}` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\InvoiceController@show` | `web, auth, role:admin` |
| `admin/billing/invoices/{invoice}/download` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\InvoiceController@download` | `web, auth, role:admin` |
| `admin/billing/invoices/{invoice}/preview` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\InvoiceController@preview` | `web, auth, role:admin` |
| `admin/billing/invoices/{invoice}/resend` | `POST` | `App\Http\Controllers\Admin\Billing\InvoiceController@resend` | `web, auth, role:admin` |
| `admin/billing/coupons` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\CouponController@index` | `web, auth, role:admin` |
| `admin/billing/coupons/create` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\CouponController@create` | `web, auth, role:admin` |
| `admin/billing/coupons` | `POST` | `App\Http\Controllers\Admin\Billing\CouponController@store` | `web, auth, role:admin` |
| `admin/billing/coupons/{coupon}` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\CouponController@show` | `web, auth, role:admin` |
| `admin/billing/coupons/{coupon}/edit` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\CouponController@edit` | `web, auth, role:admin` |
| `admin/billing/coupons/{coupon}` | `PUT|PATCH` | `App\Http\Controllers\Admin\Billing\CouponController@update` | `web, auth, role:admin` |
| `admin/billing/coupons/{coupon}` | `DELETE` | `App\Http\Controllers\Admin\Billing\CouponController@destroy` | `web, auth, role:admin` |
| `admin/billing/referrals` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\ReferralController@index` | `web, auth, role:admin` |
| `admin/billing/notifications/templates` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\BillingNotificationTemplateController@index` | `web, auth, role:admin` |
| `admin/billing/notifications/templates/create` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\BillingNotificationTemplateController@create` | `web, auth, role:admin` |
| `admin/billing/notifications/templates` | `POST` | `App\Http\Controllers\Admin\Billing\BillingNotificationTemplateController@store` | `web, auth, role:admin` |
| `admin/billing/notifications/templates/{template}/edit` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\BillingNotificationTemplateController@edit` | `web, auth, role:admin` |
| `admin/billing/notifications/templates/{template}` | `PUT|PATCH` | `App\Http\Controllers\Admin\Billing\BillingNotificationTemplateController@update` | `web, auth, role:admin` |
| `admin/billing/notifications` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\BillingNotificationController@index` | `web, auth, role:admin` |
| `admin/billing/notifications/{notification}/resend` | `POST` | `App\Http\Controllers\Admin\Billing\BillingNotificationController@resend` | `web, auth, role:admin` |
| `admin/analytics/dashboard` | `GET|HEAD` | `App\Http\Controllers\Admin\Analytics\DashboardController@index` | `web, auth, role:admin` |
| `admin/analytics/revenue` | `GET|HEAD` | `App\Http\Controllers\Admin\Analytics\RevenueController@index` | `web, auth, role:admin` |
| `admin/analytics/customers` | `GET|HEAD` | `App\Http\Controllers\Admin\Analytics\CustomerController@index` | `web, auth, role:admin` |
| `admin/analytics/subscriptions` | `GET|HEAD` | `App\Http\Controllers\Admin\Analytics\SubscriptionController@index` | `web, auth, role:admin` |
| `admin/analytics/payments` | `GET|HEAD` | `App\Http\Controllers\Admin\Analytics\PaymentController@index` | `web, auth, role:admin` |
| `admin/analytics/reports` | `GET|HEAD` | `App\Http\Controllers\Admin\Analytics\ReportController@index` | `web, auth, role:admin` |
| `admin/analytics/reports/export` | `POST` | `App\Http\Controllers\Admin\Analytics\ReportController@export` | `web, auth, role:admin` |
| `admin/realtors/{realtor}/billing` | `GET|HEAD` | `App\Http\Controllers\Admin\Billing\SubscriptionController@customerBilling` | `web, auth, role:admin` |
| `admin/realtors/{realtor}/suspend-subscription` | `POST` | `App\Http\Controllers\Admin\RealtorController@suspendSubscription` | `web, auth, role:admin` |
| `admin/realtors/{realtor}/resume-subscription` | `POST` | `App\Http\Controllers\Admin\RealtorController@resumeSubscription` | `web, auth, role:admin` |
| `admin/settings` | `GET|HEAD` | `App\Http\Controllers\AdminController@settings` | `web, auth, role:admin` |
| `admin/settings` | `POST` | `App\Http\Controllers\AdminController@updateSettings` | `web, auth, role:admin` |
| `admin/settings/appearance` | `POST` | `App\Http\Controllers\AdminController@updateAppearance` | `web, auth, role:admin` |
| `admin/settings/meta-capi` | `POST` | `App\Http\Controllers\AdminController@updateMetaCapi` | `web, auth, role:admin` |
| `admin/settings/billing` | `GET|HEAD` | `App\Http\Controllers\Admin\BillingSettingsController@index` | `web, auth, role:admin` |
| `admin/settings/billing` | `POST` | `App\Http\Controllers\Admin\BillingSettingsController@update` | `web, auth, role:admin` |
| `realtor/settings` | `GET|HEAD` | `App\Http\Controllers\RealtorController@settings` | `web, auth, role:realtor` |
| `realtor/settings/notifications` | `PUT` | `App\Http\Controllers\RealtorController@updateNotifications` | `web, auth, role:realtor` |
| `realtor/settings/privacy` | `PUT` | `App\Http\Controllers\RealtorController@updatePrivacy` | `web, auth, role:realtor` |
| `realtor/settings/appearance` | `PUT` | `App\Http\Controllers\RealtorController@updateAppearance` | `web, auth, role:realtor` |
| `realtor/settings/account` | `DELETE` | `App\Http\Controllers\RealtorController@deleteAccount` | `web, auth, role:realtor` |
| `realtor/realtor/billing` | `GET|HEAD` | `App\Http\Controllers\Realtor\BillingController@index` | `web, auth, role:realtor` |
| `realtor/realtor/billing/invoices/{invoice}` | `GET|HEAD` | `App\Http\Controllers\Realtor\BillingController@showInvoice` | `web, auth, role:realtor` |
| `realtor/realtor/billing/invoices/{invoice}/download` | `GET|HEAD` | `App\Http\Controllers\Realtor\BillingController@downloadInvoice` | `web, auth, role:realtor` |
| `realtor/realtor/billing/notifications` | `GET|HEAD` | `App\Http\Controllers\Realtor\BillingNotificationController@index` | `web, auth, role:realtor` |
| `realtor/realtor/billing/notifications/{notification}/read` | `POST` | `App\Http\Controllers\Realtor\BillingNotificationController@markAsRead` | `web, auth, role:realtor` |
