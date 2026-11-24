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
        Schema::table('plans', function (Blueprint $table) {
            $table->string('key')->nullable()->unique()->after('id');
        });

        // Populate key column for existing plans
        DB::table('plans')->where('name', 'Basic')->update(['key' => 'basic']);
        DB::table('plans')->where('name', 'Pro')->update(['key' => 'pro']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }
};
