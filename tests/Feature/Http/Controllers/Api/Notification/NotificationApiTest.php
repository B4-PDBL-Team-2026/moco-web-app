<?php

use App\Models\Category;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\User;
use App\Notifications\FixedCostReminder;
use Laravel\Sanctum\Sanctum;

function createDummyOccurrence(User $user): FixedCostOccurrence
{
    $category = Category::factory()->expense()->create();

    $template = FixedCostTemplate::create([
        'user_id' => $user->id,
        'name' => 'Test Bill',
        'amount' => 1000,
        'due_day' => 10,
        'cycle_type' => 'monthly',
        'category_id' => $category->id,
    ]);

    return FixedCostOccurrence::create([
        'fixed_cost_template_id' => $template->id,
        'user_id' => $user->id,
        'name' => 'Test Bill March',
        'amount' => 1000,
        'due_date' => now(),
        'status' => 'pending',
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'category_id' => $category->id,
    ]);
}

test('unauthenticated user cannot access notifications', function () {
    $this->getJson('/api/notifications')->assertStatus(401);
});

test('user can get their notification list', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new FixedCostReminder(createDummyOccurrence($user)));

    $this->getJson('/api/notifications')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => ['data', 'current_page'],
            'message',
        ]);
});

test('user can mark notification as read', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $user->notify(new FixedCostReminder(createDummyOccurrence($user)));
    $notification = $user->unreadNotifications->first();

    $this->postJson("/api/notifications/{$notification->id}/read")
        ->assertStatus(200);

    expect($user->fresh()->unreadNotifications->count())->toBe(0);
});
