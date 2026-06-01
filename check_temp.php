<?php
use App\Domains\User\Models\User;

$users = User::all();
echo 'User count: ' . $users->count() . PHP_EOL;
foreach ($users as $u) {
    echo 'ID: ' . $u->id . ' | Email: ' . $u->email . ' | Onboarded: ' . ($u->has_onboarded ? 'yes' : 'no') . PHP_EOL;
}
