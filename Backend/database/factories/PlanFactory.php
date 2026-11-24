<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'key' => 'pro',
            'name' => 'Pro',
            'max_campaigns' => null,
            'max_leads_per_campaign' => null,
            'is_default' => false,
            'is_active' => true,
            'price' => null,
        ];
    }

    public function basic(): static
    {
        return $this->state(fn () => [
            'key' => 'basic',
            'name' => 'Basic',
            'max_campaigns' => 5,
            'max_leads_per_campaign' => 100,
            'is_default' => true,
            'price' => 0,
        ]);
    }
}
