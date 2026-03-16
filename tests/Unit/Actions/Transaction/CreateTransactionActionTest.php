<?php

use App\Actions\Transaction\CreateTransactionAction;
use App\DTOs\Transaction\CreateTransactionData;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('creates income transaction and updates user balance', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: 'Monthly salary',
        transactionDate: now()->toImmutable(),
    );

    $action = app(CreateTransactionAction::class);

    $transaction = $action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class);

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Salary',
        'amount' => '500.00',
        'type' => TransactionType::INCOME->value,
    ]);

    expect($user->fresh()->balance)->toBe('1500.00');
});

it('creates expense transaction and updates user balance', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Groceries',
        amount: '200.00',
        type: TransactionType::EXPENSE,
        note: 'Weekly groceries',
        transactionDate: now()->toImmutable(),
    );

    $action = app(CreateTransactionAction::class);

    $transaction = $action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($user->fresh()->balance)->toBe('800.00');
});

it('throws validation exception when category type does not match transaction type', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: now()->toImmutable(),
    );

    $action = app(CreateTransactionAction::class);

    $action->execute($user, $dto);
})->throws(ValidationException::class);

it('fails when category does not belong to user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $category = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::INCOME,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: now()->toImmutable(),
    );

    $action = app(CreateTransactionAction::class);

    $action->execute($user, $dto);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
