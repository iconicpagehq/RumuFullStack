<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExtendedModulesApiTest extends TestCase
{
    use RefreshDatabase;

    private function realtor(): User
    {
        $plan = Plan::factory()->basic()->create();

        return User::factory()->create(['role' => 'realtor', 'plan_id' => $plan->id]);
    }

    public function test_realtor_billing_dashboard_returns_empty_state(): void
    {
        Sanctum::actingAs($this->realtor());

        $this->getJson('/api/v1/realtor/billing')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'current_subscription',
                    'summary' => ['total_paid', 'pending_payments', 'total_invoices'],
                    'subscriptions' => ['data', 'meta'],
                    'payments' => ['data', 'meta'],
                    'invoices' => ['data', 'meta'],
                ],
            ]);
    }

    public function test_realtor_can_read_and_update_settings(): void
    {
        Sanctum::actingAs($this->realtor());

        $this->getJson('/api/v1/realtor/settings')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->putJson('/api/v1/realtor/settings/appearance', [
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'Asia/Kolkata',
        ])->assertOk()
            ->assertJsonPath('data.user.theme', 'dark');

        $this->putJson('/api/v1/realtor/settings/privacy', [
            'public_profile' => true,
        ])->assertOk()
            ->assertJsonPath('data.user.public_profile', true);
    }

    public function test_realtor_meta_integration_and_capi_settings(): void
    {
        $realtor = $this->realtor();
        $campaign = Campaign::factory()->create(['user_id' => $realtor->id]);

        Sanctum::actingAs($realtor);

        $this->getJson("/api/v1/realtor/campaigns/{$campaign->id}/meta-integration")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['pages', 'stats', 'webhook_health', 'logs']]);

        $this->postJson("/api/v1/realtor/campaigns/{$campaign->id}/meta-integration/capi", [
            'pixel_id' => '123456789',
            'access_token' => 'test-token',
            'is_active' => true,
            'status_mapping' => ['won' => 'Purchase'],
        ])->assertOk()
            ->assertJsonPath('data.pixel_id', '123456789')
            ->assertJsonPath('data.has_access_token', true);
    }

    public function test_checkout_quote_endpoints(): void
    {
        $realtor = $this->realtor();
        $plan = Plan::factory()->create(['key' => 'growth', 'name' => 'Growth', 'price' => 1000]);

        $this->getJson('/api/v1/checkout/plans')
            ->assertOk()
            ->assertJsonPath('success', true);

        Sanctum::actingAs($realtor);

        $this->getJson("/api/v1/checkout/summary?plan_id={$plan->id}&duration_months=1")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['price', 'discount', 'taxable_amount', 'tax_amount', 'total']]);
    }

    public function test_admin_extended_modules_are_reachable(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $realtor = $this->realtor();

        Sanctum::actingAs($admin);

        $endpoints = [
            '/api/v1/admin/billing/subscriptions',
            '/api/v1/admin/billing/payments',
            '/api/v1/admin/billing/invoices',
            '/api/v1/admin/billing/coupons',
            '/api/v1/admin/billing/referrals',
            '/api/v1/admin/billing/notification-templates',
            '/api/v1/admin/billing/notifications',
            '/api/v1/admin/billing/settings',
            '/api/v1/admin/analytics/dashboard',
            '/api/v1/admin/analytics/revenue',
            '/api/v1/admin/analytics/payments',
            '/api/v1/admin/analytics/customers',
            '/api/v1/admin/analytics/reports',
            '/api/v1/admin/realtors',
            "/api/v1/admin/realtors/{$realtor->id}",
            "/api/v1/admin/realtors/{$realtor->id}/billing",
            '/api/v1/admin/users',
            '/api/v1/admin/settings',
        ];

        foreach ($endpoints as $endpoint) {
            $this->getJson($endpoint)
                ->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_admin_can_create_and_delete_coupon(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $response = $this->postJson('/api/v1/admin/billing/coupons', [
            'code' => 'launch10',
            'name' => 'Launch 10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'applicable_plans' => 'growth,pro',
        ])->assertCreated();

        $couponId = $response->json('data.id');

        $this->getJson("/api/v1/admin/billing/coupons/{$couponId}")
            ->assertOk()
            ->assertJsonPath('data.code', 'LAUNCH10');

        $this->deleteJson("/api/v1/admin/billing/coupons/{$couponId}")
            ->assertOk();
    }
}
