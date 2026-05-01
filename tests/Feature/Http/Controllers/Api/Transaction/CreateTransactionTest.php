<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\CreateTransactionAction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;

it('throws BusinessRuleException when category type does not match transaction type', function () {

    $user = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'timezone' => 'Asia/Jakarta',
    ]);

    $category = Category::factory()->expense()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Test Income',
        amount: '10000',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: CarbonImmutable::now(),
    );

    $action = app(CreateTransactionAction::class);

    expect(fn () => $action->execute($user, $dto))
        ->toThrow(BusinessRuleException::class);
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

    $category = Category::factory()->income()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Salary',
        amount: '100000',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: CarbonImmutable::now(),
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

    $category = Category::factory()->expense()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Buy Laptop',
        amount: '999999999',
        type: TransactionType::EXPENSE,
        note: null,
        transactionAt: CarbonImmutable::now(),
    );

    $action = app(CreateTransactionAction::class);

    expect(fn () => $action->execute($user, $dto))
        ->toThrow(BusinessRuleException::class);
});
