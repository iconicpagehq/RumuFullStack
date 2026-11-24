<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('social_conversations', 'participant_avatar')) {
                $table->text('participant_avatar')->nullable()->after('participant_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('social_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('social_conversations', 'participant_avatar')) {
                $table->dropColumn('participant_avatar');
            }
        });
    }
};

