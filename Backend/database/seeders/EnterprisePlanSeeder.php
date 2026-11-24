<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class EnterprisePlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['key' => 'enterprise-starter'],
            [
                'name' => 'Enterprise Starter',
                'audience' => 'enterprise',
                'max_campaigns' => 5,
                'max_leads_per_campaign' => null,
                'max_managers' => 2,
                'max_employees' => 15,
                'max_teams' => 3,
                'max_monthly_leads' => 500,
                'max_inventory_units' => 200,
                'advanced_reports' => false,
                'meta_integrations' => false,
                'is_default' => false,
                'is_active' => true,
                'price' => 4999.00,
                'sort_order' => 10,
                'header_color' => '#650052',
            ]
        );

        Plan::updateOrCreate(
            ['key' => 'enterprise-growth'],
            [
                'name' => 'Enterprise Growth',
                'audience' => 'enterprise',
                'max_campaigns' => null,
                'max_leads_per_campaign' => null,
                'max_managers' => 10,
                'max_employees' => 100,
                'max_teams' => 20,
                'max_monthly_leads' => 5000,
                'max_inventory_units' => 2000,
                'advanced_reports' => true,
                'meta_integrations' => true,
                'is_default' => false,
                'is_active' => true,
                'price' => 14999.00,
                'sort_order' => 11,
                'header_color' => '#8a006b',
            ]
        );

        if ($this->command) {
            $this->command->info('Enterprise plans seeded successfully.');
        }
    }
}
