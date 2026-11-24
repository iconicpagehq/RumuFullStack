<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_conversations', function (Blueprint $table) {
            $table->string('external_id', 191)->change();
            $table->string('participant_id', 191)->change();
        });

        Schema::table('social_messages', function (Blueprint $table) {
            $table->string('external_id', 191)->nullable()->change();
            $table->string('sender_id', 191)->change();
            $table->string('recipient_id', 191)->change();
        });
    }

    public function down(): void
    {
        Schema::table('social_conversations', function (Blueprint $table) {
            $table->string('external_id', 100)->change();
            $table->string('participant_id', 100)->change();
        });

        Schema::table('social_messages', function (Blueprint $table) {
            $table->string('external_id', 100)->nullable()->change();
            $table->string('sender_id', 100)->change();
            $table->string('recipient_id', 100)->change();
        });
    }
};

