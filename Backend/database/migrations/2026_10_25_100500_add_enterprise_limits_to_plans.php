<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('audience')->default('realtor')->after('name')->index();
            $table->unsignedInteger('max_managers')->nullable()->after('max_leads_per_campaign');
            $table->unsignedInteger('max_employees')->nullable()->after('max_managers');
            $table->unsignedInteger('max_teams')->nullable()->after('max_employees');
            $table->unsignedInteger('max_monthly_leads')->nullable()->after('max_teams');
            $table->unsignedInteger('max_inventory_units')->nullable()->after('max_monthly_leads');
            $table->boolean('advanced_reports')->default(false)->after('max_inventory_units');
            $table->boolean('meta_integrations')->default(false)->after('advanced_reports');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'audience',
                'max_managers',
                'max_employees',
                'max_teams',
                'max_monthly_leads',
                'max_inventory_units',
                'advanced_reports',
                'meta_integrations',
            ]);
        });
    }
};
