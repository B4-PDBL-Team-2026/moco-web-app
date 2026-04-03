<?php

uses(RefreshDatabase::class);
uses(TestCase::class)->in('Unit');

use App\Domains\Transactions\Actions\DeleteTransactionAction;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\UnauthorizedException;
use Tests\TestCase;

it('deletes expense transaction', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '800.00',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '200.00',
    ]);

    app(DeleteTransactionAction::class)->execute($user, $transaction);

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});

it('deletes income transaction when balance remains non-negative', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1500.00',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::INCOME,
        'amount' => '500.00',
    ]);

    app(DeleteTransactionAction::class)->execute($user, $transaction);

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
});

it('fails when user tries to delete other users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $otherUser->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $otherUser->id,
        'current_balance' => '500.00',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    app(DeleteTransactionAction::class)->execute($user, $transaction);

})->throws(UnauthorizedException::class);
