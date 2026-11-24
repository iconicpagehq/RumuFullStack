<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. whatsapp_accounts
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('waba_id', 100)->nullable(); // WhatsApp Business Account ID
            $table->string('phone_number_id', 100); // Meta Phone Number ID
            $table->string('display_phone_number', 50); // E.g. "+91 98765 43210"
            $table->string('verified_name', 255)->nullable();
            $table->string('code_verification_status', 50)->default('NOT_VERIFIED');
            $table->string('quality_rating', 50)->default('GREEN');
            $table->string('status', 30)->default('connected'); // connected, disconnected, pending, expired
            $table->text('access_token'); // Encrypted access token
            $table->text('app_secret')->nullable(); // Encrypted app secret if tenant-specific
            $table->string('webhook_verify_token', 128)->nullable();
            $table->string('api_version', 20)->default('v19.0');
            $table->boolean('is_default')->default(false);
            $table->json('settings')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index('phone_number_id');
            $table->index('waba_id');
        });

        // 2. whatsapp_contacts
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('wa_id', 50); // Meta WhatsApp ID / E.164 without plus, e.g. 919876543210
            $table->string('phone_number', 50); // Clean E.164 phone number
            $table->string('name', 255)->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->json('profile_data')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'wa_id']);
            $table->index(['company_id', 'phone_number']);
            $table->index('lead_id');
        });

        // 3. whatsapp_conversations
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->foreignId('whatsapp_contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Assigned agent/employee
            $table->string('status', 30)->default('open'); // open, pending, resolved, closed, expired
            $table->timestamp('session_expires_at')->nullable(); // 24h Meta Customer Service window
            $table->unsignedInteger('unread_count')->default(0);
            $table->text('last_message_text')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_incoming_at')->nullable();
            $table->timestamp('last_outgoing_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['whatsapp_account_id', 'whatsapp_contact_id']);
            $table->index(['company_id', 'status']);
            $table->index('assigned_to');
            $table->index('last_message_at');
        });

        // 4. whatsapp_messages
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('whatsapp_conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('meta_message_id', 191)->nullable(); // e.g. wamid.HBgM...
            $table->string('direction', 20); // incoming, outgoing
            $table->string('sender_id', 100);
            $table->string('recipient_id', 100);
            $table->string('type', 30)->default('text'); // text, image, document, audio, video, location, template, interactive
            $table->text('content')->nullable(); // Text body or caption
            $table->text('media_url')->nullable();
            $table->string('media_mime_type', 100)->nullable();
            $table->json('raw_payload')->nullable();
            $table->string('status', 30)->default('pending'); // pending, sent, delivered, read, failed
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('meta_message_id');
            $table->index(['company_id', 'status']);
            $table->index(['whatsapp_conversation_id', 'created_at']);
            $table->index('direction');
        });

        // 5. whatsapp_templates
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('meta_template_id', 100)->nullable();
            $table->string('name', 100);
            $table->string('language', 20)->default('en_US');
            $table->string('category', 50)->default('UTILITY'); // MARKETING, UTILITY, AUTHENTICATION
            $table->string('status', 30)->default('APPROVED'); // APPROVED, PENDING, REJECTED, PAUSED, DISABLED
            $table->json('components'); // HEADER, BODY, FOOTER, BUTTONS
            $table->json('sample_values')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['whatsapp_account_id', 'name', 'language']);
            $table->index(['company_id', 'status']);
            $table->index('category');
        });

        // 6. whatsapp_webhook_events
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 191)->unique(); // Meta event ID or payload hash for deduplication
            $table->string('event_type', 100); // messages, statuses, etc.
            $table->longText('payload'); // Raw JSON payload
            $table->string('status', 30)->default('pending'); // pending, processing, processed, failed
            $table->text('error')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('event_type');
            $table->index('created_at');
        });

        // 7. whatsapp_message_logs
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->string('api_endpoint', 255);
            $table->string('http_method', 10)->default('POST');
            $table->integer('http_status')->nullable();
            $table->longText('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->text('error_details')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'http_status']);
            $table->index('whatsapp_message_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('whatsapp_conversations');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
