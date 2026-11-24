<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('call_numbers')) {
            Schema::create('call_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('number', 32);
                $table->string('label', 64)->nullable();
                $table->string('provider', 32)->default('exotel');
                $table->string('provider_number_id', 191)->nullable();
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'number']);
            });
        }

        if (!Schema::hasTable('user_call_profiles')) {
            Schema::create('user_call_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->string('agent_number', 32)->nullable();
                $table->boolean('auto_record')->default(true);
                $table->boolean('consent_notice')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('call_logs')) {
            Schema::create('call_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('edition', 16)->index();
                $table->foreignId('realtor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('enterprise_id')->nullable()->constrained('enterprises')->nullOnDelete();
                $table->foreignId('enterprise_membership_id')->nullable()->constrained('enterprise_memberships')->nullOnDelete();
                $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
                $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
                $table->string('lead_name', 255)->nullable();
                $table->string('lead_phone', 32)->nullable();
                $table->string('provider', 32)->nullable();
                $table->string('provider_call_id', 191)->nullable()->index();
                $table->string('direction', 16)->default('outbound');
                $table->string('source', 24)->default('app');
                $table->string('status', 24)->default('initiating')->index();
                $table->string('disposition', 32)->nullable()->index();
                $table->text('disposition_notes')->nullable();
                $table->string('from_number', 32)->nullable();
                $table->string('to_number', 32)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('answered_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->unsignedInteger('duration_seconds')->default(0);
                $table->string('recording_status', 24)->default('none')->index();
                $table->string('recording_disk', 32)->nullable();
                $table->string('recording_path', 255)->nullable();
                $table->string('recording_mime', 64)->nullable();
                $table->unsignedBigInteger('recording_size')->nullable();
                $table->unsignedInteger('recording_duration_seconds')->nullable();
                $table->string('recording_sha256', 64)->nullable()->index();
                $table->timestamp('recording_deleted_at')->nullable();
                $table->boolean('retention_hold')->default(false);
                $table->boolean('consent_announced')->default(false);
                $table->decimal('cost', 10, 4)->nullable();
                $table->json('provider_payload')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['edition', 'realtor_user_id', 'created_at']);
                $table->index(
                    ['enterprise_id', 'enterprise_membership_id', 'created_at'],
                    'call_logs_enterprise_scope_idx'
                );
                $table->index(['lead_id', 'created_at']);
                $table->index(['initiated_by', 'status']);
            });
        } else {
            $this->ensureCallLogIndexes();
        }

        if (!Schema::hasTable('call_webhook_events')) {
            Schema::create('call_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 32);
                $table->string('event_id', 191);
                $table->foreignId('call_log_id')->nullable()->constrained('call_logs')->nullOnDelete();
                $table->string('provider_call_id', 191)->nullable();
                $table->string('event_type', 64);
                $table->string('status', 24)->default('processed');
                $table->json('payload')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'event_id']);
            });
        }

        if (!Schema::hasTable('call_recording_access_logs')) {
            Schema::create('call_recording_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('call_log_id')->constrained('call_logs')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 24);
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->timestamps();

                $table->index(['call_log_id', 'action']);
            });
        }
    }

    private function ensureCallLogIndexes(): void
    {
        $indexes = [
            ['call_logs_edition_realtor_user_id_created_at_index', ['edition', 'realtor_user_id', 'created_at']],
            ['call_logs_enterprise_scope_idx', ['enterprise_id', 'enterprise_membership_id', 'created_at']],
            ['call_logs_lead_id_created_at_index', ['lead_id', 'created_at']],
            ['call_logs_initiated_by_status_index', ['initiated_by', 'status']],
        ];

        foreach ($indexes as [$name, $columns]) {
            if (Schema::hasIndex('call_logs', $name)) {
                continue;
            }

            Schema::table('call_logs', function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('call_recording_access_logs');
        Schema::dropIfExists('call_webhook_events');
        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('user_call_profiles');
        Schema::dropIfExists('call_numbers');
    }
};
