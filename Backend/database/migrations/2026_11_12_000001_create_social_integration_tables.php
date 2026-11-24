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
        // 1. social_accounts
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('platform', 30)->default('facebook'); // facebook, instagram
            $table->string('external_id', 100);
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->text('profile_picture_url')->nullable();
            $table->text('access_token'); // Encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('permissions')->nullable();
            $table->string('status', 30)->default('connected'); // connected, expired, disconnected
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('external_id');
        });

        // 2. social_pages (Facebook Pages & linked Instagram Accounts)
        Schema::create('social_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('platform', 30)->default('facebook'); // facebook, instagram
            $table->string('external_id', 100); // Page ID or IG Business ID
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('category')->nullable();
            $table->string('instagram_business_account_id', 100)->nullable();
            $table->text('access_token'); // Encrypted page token
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('external_id');
            $table->index('instagram_business_account_id');
        });

        // 3. social_posts
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_page_id')->constrained('social_pages')->cascadeOnDelete();
            $table->string('platform', 30); // facebook, instagram
            $table->string('external_id', 100);
            $table->text('permalink')->nullable();
            $table->text('message')->nullable();
            $table->string('media_type', 50)->nullable(); // image, video, carousel_album, reel, status
            $table->text('media_url')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['social_page_id', 'external_id']);
            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('published_at');
        });

        // 4. social_comments
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('parent_comment_id')->nullable()->constrained('social_comments')->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('external_id', 100);
            $table->string('from_id', 100)->nullable();
            $table->string('from_name')->nullable();
            $table->text('message');
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('created_time')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['social_post_id', 'external_id']);
            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('parent_comment_id');
            $table->index('created_time');
        });

        // 5. social_conversations
        Schema::create('social_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_page_id')->constrained('social_pages')->cascadeOnDelete();
            $table->string('platform', 30); // facebook, instagram
            $table->string('external_id', 100); // Meta conversation ID
            $table->string('participant_id', 100); // PSID or IGSID
            $table->string('participant_name')->nullable();
            $table->string('participant_username')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->text('last_message_text')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->foreignId('is_assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->unique(['social_page_id', 'external_id']);
            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('participant_id');
            $table->index('last_message_at');
            $table->index('is_assigned_to');
        });

        // 6. social_messages
        Schema::create('social_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_conversation_id')->constrained('social_conversations')->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('external_id', 100)->nullable();
            $table->string('sender_id', 100);
            $table->string('sender_name')->nullable();
            $table->string('recipient_id', 100);
            $table->text('message_text')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_from_page')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('external_id');
            $table->index('sent_at');
        });

        // 7. social_mentions
        Schema::create('social_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_page_id')->constrained('social_pages')->cascadeOnDelete();
            $table->string('platform', 30); // facebook, instagram
            $table->string('external_id', 100);
            $table->string('media_id', 100)->nullable();
            $table->string('comment_id', 100)->nullable();
            $table->string('mention_type', 30)->default('post'); // post, story, comment, tag
            $table->text('media_url')->nullable();
            $table->text('caption')->nullable();
            $table->string('author_username')->nullable();
            $table->timestamp('mentioned_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('external_id');
            $table->index('mentioned_at');
        });

        // 8. social_insights
        Schema::create('social_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_page_id')->constrained('social_pages')->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('metric_type', 50); // page_impressions, reach, profile_views, engagement, etc.
            $table->string('period', 30)->default('day'); // day, week, days_28, lifetime
            $table->json('metrics');
            $table->date('recorded_date');
            $table->timestamps();

            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index(['social_page_id', 'metric_type', 'recorded_date']);
        });

        // 9. social_webhook_events
        Schema::create('social_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 100)->unique();
            $table->string('platform', 30)->default('meta');
            $table->string('event_type', 100);
            $table->json('payload');
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('processed');
            $table->index('event_type');
            $table->index('created_at');
        });

        // 10. social_sync_logs
        Schema::create('social_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('social_page_id')->nullable()->constrained('social_pages')->cascadeOnDelete();
            $table->string('platform', 30);
            $table->string('sync_type', 50); // posts, comments, conversations, insights, full
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed, failed
            $table->unsignedInteger('items_synced')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'platform']);
            $table->index(['user_id', 'platform']);
            $table->index('status');
        });

        // 11. social_oauth_states
        Schema::create('social_oauth_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('enterprises')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('state', 128)->unique();
            $table->text('redirect_uri')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_oauth_states');
        Schema::dropIfExists('social_sync_logs');
        Schema::dropIfExists('social_webhook_events');
        Schema::dropIfExists('social_insights');
        Schema::dropIfExists('social_mentions');
        Schema::dropIfExists('social_messages');
        Schema::dropIfExists('social_conversations');
        Schema::dropIfExists('social_comments');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_pages');
        Schema::dropIfExists('social_accounts');
    }
};

