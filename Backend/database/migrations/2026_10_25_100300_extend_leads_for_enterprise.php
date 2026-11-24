<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->change();

            $table->foreignId('enterprise_id')->nullable()->after('campaign_id')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('enterprise_id')->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_manager_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->foreignId('inventory_project_id')->nullable()->after('inventory_unit_id')->constrained('inventory_projects')->nullOnDelete();

            $table->string('enterprise_stage')->nullable()->after('status')->index();
            $table->string('priority')->default('medium')->after('enterprise_stage');
            $table->decimal('budget_min', 15, 2)->nullable();
            $table->decimal('budget_max', 15, 2)->nullable();
            $table->string('preferred_location')->nullable();
            $table->string('preferred_configuration')->nullable();
            $table->timestamp('next_follow_up_at')->nullable()->index();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->decimal('deal_value', 15, 2)->nullable();
            $table->text('lost_reason')->nullable();
            $table->timestamp('won_at')->nullable();

            $table->index(['enterprise_id', 'enterprise_stage']);
            $table->index(['enterprise_id', 'assigned_to', 'enterprise_stage'], 'leads_enterprise_assignee_stage_index');
            $table->index(['enterprise_id', 'team_id', 'enterprise_stage'], 'leads_enterprise_team_stage_index');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_enterprise_assignee_stage_index');
            $table->dropIndex('leads_enterprise_team_stage_index');
            $table->dropConstrainedForeignId('inventory_project_id');
            $table->dropConstrainedForeignId('assigned_manager_id');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('team_id');
            $table->dropConstrainedForeignId('enterprise_id');
            $table->dropColumn([
                'enterprise_stage', 'priority', 'budget_min', 'budget_max',
                'preferred_location', 'preferred_configuration',
                'next_follow_up_at', 'last_contacted_at', 'assigned_at',
                'deal_value', 'lost_reason', 'won_at',
            ]);
        });
    }
};
