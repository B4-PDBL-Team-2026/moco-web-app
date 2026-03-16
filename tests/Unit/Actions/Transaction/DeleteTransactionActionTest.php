<?php

use App\Actions\Transaction\DeleteTransactionAction;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

it('deletes expense transaction and restores user balance', function () {
    $user = User::factory()->create([
        'balance' => '800.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '200.00',
    ]);

    $action = app(DeleteTransactionAction::class);

    $action->execute($user, $transaction);

    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
    ]);

    expect($user->fresh()->balance)->toBe('1000.00');
});

it('deletes income transaction and reduces user balance', function () {
    $user = User::factory()->create([
        'balance' => '1500.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => TransactionType::INCOME,
        'amount' => '500.00',
    ]);

    $action = app(DeleteTransactionAction::class);

    $action->execute($user, $transaction);

    expect($user->fresh()->balance)->toBe('1000.00');
});

it('fails when user tries to delete other users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $category->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    $action = app(DeleteTransactionAction::class);

    $action->execute($user, $transaction);
})->throws(\Illuminate\Validation\UnauthorizedException::class);
