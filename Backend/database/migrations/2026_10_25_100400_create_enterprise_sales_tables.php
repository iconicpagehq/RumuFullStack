<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['enterprise_id', 'lead_id', 'created_at']);
            $table->index(['enterprise_id', 'type', 'created_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium');
            $table->timestamp('due_at')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('completion_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enterprise_id', 'assigned_to', 'status']);
            $table->index(['enterprise_id', 'due_at']);
        });

        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_project_id')->nullable()->constrained('inventory_projects')->nullOnDelete();
            $table->foreignId('inventory_unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('status')->default('scheduled')->index();
            $table->boolean('customer_attended')->nullable();
            $table->text('outcome')->nullable();
            $table->text('feedback')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['enterprise_id', 'scheduled_at']);
            $table->index(['enterprise_id', 'assigned_to', 'status']);
        });

        Schema::create('sales_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_month');
            $table->string('metric');
            $table->decimal('target_value', 15, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['enterprise_id', 'period_month', 'metric']);
            $table->index(['enterprise_id', 'team_id', 'period_month'], 'sales_targets_enterprise_team_month_index');
            $table->index(['enterprise_id', 'user_id', 'period_month'], 'sales_targets_enterprise_user_month_index');
        });

        Schema::create('enterprise_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enterprise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['enterprise_id', 'created_at']);
            $table->index(['enterprise_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enterprise_audit_logs');
        Schema::dropIfExists('sales_targets');
        Schema::dropIfExists('site_visits');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('lead_activities');
    }
};
