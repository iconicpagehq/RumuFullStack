<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mcp_oauth_clients')) {
            Schema::create('mcp_oauth_clients', function (Blueprint $table) {
                $table->id();
                $table->string('client_id')->unique();
                $table->string('name');
                $table->json('redirect_uris');
                $table->json('grant_types')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('mcp_oauth_authorization_codes')) {
            Schema::create('mcp_oauth_authorization_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code_hash', 64)->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('client_id')->index();
                $table->text('redirect_uri');
                $table->string('scopes')->nullable();
                $table->string('code_challenge', 128);
                $table->string('code_challenge_method', 10)->default('S256');
                $table->timestamp('expires_at');
                $table->timestamp('consumed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_oauth_authorization_codes');
        Schema::dropIfExists('mcp_oauth_clients');
    }
};
