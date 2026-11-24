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
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('invoice_number');
            $table->index('status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('gateway_payment_id');
            $table->index('payment_status');
            $table->index('transaction_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['invoice_number']);
            $table->dropIndex(['status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['gateway_payment_id']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['transaction_id']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
    }
};
