<?php

use App\Domains\Notification\Actions\DeleteNotificationByIdAction;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

it('successfully deletes a notification owned by the user', function () {
    $user = User::factory()->create();

    $notification = $user->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'App\Notifications\FixedCostReminder',
        'data' => ['message' => 'Tagihan Netflix'],
    ]);

    $action = app(DeleteNotificationByIdAction::class);
    $result = $action->execute($user, $notification->id);

    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('notifications', [
        'id' => $notification->id,
    ]);
});

it('throws ModelNotFoundException if notification does not exist or belongs to another user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $notificationUser2 = $user2->notifications()->create([
        'id' => Str::uuid(),
        'type' => 'App\Notifications\FixedCostReminder',
        'data' => ['message' => 'Tagihan Kos'],
    ]);

    $action = app(DeleteNotificationByIdAction::class);

    $action->execute($user1, $notificationUser2->id);
})->throws(ModelNotFoundException::class);
