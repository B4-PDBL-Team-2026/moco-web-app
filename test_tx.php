<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Domains\User\Models\User;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;

$user = User::first();
echo "User: " . ($user->email ?? 'none') . "\n";
echo "Has onboarded: " . ($user->has_onboarded ? 'yes' : 'no') . "\n";

$settings = UserBudgetSetting::where('user_id', $user->id)->first();
echo "Budget settings: " . ($settings ? 'yes' : 'no') . "\n";

$snapshot = UserBudgetSnapshot::where('user_id', $user->id)->first();
echo "Budget snapshot: " . ($snapshot ? 'yes (balance: ' . $snapshot->current_balance . ')' : 'no') . "\n";
