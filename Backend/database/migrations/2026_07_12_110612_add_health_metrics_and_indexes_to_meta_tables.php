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
        Schema::table('meta_pages', function (Blueprint $table) {
            $table->unsignedInteger('consecutive_failures')->default(0)->after('token_last_synced_at');
            $table->timestamp('last_successful_api_request_at')->nullable()->after('consecutive_failures');
            $table->timestamp('last_failed_api_request_at')->nullable()->after('last_successful_api_request_at');

            // Add indexes
            $table->index('user_id');
            $table->index('page_id');
        });

        Schema::table('meta_campaign_forms', function (Blueprint $table) {
            $table->index('campaign_id');
            $table->index('page_id');
        });

        Schema::table('meta_sync_logs', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('campaign_id');
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_sync_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['campaign_id']);
            $table->dropIndex(['event_type']);
        });

        Schema::table('meta_campaign_forms', function (Blueprint $table) {
            $table->dropIndex(['campaign_id']);
            $table->dropIndex(['page_id']);
        });

        Schema::table('meta_pages', function (Blueprint $table) {
            $table->dropColumn([
                'consecutive_failures',
                'last_successful_api_request_at',
                'last_failed_api_request_at',
            ]);

            $table->dropIndex(['user_id']);
            $table->dropIndex(['page_id']);
        });
    }
};
