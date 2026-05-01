<?php

use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

// Helpers

function userWithPassword(string $password = 'secret1234'): User
{
    return User::factory()->create(['password' => Hash::make($password)]);
}

// Authentication

test('unauthenticated request returns 401', function () {
    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])
        ->assertUnauthorized();
});

// Validation

test('returns 422 when password is missing', function () {
    Sanctum::actingAs(userWithPassword());

    $this->deleteJson('/api/auth/user')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('returns 422 when password is incorrect', function () {
    Sanctum::actingAs(userWithPassword());

    $this->deleteJson('/api/auth/user', ['password' => 'wrongpassword'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

test('returns 422 with the correct error message when password is wrong', function () {
    Sanctum::actingAs(userWithPassword());

    $this->deleteJson('/api/auth/user', ['password' => 'wrongpassword'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password'])
        ->assertJsonFragment(['The provided password is incorrect.']);
});

// Success

test('returns 200 with success message on valid deletion', function () {
    Sanctum::actingAs(userWithPassword());

    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])
        ->assertOk()
        ->assertJsonPath('message', 'Account deleted successfully.');
});

test('removes the user row from the database', function () {
    $user = userWithPassword();
    Sanctum::actingAs($user);

    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('revokes all sanctum tokens after deletion', function () {
    $user = userWithPassword();
    $user->createToken('mobile');
    $user->createToken('web');
    Sanctum::actingAs($user);

    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    expect(
        DB::table('personal_access_tokens')->where('tokenable_id', $user->id)->count()
    )->toBe(0);
});

test('deletes sessions for the user', function () {
    $user = userWithPassword();
    DB::table('sessions')->insert([
        'id' => 'sess_delete_test',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'PHPUnit',
        'payload' => 'data',
        'last_activity' => now()->timestamp,
    ]);
    Sanctum::actingAs($user);

    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
});

test('deletes password reset tokens for the user email', function () {
    $user = userWithPassword();
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => bcrypt('token'),
        'created_at' => now(),
    ]);
    Sanctum::actingAs($user);

    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});

test('deletes all transactions belonging to the user', function () {
    $user = userWithPassword();
    $cat = Category::factory()->expense()->create();

    Transaction::factory()->count(3)->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,
    ]);

    Sanctum::actingAs($user);
    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseMissing('transactions', ['user_id' => $user->id]);
});

test('deletes all fixed cost templates and their occurrences', function () {
    $user = userWithPassword();
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

    Sanctum::actingAs($user);
    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseMissing('fixed_cost_templates', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('fixed_cost_occurrences', ['user_id' => $user->id]);
});

test('deletes budget settings and snapshot', function () {
    $user = userWithPassword();
    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);
    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseMissing('user_budget_settings', ['user_id' => $user->id]);
    $this->assertDatabaseMissing('user_budget_snapshots', ['user_id' => $user->id]);
});

// Isolation — other users unaffected

test('does not delete other users', function () {
    $user = userWithPassword();
    $other = User::factory()->create();

    Sanctum::actingAs($user);
    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseHas('users', ['id' => $other->id]);
});

test('does not delete transactions belonging to other users', function () {
    $user = userWithPassword();
    $other = User::factory()->create();
    $cat = Category::factory()->expense()->create();

    $otherTx = Transaction::factory()->create([
        'user_id' => $other->id,
        'category_id' => $cat->id,

    ]);

    Sanctum::actingAs($user);
    $this->deleteJson('/api/auth/user', ['password' => 'secret1234'])->assertOk();

    $this->assertDatabaseHas('transactions', ['id' => $otherTx->id]);
});
