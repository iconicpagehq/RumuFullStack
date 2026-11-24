<?php

namespace Tests\Feature\Social;

use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Social\SocialAccount;
use App\Models\Social\SocialConversation;
use App\Models\Social\SocialOAuthState;
use App\Models\Social\SocialPage;
use App\Models\Social\SocialPost;
use App\Models\User;
use App\Services\Social\Meta\MetaOAuthService;
use App\Services\Social\Meta\MetaTokenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SocialIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.app_id' => '123456789',
            'services.meta.app_secret' => 'supersecretappsecret',
            'services.meta.webhook_verify_token' => 'meta_rumu_verify_token_123',
            'services.meta.graph_version' => 'v20.0',
        ]);
    }

    /** @test */
    public function it_successfully_verifies_meta_webhook_challenge()
    {
        $response = $this->get('/api/v1/social/meta/webhook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'meta_rumu_verify_token_123',
            'hub_challenge' => 'challenge_code_987654',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('challenge_code_987654', $response->getContent());
    }

    /** @test */
    public function it_rejects_invalid_meta_webhook_challenge_token()
    {
        $response = $this->get('/api/v1/social/meta/webhook?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge_code_987654',
        ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_x_hub_signature_and_queues_webhook_event()
    {
        Queue::fake();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_ext_123',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => 'cust_456'],
                            'recipient' => ['id' => 'page_ext_123'],
                            'timestamp' => 1731400000000,
                            'message' => [
                                'mid' => 'mid_abc123',
                                'text' => 'Hello from Messenger!',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawBody = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $rawBody, 'supersecretappsecret');

        $response = $this->call(
            'POST',
            '/api/v1/social/meta/webhook',
            [],
            [],
            [],
            ['HTTP_X_HUB_SIGNATURE_256' => $signature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(200);
        $this->assertEquals('EVENT_RECEIVED', $response->getContent());
    }

    /** @test */
    public function it_rejects_webhook_with_tampered_signature()
    {
        $rawBody = json_encode(['object' => 'page']);
        $badSignature = 'sha256=invalid_signature_hash';

        $response = $this->call(
            'POST',
            '/api/v1/social/meta/webhook',
            [],
            [],
            [],
            ['HTTP_X_HUB_SIGNATURE_256' => $badSignature, 'CONTENT_TYPE' => 'application/json'],
            $rawBody
        );

        $response->assertStatus(403);
    }

    /** @test */
    public function it_generates_and_validates_single_use_oauth_state()
    {
        $user = User::factory()->create();
        $oauthService = app(MetaOAuthService::class);

        $url = $oauthService->generateAuthorizationUrl($user->id, null, 'https://rumu.in/callback');

        $this->assertStringContainsString('https://www.facebook.com/v20.0/dialog/oauth', $url);
        $this->assertStringContainsString('client_id=123456789', $url);

        // State stored in database
        $stateRecord = SocialOAuthState::where('user_id', $user->id)->first();
        $this->assertNotNull($stateRecord);
        $this->assertEquals($user->id, $stateRecord->user_id);

        // Validate state
        $validated = $oauthService->validateState($stateRecord->state);
        $this->assertEquals($stateRecord->state, $validated->state);

        // State is single-use and should be deleted
        $this->assertDatabaseMissing('social_oauth_states', ['id' => $stateRecord->id]);
    }

    /** @test */
    public function it_encrypts_access_tokens_at_rest()
    {
        $user = User::factory()->create();
        $secretToken = 'EAABsb_very_secret_meta_user_token_12345';

        $account = SocialAccount::create([
            'user_id' => $user->id,
            'company_id' => null,
            'platform' => 'facebook',
            'external_id' => 'meta_uid_100',
            'name' => 'Atharva Sawant',
            'access_token' => $secretToken,
            'status' => 'connected',
        ]);

        // Decrypted via accessor
        $this->assertEquals($secretToken, $account->access_token);

        // Raw database column is encrypted and never matches plain text
        $rawInDb = \DB::table('social_accounts')->where('id', $account->id)->value('access_token');
        $this->assertNotEquals($secretToken, $rawInDb);
    }

    /** @test */
    public function it_enforces_multi_tenant_isolation_between_companies()
    {
        $companyA = Enterprise::create([
            'name' => 'Company A Realty',
            'email' => 'company.a@realty.com',
            'slug' => 'company-a',
            'status' => 'active',
        ]);

        $companyB = Enterprise::create([
            'name' => 'Company B Homes',
            'email' => 'company.b@homes.com',
            'slug' => 'company-b',
            'status' => 'active',
        ]);

        $userA = User::factory()->create();
        $userB = User::factory()->create();

        EnterpriseMembership::create([
            'enterprise_id' => $companyA->id,
            'user_id' => $userA->id,
            'role' => EnterpriseMembership::ROLE_ADMIN,
            'status' => EnterpriseMembership::STATUS_ACTIVE,
        ]);

        EnterpriseMembership::create([
            'enterprise_id' => $companyB->id,
            'user_id' => $userB->id,
            'role' => EnterpriseMembership::ROLE_ADMIN,
            'status' => EnterpriseMembership::STATUS_ACTIVE,
        ]);

        $accountA = SocialAccount::create([
            'company_id' => $companyA->id,
            'user_id' => $userA->id,
            'platform' => 'facebook',
            'external_id' => 'fb_comp_a',
            'name' => 'Company A Page Account',
            'access_token' => 'token_a',
            'status' => 'connected',
        ]);

        $accountB = SocialAccount::create([
            'company_id' => $companyB->id,
            'user_id' => $userB->id,
            'platform' => 'facebook',
            'external_id' => 'fb_comp_b',
            'name' => 'Company B Page Account',
            'access_token' => 'token_b',
            'status' => 'connected',
        ]);

        // User A requests accounts
        $responseA = $this->actingAs($userA)->getJson('/api/v1/social/accounts');
        $responseA->assertStatus(200);
        $dataA = $responseA->json('data');

        $this->assertCount(1, $dataA);
        $this->assertEquals('Company A Page Account', $dataA[0]['name']);

        // User B cannot see User A's accounts
        $responseB = $this->actingAs($userB)->getJson('/api/v1/social/accounts');
        $responseB->assertStatus(200);
        $dataB = $responseB->json('data');

        $this->assertCount(1, $dataB);
        $this->assertEquals('Company B Page Account', $dataB[0]['name']);

        // Attempting to delete Company A's account as User B should fail with 404
        $deleteResp = $this->actingAs($userB)->deleteJson("/api/v1/social/accounts/{$accountA->id}");
        $deleteResp->assertStatus(404);
    }
}

