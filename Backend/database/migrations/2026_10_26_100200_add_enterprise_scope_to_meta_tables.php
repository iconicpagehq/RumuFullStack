<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meta_tokens', function (Blueprint $table) {
            $table->foreignId('enterprise_id')->nullable()->constrained()->nullOnDelete();
            $table->index('enterprise_id');
        });

        Schema::table('meta_pages', function (Blueprint $table) {
            $table->foreignId('enterprise_id')->nullable()->constrained()->nullOnDelete();
            $table->index('enterprise_id');
        });
    }

    public function down(): void
    {
        Schema::table('meta_tokens', function (Blueprint $table) {
            $table->dropForeign(['enterprise_id']);
            $table->dropIndex(['enterprise_id']);
            $table->dropColumn('enterprise_id');
        });

        Schema::table('meta_pages', function (Blueprint $table) {
            $table->dropForeign(['enterprise_id']);
            $table->dropIndex(['enterprise_id']);
            $table->dropColumn('enterprise_id');
        });
    }
};
