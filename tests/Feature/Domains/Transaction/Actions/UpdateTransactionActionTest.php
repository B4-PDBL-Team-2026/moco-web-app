<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\UpdateTransactionAction;
use App\Domains\Transaction\DTOs\UpdateTransactionData;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Illuminate\Validation\UnauthorizedException;

it('successfully updates transaction name and note only', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = Category::factory()->expense()->create();

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
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: true,
        note: 'New Note',
        transactionAtProvided: false,
        transactionAt: null,
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->name)->toBe('New Name')
        ->and($updated->note)->toBe('New Note');
});

it('successfully updates transaction amount', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '900.00',
    ]);

    $category = Category::factory()->expense()->create();

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
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect((string) $updated->amount)->toBe('250.00');
});

it('correctly updates transaction date and converts to UTC', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = Category::factory()->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_at' => '2026-03-01 10:00:00',
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'transactionAt' => '2026-03-15T15:00:00+07:00',
    ]);

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->transaction_at->toDateTimeString())->toBe('2026-03-15 08:00:00');
});

it('successfully updates transaction with system category', function () {
    $user = User::factory()->create();
    $oldCategory = Category::factory()->expense()->create();
    $newCategory = Category::factory()->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $oldCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'categoryId' => $newCategory->id,
        'categoryType' => 'system',
    ]);

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->category_id)->toBe($newCategory->id);
});

it('successfully updates transaction with custom category belonging to user', function () {
    $user = User::factory()->create();
    $sysCategory = Category::factory()->expense()->create();

    $customCategory = Category::factory()->custom($user)->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $sysCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'categoryId' => $customCategory->id,
        'categoryType' => 'custom',
    ]);

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->category_id)->toBe($customCategory->id);
});

it('throws validation exception if amount update causes negative balance', function () {
    $user = User::factory()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '50.00',
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '50.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = UpdateTransactionData::fromArray(['amount' => '200.00']);

    expect(fn () => app(UpdateTransactionAction::class)->execute($user, $transaction, $dto))
        ->toThrow(BusinessRuleException::class);
});

it('throws exception when updating to a future date', function () {
    [$user, $category] = setupUserWithBudget();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'transactionAt' => now()->addDay()->toIso8601String(),
    ]);

    expect(fn () => app(UpdateTransactionAction::class)->execute($user, $transaction, $dto))
        ->toThrow(BusinessRuleException::class);
});

it('throws validation exception if custom category belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $sysCategory = Category::factory()->expense()->create();
    $hackedCategory = Category::factory()->custom($otherUser)->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $sysCategory->id,

        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'categoryId' => $hackedCategory->id,
        'categoryType' => 'custom',
    ]);

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(BusinessRuleException::class);

it('throws validation exception when category type does not match transaction type', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $expenseCategory = Category::factory()->expense()->create();
    $incomeCategory = Category::factory()->income()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $expenseCategory->id,
        'type' => TransactionType::EXPENSE,
        'amount' => '100.00',
    ]);

    // Try to assign an income category to an expense transaction
    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        categoryIdProvided: true,
        categoryId: $incomeCategory->id,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(BusinessRuleException::class);

it('fails when user tries to update other users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $otherUser->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $otherUser->id,
        'current_balance' => '500.00',
    ]);

    $category = Category::factory()->expense()->create();

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
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(UnauthorizedException::class);
