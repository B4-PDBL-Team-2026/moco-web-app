<?php

use App\Domains\Transactions\Actions\CreateTransactionAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

it('throws validation exception when category type does not match transaction type', function () {

    $user = User::factory()->create();

    $category = SystemCategory::factory()->create([
        'type' => 'expense',
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: 'system',
        name: 'Test Income',
        amount: '10000',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: CarbonImmutable::now(),
    );

    $action = app(CreateTransactionAction::class);

    expect(fn () => $action->execute($user, $dto))
        ->toThrow(ValidationException::class);
});

it('creates income transaction successfully', function () {

    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => 'monthly',
        'flooring_limit' => '0',
        'ceiling_limit' => '999999999',
        'timezone' => 'Asia/Jakarta',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => 'income',
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: 'system',
        name: 'Salary',
        amount: '100000',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: CarbonImmutable::now(),
    );

    $action = app(CreateTransactionAction::class);

    $transaction = $action->execute($user, $dto);

    expect($transaction)->not->toBeNull();
    expect($transaction->type)->toBe(TransactionType::INCOME);
});

it('rejects expense when amount exceeds balance', function () {

    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => 'monthly',
        'flooring_limit' => '0',
        'ceiling_limit' => '999999999',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '0',
    ]);

    $category = SystemCategory::factory()->create([
        'type' => 'expense',
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: 'system',
        name: 'Buy Laptop',
        amount: '999999999',
        type: TransactionType::EXPENSE,
        note: null,
        transactionDate: CarbonImmutable::now(),
    );

    $action = app(CreateTransactionAction::class);

    expect(fn () => $action->execute($user, $dto))
        ->toThrow(ValidationException::class);
});
