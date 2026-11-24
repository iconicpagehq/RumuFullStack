<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get Pro plan
        $proPlan = Plan::where('name', 'Pro')->first();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@rumu.in',
            'role' => 'admin',
            'plan_id' => $proPlan?->id,
            'password' => Hash::make('Rumu1100@'),
        ]);
    }
}
