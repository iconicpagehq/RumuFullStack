<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--name=Admin : Admin name}
                            {--email=admin@rumu.in : Admin email}
                            {--password=Rumu1100@ : Admin password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        // Check if user already exists
        if (User::where('email', $email)->exists()) {
            $this->error("User with email '$email' already exists!");
            return;
        }

        // Get Pro plan
        $proPlan = Plan::where('name', 'Pro')->first();

        // Create admin user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'role' => 'admin',
            'plan_id' => $proPlan?->id,
            'password' => Hash::make($password),
        ]);

        $this->info("Admin user created successfully!");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
                ['Password', $password],
            ]
        );
    }
}
