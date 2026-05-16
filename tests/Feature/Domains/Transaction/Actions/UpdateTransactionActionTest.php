<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\UpdateTransactionAction;
use App\Domains\Transaction\DTOs\UpdateTransactionData;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\UnauthorizedException;

it('successfully updates transaction name and note only', function () {
    [$user, $category] = setupUserWithBudget();

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
    [$user, $category] = setupUserWithBudget();

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
    [$user, $category] = setupUserWithBudget();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'transaction_at' => '2026-03-01 10:00:00',
        'amount' => '100.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionAtProvided: true,
        transactionAt: CarbonImmutable::parse('2026-03-15T15:00:00+07:00')->utc(),
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->transaction_at->toDateTimeString())->toBe('2026-03-15 08:00:00');
});

it('successfully updates transaction with system category', function () {
    [$user] = setupUserWithBudget();
    $oldCategory = Category::factory()->expense()->create();
    $newCategory = Category::factory()->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $oldCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        categoryIdProvided: true,
        categoryId: $newCategory->id,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->category_id)->toBe($newCategory->id);
});

it('successfully updates transaction with custom category belonging to user', function () {
    [$user] = setupUserWithBudget();
    $sysCategory = Category::factory()->expense()->create();

    $customCategory = Category::factory()->custom($user)->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $sysCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        categoryIdProvided: true,
        categoryId: $customCategory->id,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->category_id)->toBe($customCategory->id);
});

it('throws validation exception if amount update causes negative balance', function () {
    [$user] = setupUserWithBudget(['initial_balance' => '50.00']);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'amount' => '50.00',
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: true,
        amount: '200.00',
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    expect(fn () => app(UpdateTransactionAction::class)->execute($user, $transaction, $dto))
        ->toThrow(BusinessRuleException::class);
});

it('throws exception when updating to a future date', function () {
    [$user, $category] = setupUserWithBudget();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        categoryIdProvided: false,
        categoryId: null,
        noteProvided: false,
        note: null,
        transactionAtProvided: true,
        transactionAt: now()->addDay()->toImmutable(), // Ensure it's a future date
    );

    expect(fn () => app(UpdateTransactionAction::class)->execute($user, $transaction, $dto))
        ->toThrow(BusinessRuleException::class);
});

it('throws validation exception if custom category belongs to another user', function () {
    [$user] = setupUserWithBudget();
    $otherUser = User::factory()->create();

    $sysCategory = Category::factory()->expense()->create();
    $hackedCategory = Category::factory()->custom($otherUser)->expense()->create();

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $sysCategory->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = new UpdateTransactionData(
        nameProvided: false,
        name: null,
        amountProvided: false,
        amount: null,
        categoryIdProvided: true,
        categoryId: $hackedCategory->id,
        noteProvided: false,
        note: null,
        transactionAtProvided: false,
        transactionAt: null,
    );

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(BusinessRuleException::class);

it('throws validation exception when category type does not match transaction type', function () {
    [$user] = setupUserWithBudget();

    $expenseCategory = Category::factory()->expense()->create();
    $incomeCategory = Category::factory()->income()->create();

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
    [$user] = setupUserWithBudget();
    [$otherUser] = setupUserWithBudget();

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
