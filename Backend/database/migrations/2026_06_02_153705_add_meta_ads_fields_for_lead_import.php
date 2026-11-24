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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('meta_lead_id')->nullable()->after('source');
            $table->index('meta_lead_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('meta_lead_form_id')->nullable()->after('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('facebook_page_id')->nullable()->after('facebook_avatar');
            $table->text('facebook_page_access_token')->nullable()->after('facebook_page_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['meta_lead_id']);
            $table->dropColumn('meta_lead_id');
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('meta_lead_form_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['facebook_page_id', 'facebook_page_access_token']);
        });
    }
};
