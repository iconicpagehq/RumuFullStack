<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'assawnat100@gmail.com';
$user = \App\Models\User::where('email', $email)->first();

if ($user) {
    \DB::table('plan_upgrade_requests')->where('user_id', $user->id)->delete();
    \App\Models\Subscription::where('user_id', $user->id)->where('status', 'pending')->delete();
    echo "Successfully deleted pending requests and subscriptions for {$email}\n";
} else {
    echo "User not found with email {$email}\n";
}
