<?php

namespace Tests\Unit\Domains\Profile;

use App\Domains\User\Actions\Profile\UpdateProfileAction;
use App\Domains\User\DTOs\Profile\UpdateProfileData;
use App\Domains\User\Models\User;
use App\Domains\User\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateProfileActionTest extends TestCase
{
    use RefreshDatabase;

    private UpdateProfileAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new UpdateProfileAction;
    }

    public function test_it_creates_profile_when_none_exists(): void
    {
        $user = User::factory()->create();
        $dto = new UpdateProfileData(
            displayName: 'New User',
            avatarUrl: null,
            currency: null,
            locale: null,
        );

        $profile = $this->action->execute($user->id, $dto);

        $this->assertInstanceOf(UserProfile::class, $profile);
        $this->assertEquals('New User', $profile->display_name);
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'display_name' => 'New User',
        ]);
    }

    public function test_it_updates_only_provided_fields(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create([
            'user_id' => $user->id,
            'display_name' => 'Old Name',
            'currency' => 'IDR',
        ]);

        $dto = new UpdateProfileData(
            displayName: null,
            avatarUrl: null,
            currency: 'USD',
            locale: null,
        );

        $profile = $this->action->execute($user->id, $dto);

        // display_name must be untouched
        $this->assertEquals('Old Name', $profile->display_name);
        $this->assertEquals('USD', $profile->currency);
    }

    public function test_it_updates_existing_profile(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'display_name' => 'Old Name']);

        $dto = new UpdateProfileData(
            displayName: 'Updated Name',
            avatarUrl: null,
            currency: null,
            locale: null,
        );

        $profile = $this->action->execute($user->id, $dto);

        $this->assertEquals('Updated Name', $profile->display_name);
        $this->assertDatabaseCount('user_profiles', 1);
    }
}
