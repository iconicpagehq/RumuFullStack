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
        Schema::table('meta_campaign_forms', function (Blueprint $table) {
            $table->dateTime('last_webhook_received_at')->nullable()->after('form_name');
            $table->dateTime('last_manual_sync_at')->nullable()->after('last_webhook_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_campaign_forms', function (Blueprint $table) {
            $table->dropColumn(['last_webhook_received_at', 'last_manual_sync_at']);
        });
    }
};
