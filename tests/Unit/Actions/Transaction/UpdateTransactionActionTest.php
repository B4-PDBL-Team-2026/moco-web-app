<?php

use App\Domains\Transactions\Actions\UpdateTransactionAction;
use App\Domains\Transactions\DTOs\UpdateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('updates transaction name and note only', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Old Name',
        'note' => 'Old Note',
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: true,
        name: 'New Name',
        amountProvided: false,
        amount: null,
        typeProvided: false,
        type: null,
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: true,
        note: 'New Note',
        transactionDateProvided: false,
        transactionDate: null,
    );

    $action = app(UpdateTransactionAction::class);

    $updated = $action->execute($user, $transaction, $dto);

    expect($updated->name)->toBe('New Name')
        ->and($updated->note)->toBe('New Note')
        ->and($user->fresh()->balance)->toBe('1000.00');
});

it('updates amount and recalculates balance', function () {
    $user = User::factory()->create([
        'balance' => '900.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: true,
        amount: '250.00',
        typeProvided: false,
        type: null,
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    $action = app(UpdateTransactionAction::class);

    $updated = $action->execute($user, $transaction, $dto);

    expect((string) $updated->amount)->toBe('250.00')
        ->and($user->fresh()->balance)->toBe('750.00');
});

it('updates type and recalculates balance', function () {
    $user = User::factory()->create([
        'balance' => '900.00',
    ]);

    $expenseCategory = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'expense tag',
        'type' => TransactionType::EXPENSE,
    ]);

    $incomeCategory = Category::factory()->create([
        'name' => 'income tag',
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        typeProvided: true,
        type: TransactionType::INCOME,
        categoryIdProvided: true,
        categoryId: $incomeCategory->id,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    $action = app(UpdateTransactionAction::class);

    $updated = $action->execute($user, $transaction, $dto);

    expect($updated->type)->toBe(TransactionType::INCOME)
        ->and($user->fresh()->balance)->toBe('1100.00');
});

it('updates transaction date', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_date' => '2026-03-01',
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        typeProvided: false,
        type: null,
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: true,
        transactionDate: CarbonImmutable::parse('2026-03-15'),
    );

    $action = app(UpdateTransactionAction::class);

    $updated = $action->execute($user, $transaction, $dto);

    expect($updated->transaction_date->toDateString())->toBe('2026-03-15');
});

it('throws validation exception when updated category type does not match updated type', function () {
    $user = User::factory()->create([
        'balance' => '1000.00',
    ]);

    $expenseCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE,
    ]);

    $incomeCategory = Category::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        typeProvided: true,
        type: TransactionType::EXPENSE,
        categoryIdProvided: true,
        categoryId: $incomeCategory->id,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    $action = app(UpdateTransactionAction::class);

    $action->execute($user, $transaction, $dto);
})->throws(ValidationException::class);

it('fails when user tries to update other users transaction', function () {
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

    $dto = new UpdateTransactionData(
        nameProvided: true,
        name: 'Hacked',
        amountProvided: false,
        amount: null,
        typeProvided: false,
        type: null,
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    $action = app(UpdateTransactionAction::class);

    $action->execute($user, $transaction, $dto);
})->throws(\Illuminate\Validation\UnauthorizedException::class);
