<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlansSeeder::class,    // Pehle Plans (kyunki Admin ko Pro plan chahiye)
            EnterprisePlanSeeder::class,
            AdminSeeder::class,    // Phir Admin user
        ]);

        // Optional: Test user create karna ho toh uncomment karo
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
