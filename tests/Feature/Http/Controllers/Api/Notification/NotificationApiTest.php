<?php

namespace Tests\Feature\Http\Controllers\Api\Notification;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\FixedCostReminder;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }

    public function test_user_can_get_their_notification_list()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $user->notify(new FixedCostReminder($this->createDummyOccurrence($user)));

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['data', 'current_page'],
                     'message'
                 ]);
    }

    public function test_user_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $user->notify(new FixedCostReminder($this->createDummyOccurrence($user)));
        $notification = $user->unreadNotifications->first();

        $response = $this->postJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertEquals(0, $user->fresh()->unreadNotifications->count());
    }

       private function createDummyOccurrence($user)
    {
        $template = FixedCostTemplate::create([
            'user_id' => $user->id,
            'name' => 'Test Bill',
            'amount' => 1000,
            'due_day' => 10,
            'cycle_type' => 'monthly',
            'category_type' => 'expense',
            'category_id' => 1
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
            'category_type' => 'expense',
            'category_id' => 1
        ]);
    }
}
