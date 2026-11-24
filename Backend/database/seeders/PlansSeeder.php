<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Basic Plan (if not exists)
        Plan::firstOrCreate(
            ['key' => 'basic'],
            [
                'name' => 'Basic',
                'max_campaigns' => 5,
                'max_leads_per_campaign' => 100,
                'is_default' => true,
                'price' => 0.00,
                'sort_order' => 1,
            ]
        );

        // Create Pro Plan (if not exists)
        Plan::firstOrCreate(
            ['key' => 'pro'],
            [
                'name' => 'Pro',
                'max_campaigns' => null, // Unlimited
                'max_leads_per_campaign' => null, // Unlimited
                'is_default' => false,
                'price' => 999.00,
                'sort_order' => 2,
            ]
        );
        
        $this->command->info('Plans seeded successfully!');
    }
}
