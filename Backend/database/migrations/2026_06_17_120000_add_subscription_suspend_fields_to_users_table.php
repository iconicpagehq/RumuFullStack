<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('subscription_suspended_at')->nullable()->after('plan_id');
            $table->foreignId('suspended_plan_id')->nullable()->after('subscription_suspended_at')->constrained('plans')->nullOnDelete();
            $table->string('subscription_suspend_reason', 500)->nullable()->after('suspended_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['suspended_plan_id']);
            $table->dropColumn(['subscription_suspended_at', 'suspended_plan_id', 'subscription_suspend_reason']);
        });
    }
};
