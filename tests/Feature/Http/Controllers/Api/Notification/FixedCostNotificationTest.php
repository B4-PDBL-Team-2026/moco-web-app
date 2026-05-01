<?php

use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\FixedCost\Notifications\FixedCostReminder;
use App\Domains\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

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

    $this->artisan('fixed-cost:remind')->assertSuccessful();

    Notification::assertSentTo($user, FixedCostReminder::class);
});
