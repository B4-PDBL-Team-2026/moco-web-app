<?php


use App\Domains\Transactions\Actions\UpdateTransactionAction;
use App\Domains\Transactions\DTOs\UpdateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('updates transaction name and note only', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE]);

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
        categoryTypeProvided: false,
        categoryType: null,
        noteProvided: true,
        note: 'New Note',
        transactionDateProvided: false,
        transactionDate: null,
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->name)->toBe('New Name')
        ->and($updated->note)->toBe('New Note');
});

it('updates amount', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '900.00',
    ]);

    $category = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE]);

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
        categoryTypeProvided: false,
        categoryType: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect((string) $updated->amount)->toBe('250.00');
});

it('updates transaction date', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE]);

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
        categoryIdProvided: false,
        categoryId: null,
        categoryTypeProvided: false,
        categoryType: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: true,
        transactionDate: CarbonImmutable::parse('2026-03-15'),
    );

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->transaction_date->toDateString())->toBe('2026-03-15');
});

it('successfully updates transaction with system category', function () {
    $user = User::factory()->create();
    $oldCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);
    $newCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $oldCategory->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'categoryId' => $newCategory->id,
        'categoryType' => 'system',
    ]);

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->category_id)->toBe($newCategory->id)
        ->and($updated->category_type)->toBe(SystemCategory::class);
});

it('successfully updates transaction with custom category belonging to user', function () {
    $user = User::factory()->create();
    $sysCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);

    $customCategory = CustomCategory::factory()->create([
        'user_id' => $user->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $sysCategory->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'categoryId' => $customCategory->id,
        'categoryType' => 'custom',
    ]);

    $updated = app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

    expect($updated->category_id)->toBe($customCategory->id)
        ->and($updated->category_type)->toBe(CustomCategory::class);
});

it('throws validation exception if custom category belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $sysCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE->value]);
    $hackedCategory = CustomCategory::factory()->create([
        'user_id' => $otherUser->id,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'category_id' => $sysCategory->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::EXPENSE->value,
    ]);

    $dto = UpdateTransactionData::fromArray([
        'categoryId' => $hackedCategory->id,
        'categoryType' => 'custom',
    ]);

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(ValidationException::class, 'Category not found.');

it('throws validation exception when category type does not match transaction type', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $expenseCategory = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE]);
    $incomeCategory = SystemCategory::factory()->create(['type' => TransactionType::INCOME]);

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
        categoryTypeProvided: false,
        categoryType: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(ValidationException::class);

it('fails when user tries to update other users transaction', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $otherUser->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $otherUser->id,
        'current_balance' => '500.00',
    ]);

    $category = SystemCategory::factory()->create(['type' => TransactionType::EXPENSE]);

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
        categoryTypeProvided: false,
        categoryType: null,
        noteProvided: false,
        note: null,
        transactionDateProvided: false,
        transactionDate: null,
    );

    app(UpdateTransactionAction::class)->execute($user, $transaction, $dto);

})->throws(\Illuminate\Validation\UnauthorizedException::class);
