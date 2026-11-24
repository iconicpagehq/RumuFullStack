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
            $table->string('status')->default('connected')->after('access_token');
            $table->dateTime('token_last_synced_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meta_pages', function (Blueprint $table) {
            $table->dropColumn(['status', 'token_last_synced_at']);
        });
    }
};
