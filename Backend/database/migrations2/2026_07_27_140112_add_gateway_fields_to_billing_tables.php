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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway')->nullable()->after('currency');
            $table->string('gateway_payment_id')->nullable()->after('gateway');
            $table->string('gateway_order_id')->nullable()->after('gateway_payment_id');
            $table->string('gateway_signature')->nullable()->after('gateway_order_id');
            $table->json('webhook_payload')->nullable()->after('gateway_signature');
            $table->timestamp('verified_at')->nullable()->after('payment_date');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'gateway',
                'gateway_payment_id',
                'gateway_order_id',
                'gateway_signature',
                'webhook_payload',
                'verified_at'
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('activated_at');
        });
    }
};
