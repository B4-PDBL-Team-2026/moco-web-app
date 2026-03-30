<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transactions\Actions\CreateIncomeTransactionAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\TransactionValidationService;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $this->mockRecalculate->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);

    $validationService = app(TransactionValidationService::class);

    $this->action = new CreateIncomeTransactionAction(
        $this->mockRecalculate,
        $validationService
    );
});

it('successfully creates an income transaction and triggers recalculation', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->createQuietly(['type' => TransactionType::INCOME->value]);
    $date = CarbonImmutable::parse('2026-03-30');

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: SystemCategory::class,
        name: 'Gaji Bulanan',
        amount: '5000000.00',
        type: TransactionType::INCOME,
        note: 'Gaji bulan maret',
        transactionDate: $date,
    );

    $transaction = $this->action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->name)->toBe('Gaji Bulanan');

    $this->assertDatabaseHas('transactions', [
        'id' => $transaction->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'category_type' => SystemCategory::class,
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::MANUAL->value,
        'transaction_date' => '2026-03-30',
        'note' => 'Gaji bulan maret',
    ]);

    $this->mockRecalculate->shouldHaveReceived('execute');
});

it('throws exception when category is invalid for income', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->createQuietly(['type' => TransactionType::EXPENSE->value]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: SystemCategory::class,
        name: 'Nyoba Error',
        amount: '100000',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: CarbonImmutable::now(),
    );

    expect(fn () => $this->action->execute($user, $dto))
        ->toThrow(BusinessRuleException::class);

    $this->mockRecalculate->shouldNotHaveReceived('execute');
});

it('normalizes the amount correctly before saving', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->createQuietly(['type' => TransactionType::INCOME->value]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: SystemCategory::class,
        name: 'Bonus',
        amount: '5,000,000',
        type: TransactionType::INCOME,
        note: null,
        transactionDate: CarbonImmutable::now(),
    );

    $transaction = $this->action->execute($user, $dto);

    expect((string) $transaction->amount)->toBe('5000000.00');
});
