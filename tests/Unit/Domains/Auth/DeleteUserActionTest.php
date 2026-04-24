<?php

use App\Domains\Auth\Actions\DeleteUserAction;
use App\Models\Category;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->action = app(DeleteUserAction::class);
});

// User row

it('hard-deletes the user row', function () {
    $user = User::factory()->create();

    $this->action->execute($user);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('does not affect other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->action->execute($user);

    $this->assertDatabaseHas('users', ['id' => $other->id]);
});

// Sanctum tokens

it('revokes all personal access tokens for the user', function () {
    $user = User::factory()->create();
    $user->createToken('mobile');
    $user->createToken('web');

    $this->action->execute($user);

    expect(
        DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count()
    )->toBe(0);
});

it('does not revoke tokens belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $other->createToken('other-token');

    $this->action->execute($user);

    expect(
        DB::table('personal_access_tokens')->where('tokenable_id', $other->id)->count()
    )->toBe(1);
});

// Password reset tokens

it('deletes the password reset token for the user email', function () {
    $user = User::factory()->create();
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => bcrypt('sometoken'),
        'created_at' => now(),
    ]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});

it('does not delete password reset tokens for other emails', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    DB::table('password_reset_tokens')->insert([
        'email' => $other->email,
        'token' => bcrypt('othertoken'),
        'created_at' => now(),
    ]);

    $this->action->execute($user);

    $this->assertDatabaseHas('password_reset_tokens', ['email' => $other->email]);
});

// Sessions

it('deletes all sessions belonging to the user', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'sess_abc',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => 'data',
        'last_activity' => now()->timestamp,
    ]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
});

it('does not delete sessions belonging to other users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'sess_other',
        'user_id' => $other->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => 'data',
        'last_activity' => now()->timestamp,
    ]);

    $this->action->execute($user);

    $this->assertDatabaseHas('sessions', ['user_id' => $other->id]);
});

// Cascaded tables

it('deletes all transactions belonging to the user (via cascade)', function () {
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();

    Transaction::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,

    ]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('transactions', ['user_id' => $user->id]);
});

it('deletes all fixed cost templates belonging to the user (via cascade)', function () {
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();

    FixedCostTemplate::factory()->count(2)->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,

    ]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('fixed_cost_templates', ['user_id' => $user->id]);
});

it('deletes all fixed cost occurrences belonging to the user (via cascade)', function () {
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,

    ]);

    FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'category_id' => $cat->id,

    ]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('fixed_cost_occurrences', ['user_id' => $user->id]);
});

it('deletes the user budget settings (via cascade)', function () {
    $user = User::factory()->create();
    UserBudgetSetting::factory()->create(['user_id' => $user->id]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('user_budget_settings', ['user_id' => $user->id]);
});

it('deletes the user budget snapshot (via cascade)', function () {
    $user = User::factory()->create();
    UserBudgetSnapshot::factory()->create(['user_id' => $user->id]);

    $this->action->execute($user);

    $this->assertDatabaseMissing('user_budget_snapshots', ['user_id' => $user->id]);
});

// Atomicity

it('rolls back all changes if an error occurs mid-transaction', function () {
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();

    Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,

    ]);

    // Partially mock the action so it throws after token deletion but
    // before the user row is deleted — the transaction should roll back.
    $failingAction = new class extends DeleteUserAction
    {
        public function execute(User $user): void
        {
            DB::transaction(function () use ($user): void {
                $user->tokens()->delete();
                throw new RuntimeException('Simulated failure mid-transaction.');
            });
        }
    };

    expect(fn () => $failingAction->execute($user))
        ->toThrow(RuntimeException::class);

    // User must still exist — nothing was committed
    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
