<?php

namespace Tests\Unit\Domains\Profile;

use App\Domains\User\Actions\Profile\GetProfileAction;
use App\Domains\User\Models\User;
use App\Domains\User\Models\UserProfile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetProfileActionTest extends TestCase
{
    use RefreshDatabase;

    private GetProfileAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new GetProfileAction;
    }

    public function test_it_returns_user_with_profile_relationship(): void
    {
        $user = User::factory()->create();
        UserProfile::factory()->create(['user_id' => $user->id, 'display_name' => 'Tester']);

        $result = $this->action->execute($user->id);

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->relationLoaded('profile'));
        $this->assertEquals('Tester', $result->profile->display_name);
    }

    public function test_it_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->action->execute(99999);
    }
}
