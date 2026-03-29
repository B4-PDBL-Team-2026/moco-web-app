<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);
uses(Tests\TestCase::class)->in('Unit');

use App\Domains\Transactions\Actions\CreateTransactionAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Illuminate\Validation\ValidationException;

it('creates income transaction', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::INCOME,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: 'system',
        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: 'Monthly salary',
        transactionDate: now()->toImmutable(),
    );

    $transaction = app(CreateTransactionAction::class)->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class);

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'name' => 'Salary',
        'amount' => '500.00',
        'type' => TransactionType::INCOME->value,
    ]);
});

it('creates expense transaction', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: 'system',
        name: 'Groceries',
        amount: '200.00',
        type: TransactionType::EXPENSE,
        note: 'Weekly groceries',
        transactionDate: now()->toImmutable(),
    );

    $transaction = app(CreateTransactionAction::class)->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'name' => 'Groceries',
        'type' => TransactionType::EXPENSE->value,
    ]);
});

it('throws validation exception when category type does not match transaction type', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    // Category is EXPENSE but transaction type is INCOME — should fail
    $category = SystemCategory::factory()->create([
        'type' => TransactionType::EXPENSE,
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: 'system',
        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: now()->toImmutable(),
    );

    app(CreateTransactionAction::class)->execute($user, $dto);

})->throws(ValidationException::class);

it('fails when category does not exist', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $dto = new CreateTransactionData(
        categoryId: 999999, // non-existent
        categoryType: 'system',
        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: now()->toImmutable(),
    );

    app(CreateTransactionAction::class)->execute($user, $dto);

})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
