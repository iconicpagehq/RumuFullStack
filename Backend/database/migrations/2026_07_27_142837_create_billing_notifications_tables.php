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
        Schema::create('billing_notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->unique(); // e.g., 'subscription_activated', 'payment_successful'
            $table->string('subject');
            $table->longText('body_content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('billing_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // matches template type
            
            // Optional relations depending on the event
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            
            $table->enum('channel', ['email', 'in_app', 'both'])->default('both');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_notifications');
        Schema::dropIfExists('billing_notification_templates');
    }
};
