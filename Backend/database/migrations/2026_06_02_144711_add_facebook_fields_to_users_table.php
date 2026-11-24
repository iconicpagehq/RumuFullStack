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
        Schema::table('users', function (Blueprint $table) {
            $table->string('facebook_id')->nullable()->after('google_avatar');
            $table->string('facebook_avatar')->nullable()->after('facebook_id');

            $table->index('facebook_id');
            $table->index('facebook_avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['facebook_id']);
            $table->dropIndex(['facebook_avatar']);
            $table->dropColumn(['facebook_id', 'facebook_avatar']);
        });
    }
};
