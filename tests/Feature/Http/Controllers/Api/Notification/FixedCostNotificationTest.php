<?php

use App\Domains\FixedCosts\Notifications\FixedCostReminder;
use App\Models\Category;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('notification is sent on due date', function () {
    Notification::fake();

    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

    $template = FixedCostTemplate::create([
        'user_id' => $user->id,
        'name' => 'Motorcycle Installment',
        'amount' => 1500000,
        'due_day' => 25,
        'cycle_type' => 'monthly',
        'category_id' => $category->id,
    ]);

    FixedCostOccurrence::create([
        'fixed_cost_template_id' => $template->id,
        'user_id' => $user->id,
        'name' => 'Motorcycle Installment - March',
        'amount' => 1500000,
        'due_date' => Carbon::today(),
        'status' => 'pending',
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'category_id' => $category->id,
    ]);

    $this->artisan('fixed-cost:remind');

    Notification::assertSentTo($user, FixedCostReminder::class);
});
