<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => '9876543210',
            'company' => fake()->optional()->company(),
            'status' => 'new',
            'source' => 'manual',
            'meta_lead_id' => null,
            'custom_fields' => null,
        ];
    }

    public function metaAds(): static
    {
        return $this->state(fn () => [
            'source' => 'meta_ads',
            'meta_lead_id' => (string) fake()->unique()->numerify('##############'),
            'custom_fields' => ['lead_source' => 'facebook'],
        ]);
    }
}
