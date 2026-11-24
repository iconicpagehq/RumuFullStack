<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'atharvasawant020@gmail.com')->first();
if ($user) {
    $sub = App\Models\Subscription::where('user_id', $user->id)->latest()->first();
    if ($sub) {
        $oldPlan = $sub->plan_name;
        $sub->plan_name = 'Basic';
        $sub->save();
        echo "Successfully converted user's subscription from {$oldPlan} back to the Basic plan.\n";
    } else {
        echo "User does not have an active subscription record to update.\n";
    }
} else {
    echo "Could not find a user with the email atharvasawant020@gmail.com.\n";
}
