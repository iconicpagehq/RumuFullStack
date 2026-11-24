<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::where('name', 'like', '%Atharva%')
    ->orWhere('email', 'like', '%assawnat%')
    ->orWhere('email', 'like', '%assawant%')
    ->get();

foreach ($users as $user) {
    \DB::table('plan_upgrade_requests')->where('user_id', $user->id)->delete();
    \App\Models\Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();
    echo "Deleted pending requests and subscriptions for {$user->email} (ID: {$user->id})\n";
}
