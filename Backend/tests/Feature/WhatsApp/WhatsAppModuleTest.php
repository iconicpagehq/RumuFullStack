<?php

namespace Tests\Feature\WhatsApp;

use App\Jobs\WhatsApp\ProcessWhatsAppWebhookJob;
use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Models\Enterprise;
use App\Models\EnterpriseMembership;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppAccount;
use App\Models\WhatsApp\WhatsAppContact;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Models\WhatsApp\WhatsAppTemplate;
use App\Models\WhatsApp\WhatsAppWebhookEvent;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Enterprise $companyA;
    protected Enterprise $companyB;
    protected User $userA;
    protected User $userB;
    protected WhatsAppAccount $accountA;
    protected WhatsAppAccount $accountB;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.app_secret' => 'test_whatsapp_app_secret_123',
            'services.whatsapp.webhook_verify_token' => 'test_whatsapp_verify_token_xyz',
            'services.whatsapp.api_version' => 'v19.0',
            'services.whatsapp.access_token' => 'test_system_access_token',
            'services.whatsapp.phone_number_id' => '1000999888',
        ]);

        // Create Company A
        $this->companyA = Enterprise::create([
            'name' => 'Company A Realty',
            'email' => 'info@companya.com',
            'slug' => 'company-a-realty',
            'business_type' => 'real_estate',
            'company_size' => '10-50',
            'status' => 'active',
        ]);

        $this->userA = User::create([
            'name' => 'Agent A',
            'email' => 'agent.a@companya.com',
            'password' => bcrypt('secret123'),
            'role' => 'enterprise',
        ]);

        EnterpriseMembership::create([
            'enterprise_id' => $this->companyA->id,
            'user_id' => $this->userA->id,
            'role' => EnterpriseMembership::ROLE_ADMIN,
            'status' => EnterpriseMembership::STATUS_ACTIVE,
        ]);

        $this->accountA = WhatsAppAccount::create([
            'company_id' => $this->companyA->id,
            'user_id' => $this->userA->id,
            'waba_id' => 'waba_111',
            'phone_number_id' => 'phone_id_111',
            'display_phone_number' => '+91 98765 00001',
            'verified_name' => 'Company A WhatsApp',
            'access_token' => 'token_a_encrypted',
            'is_default' => true,
            'status' => 'connected',
        ]);

        // Create Company B
        $this->companyB = Enterprise::create([
            'name' => 'Company B Estates',
            'email' => 'info@companyb.com',
            'slug' => 'company-b-estates',
            'business_type' => 'real_estate',
            'company_size' => '5-10',
            'status' => 'active',
        ]);

        $this->userB = User::create([
            'name' => 'Agent B',
            'email' => 'agent.b@companyb.com',
            'password' => bcrypt('secret123'),
            'role' => 'enterprise',
        ]);

        EnterpriseMembership::create([
            'enterprise_id' => $this->companyB->id,
            'user_id' => $this->userB->id,
            'role' => EnterpriseMembership::ROLE_ADMIN,
            'status' => EnterpriseMembership::STATUS_ACTIVE,
        ]);

        $this->accountB = WhatsAppAccount::create([
            'company_id' => $this->companyB->id,
            'user_id' => $this->userB->id,
            'waba_id' => 'waba_222',
            'phone_number_id' => 'phone_id_222',
            'display_phone_number' => '+91 98765 00002',
            'verified_name' => 'Company B WhatsApp',
            'access_token' => 'token_b_encrypted',
            'is_default' => true,
            'status' => 'connected',
        ]);
    }

    /** @test */
    public function it_successfully_executes_migrations_and_creates_all_tables()
    {
        $this->assertTrue(Schema::hasTable('whatsapp_accounts'));
        $this->assertTrue(Schema::hasTable('whatsapp_contacts'));
        $this->assertTrue(Schema::hasTable('whatsapp_conversations'));
        $this->assertTrue(Schema::hasTable('whatsapp_messages'));
        $this->assertTrue(Schema::hasTable('whatsapp_templates'));
        $this->assertTrue(Schema::hasTable('whatsapp_webhook_events'));
        $this->assertTrue(Schema::hasTable('whatsapp_message_logs'));
    }

    /** @test */
    public function it_properly_configures_model_relationships()
    {
        $contact = WhatsAppContact::create([
            'company_id' => $this->companyA->id,
            'wa_id' => '919876543210',
            'phone_number' => '+919876543210',
            'name' => 'John Doe',
        ]);

        $conversation = WhatsAppConversation::create([
            'company_id' => $this->companyA->id,
            'whatsapp_account_id' => $this->accountA->id,
            'whatsapp_contact_id' => $contact->id,
            'assigned_to' => $this->userA->id,
        ]);

        $message = WhatsAppMessage::create([
            'company_id' => $this->companyA->id,
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'sender_id' => $this->accountA->phone_number_id,
            'recipient_id' => $contact->wa_id,
            'type' => 'text',
            'content' => 'Hello John!',
        ]);

        $this->assertEquals($this->companyA->id, $conversation->company->id);
        $this->assertEquals($this->accountA->id, $conversation->account->id);
        $this->assertEquals($contact->id, $conversation->contact->id);
        $this->assertEquals($this->userA->id, $conversation->assignedEmployee->id);
        $this->assertCount(1, $conversation->messages);
        $this->assertEquals($conversation->id, $message->conversation->id);
    }

    /** @test */
    public function it_enforces_company_level_data_isolation()
    {
        $contactA = WhatsAppContact::create([
            'company_id' => $this->companyA->id,
            'wa_id' => '919111111111',
            'phone_number' => '+919111111111',
            'name' => 'Customer of Company A',
        ]);

        $convA = WhatsAppConversation::create([
            'company_id' => $this->companyA->id,
            'whatsapp_account_id' => $this->accountA->id,
            'whatsapp_contact_id' => $contactA->id,
            'last_message_text' => 'Confidential Lead A',
        ]);

        // User B from Company B queries conversations
        $response = $this->actingAs($this->userB, 'sanctum')
            ->getJson('/api/whatsapp/conversations');

        $response->assertStatus(200);
        $data = $response->json('data.data') ?? $response->json('data');
        $this->assertCount(0, $data);

        // User B cannot access Conversation A by direct ID
        $unauthorizedShow = $this->actingAs($this->userB, 'sanctum')
            ->getJson("/api/whatsapp/conversations/{$convA->id}/messages");

        $unauthorizedShow->assertStatus(404);
    }

    /** @test */
    public function it_successfully_verifies_webhook_challenge()
    {
        $response = $this->get('/api/whatsapp/webhook?' . http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'test_whatsapp_verify_token_xyz',
            'hub.challenge' => 'challenge_code_123456',
        ]));

        $response->assertStatus(200);
        $this->assertEquals('challenge_code_123456', $response->getContent());
    }

    /** @test */
    public function it_rejects_webhook_verification_with_invalid_token()
    {
        $response = $this->get('/api/whatsapp/webhook?' . http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'invalid_token_here',
            'hub.challenge' => 'challenge_code_123456',
        ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function it_validates_webhook_post_signature_and_queues_processing()
    {
        Queue::fake([ProcessWhatsAppWebhookJob::class]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba_111',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'display_phone_number' => '919876500001',
                                    'phone_number_id' => 'phone_id_111',
                                ],
                                'contacts' => [
                                    [
                                        'profile' => ['name' => 'Alice'],
                                        'wa_id' => '919876543210',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '919876543210',
                                        'id' => 'wamid.test_incoming_001',
                                        'timestamp' => '1732450000',
                                        'text' => ['body' => 'I am interested in 3BHK flat.'],
                                        'type' => 'text',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawJson = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $rawJson, 'test_whatsapp_app_secret_123');

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/whatsapp/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'EVENT_RECEIVED']);

        $this->assertDatabaseHas('whatsapp_webhook_events', [
            'event_id' => 'msg_wamid.test_incoming_001',
            'event_type' => 'messages',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    }

    /** @test */
    public function it_rejects_webhook_post_with_invalid_signature()
    {
        $payload = ['test' => 'data'];
        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=invalid_signature_hash',
        ])->postJson('/api/whatsapp/webhook', $payload);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_duplicate_webhook_events()
    {
        Queue::fake([ProcessWhatsAppWebhookJob::class]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messages' => [
                                    ['id' => 'wamid.duplicate_test_123'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $rawJson = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $rawJson, 'test_whatsapp_app_secret_123');

        // First call
        $response1 = $this->withHeaders(['X-Hub-Signature-256' => $signature])
            ->postJson('/api/whatsapp/webhook', $payload);
        $response1->assertStatus(200);

        // Second call with same payload
        $response2 = $this->withHeaders(['X-Hub-Signature-256' => $signature])
            ->postJson('/api/whatsapp/webhook', $payload);
        $response2->assertStatus(200);
        $response2->assertJsonPath('detail.status', 'duplicate');

        $this->assertEquals(1, WhatsAppWebhookEvent::where('event_id', 'msg_wamid.duplicate_test_123')->count());
    }

    /** @test */
    public function it_validates_outgoing_message_requests()
    {
        // Missing recipient 'to'
        $response = $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/whatsapp/messages/send', [
                'message' => 'Hello',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['to']);

        // Missing message body when sending text
        $response2 = $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/whatsapp/messages/send', [
                'to' => '919876543210',
            ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function it_dispatches_send_job_and_handles_api_success()
    {
        Queue::fake([SendWhatsAppMessageJob::class]);

        $response = $this->actingAs($this->userA, 'sanctum')
            ->postJson('/api/whatsapp/messages/send', [
                'to' => '919876543210',
                'message' => 'Welcome to Rumu CRM!',
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('whatsapp_messages', [
            'company_id' => $this->companyA->id,
            'recipient_id' => '919876543210',
            'content' => 'Welcome to Rumu CRM!',
            'direction' => 'outgoing',
            'status' => 'pending',
        ]);

        Queue::assertPushed(SendWhatsAppMessageJob::class);
    }

    /** @test */
    public function it_handles_whatsapp_api_failure_gracefully()
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => [
                    'message' => 'The message recipient is not valid or opt-in missing.',
                    'code' => 131026,
                ],
            ], 400),
        ]);

        $contact = WhatsAppContact::create([
            'company_id' => $this->companyA->id,
            'wa_id' => '919999999999',
            'phone_number' => '+919999999999',
        ]);

        $conversation = WhatsAppConversation::create([
            'company_id' => $this->companyA->id,
            'whatsapp_account_id' => $this->accountA->id,
            'whatsapp_contact_id' => $contact->id,
        ]);

        $message = WhatsAppMessage::create([
            'company_id' => $this->companyA->id,
            'whatsapp_conversation_id' => $conversation->id,
            'direction' => 'outgoing',
            'sender_id' => $this->accountA->phone_number_id,
            'recipient_id' => $contact->wa_id,
            'type' => 'text',
            'content' => 'Test message',
            'status' => 'pending',
        ]);

        $job = new SendWhatsAppMessageJob($message->id);

        try {
            $job->handle(app(WhatsAppService::class));
            $this->fail('Expected exception from Meta API failure.');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('Meta WhatsApp API Error [131026]', $e->getMessage());
        }

        // Run failed handler
        $job->failed(new \Exception('Meta WhatsApp API Error [131026]'));

        $this->assertDatabaseHas('whatsapp_messages', [
            'id' => $message->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('whatsapp_message_logs', [
            'company_id' => $this->companyA->id,
            'whatsapp_message_id' => $message->id,
            'http_status' => 400,
        ]);
    }
}
