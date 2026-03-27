<?php

namespace Tests\Feature\Http\Controllers\Api\Profile;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_display_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'display_name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.display_name', 'New Name');

        $this->assertDatabaseHas('user_profiles', [
            'user_id'      => $user->id,
            'display_name' => 'New Name',
        ]);
    }

    public function test_user_can_update_currency(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'currency' => 'USD',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.currency', 'USD');
    }

    public function test_partial_update_does_not_overwrite_untouched_fields(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id'      => $user->id,
            'display_name' => 'Original Name',
            'currency'     => 'IDR',
        ]);

        $this->actingAs($user)->patchJson('/api/profile', [
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id'      => $user->id,
            'display_name' => 'Original Name',
            'currency'     => 'USD',
        ]);
    }

    public function test_invalid_currency_format_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'currency' => 'INVALID',
        ]);

        $response->assertUnprocessable();
    }

    public function test_invalid_avatar_url_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patchJson('/api/profile', [
            'avatar_url' => 'not-a-url',
        ]);

        $response->assertUnprocessable();
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->patchJson('/api/profile', [
            'display_name' => 'Hacker',
        ]);

        $response->assertUnauthorized();
    }
}