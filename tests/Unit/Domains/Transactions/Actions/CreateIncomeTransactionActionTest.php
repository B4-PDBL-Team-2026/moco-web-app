<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transactions\Actions\CreateIncomeTransactionAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\TransactionValidationService;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    $date = CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC');

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: SystemCategory::class,
        name: 'Gaji Bulanan',
        amount: '5000000.00',
        type: TransactionType::INCOME,
        note: 'Gaji bulan maret',
        transactionAt: $date,
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
        'transaction_at' => '2026-03-30 10:00:00',
        'note' => 'Gaji bulan maret',
    ]);

    $this->mockRecalculate->shouldHaveReceived('execute');
});

it('successfully creates income transaction using a Custom Category', function () {
    $user = User::factory()->create();

    $customCategory = CustomCategory::factory()->createQuietly([
        'user_id' => $user->id,
        'type' => TransactionType::INCOME->value,
    ]);

    $date = CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC');

    $dto = new CreateTransactionData(
        categoryId: $customCategory->id,
        categoryType: CustomCategory::class,
        name: 'Project Freelance',
        amount: '1500000.00',
        type: TransactionType::INCOME,
        note: 'Bikin web',
        transactionAt: $date,
    );

    $transaction = $this->action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->category_type)->toBe(CustomCategory::class);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'category_id' => $customCategory->id,
        'category_type' => CustomCategory::class,
        'type' => TransactionType::INCOME->value,
        'transaction_at' => '2026-03-30 10:00:00',
    ]);

    $this->mockRecalculate->shouldHaveReceived('execute');
});

it('throws an BusinessRuleException if income amount is zero or negative', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->createQuietly(['type' => TransactionType::INCOME->value]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: SystemCategory::class,
        name: 'Hacker Attempt',
        amount: '-5000000.00',
        type: TransactionType::INCOME,
        note: 'Mencoba hack saldo',
        transactionAt: CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC'),
    );

    expect(fn () => $this->action->execute($user, $dto))
        ->toThrow(BusinessRuleException::class);

    $this->mockRecalculate->shouldNotHaveReceived('execute');
});

it('throws exception when category type is mismatch with transaction type income', function () {
    $user = User::factory()->create();

    $category = SystemCategory::factory()->createQuietly(['type' => TransactionType::EXPENSE->value]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        categoryType: SystemCategory::class,
        name: 'Nyoba Error',
        amount: '100000',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: CarbonImmutable::now(),
    );

    expect(fn () => $this->action->execute($user, $dto))
        ->toThrow(BusinessRuleException::class);

    $this->mockRecalculate->shouldNotHaveReceived('execute');
});

it('throws ModelNotFoundException when category does not exist', function () {
    $user = User::factory()->create();

    $dto = new CreateTransactionData(
        categoryId: 99999,
        categoryType: SystemCategory::class,
        name: 'Gaji Ghaib',
        amount: '5000000.00',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC'),
    );

    expect(fn () => $this->action->execute($user, $dto))
        ->toThrow(ModelNotFoundException::class);

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
        transactionAt: CarbonImmutable::now(),
    );

    $transaction = $this->action->execute($user, $dto);

    expect((string) $transaction->amount)->toBe('5000000.00');
});
