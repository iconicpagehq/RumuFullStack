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
        Schema::create('meta_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('meta_user_id');
            $table->text('access_token');
            $table->timestamps();
        });

        Schema::create('meta_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('page_id');
            $table->string('page_name');
            $table->text('access_token');
            $table->timestamps();
        });

        Schema::create('meta_campaign_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('page_id');
            $table->string('form_id');
            $table->string('form_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meta_campaign_forms');
        Schema::dropIfExists('meta_pages');
        Schema::dropIfExists('meta_tokens');
    }
};
