<?php

namespace Tests\Feature\Http\Controllers\Api\Profile;

use App\Domains\User\Models\User;
use App\Domains\User\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_retrieve_their_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'display_name' => 'John Doe']);

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertOk()
            ->assertJsonPath('data.profile.display_name', 'John Doe');
    }

    public function test_unauthenticated_user_cannot_retrieve_profile(): void
    {
        $response = $this->getJson('/api/user/profile');

        $response->assertUnauthorized();
    }

    public function test_profile_is_returned_even_when_no_profile_row_exists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertOk();
    }
}
