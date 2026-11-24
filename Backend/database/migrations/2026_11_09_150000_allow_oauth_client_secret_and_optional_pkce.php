<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mcp_oauth_clients')) {
            if (! Schema::hasColumn('mcp_oauth_clients', 'client_secret')) {
                Schema::table('mcp_oauth_clients', function (Blueprint $table) {
                    $table->string('client_secret')->nullable()->after('name');
                });
            }
        }

        if (Schema::hasTable('mcp_oauth_authorization_codes')) {
            // Make code_challenge and code_challenge_method nullable for standard OAuth confidential clients (like ChatGPT)
            try {
                DB::statement('ALTER TABLE mcp_oauth_authorization_codes MODIFY code_challenge VARCHAR(128) NULL');
                DB::statement('ALTER TABLE mcp_oauth_authorization_codes MODIFY code_challenge_method VARCHAR(10) NULL');
            } catch (\Throwable $e) {
                // If already nullable or syntax differs, continue safely
            }
        }

        // Pre-register ChatGPT Custom GPT OAuth client
        \App\Models\McpOAuthClient::updateOrCreate(
            ['client_id' => 'rumu-chatgpt'],
            [
                'name' => 'ChatGPT Custom GPT',
                'client_secret' => 'rumu_sec_chatgpt_948f2a1b7e6c5d0e',
                'redirect_uris' => [
                    'https://chat.openai.com/aip/*',
                    'https://chatgpt.com/aip/*',
                    'https://chatgpt.com/*',
                ],
                'grant_types' => ['authorization_code'],
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('mcp_oauth_clients') && Schema::hasColumn('mcp_oauth_clients', 'client_secret')) {
            Schema::table('mcp_oauth_clients', function (Blueprint $table) {
                $table->dropColumn('client_secret');
            });
        }
    }
};
