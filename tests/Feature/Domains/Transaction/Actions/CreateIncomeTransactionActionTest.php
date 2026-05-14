<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\Actions\CreateIncomeTransactionAction;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Services\TransactionValidator;
use App\Domains\User\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $this->mockRecalculate->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);

    $validationService = app(TransactionValidator::class);

    $this->action = new CreateIncomeTransactionAction(
        $this->mockRecalculate,
        $validationService,
    );
});

it('successfully creates an income transaction and triggers recalculation', function () {
    $user = User::factory()->create();
    $category = Category::factory()->income()->create();
    $date = CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC');

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
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
        'type' => TransactionType::INCOME->value,
        'source' => TransactionSource::MANUAL->value,
        'transaction_at' => '2026-03-30 10:00:00',
        'note' => 'Gaji bulan maret',
    ]);

    $this->mockRecalculate->shouldHaveReceived('execute');
});

it('successfully creates income transaction using a Custom Category', function () {
    [$user] = setupUserWithBudget();

    $customCategory = Category::factory()->custom($user)->income()->create();

    $date = CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC');

    $dto = new CreateTransactionData(
        categoryId: $customCategory->id,
        name: 'Project Freelance',
        amount: '1500000.00',
        type: TransactionType::INCOME,
        note: 'Bikin web',
        transactionAt: $date,
    );

    $transaction = $this->action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'category_id' => $customCategory->id,
        'type' => TransactionType::INCOME->value,
        'transaction_at' => '2026-03-30 10:00:00',
    ]);

    $this->mockRecalculate->shouldHaveReceived('execute');
});

it('throws an BusinessRuleException if income amount is zero or negative', function () {
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,
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
    [$user] = setupUserWithBudget();

    $category = Category::factory()->expense()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,
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
    [$user] = setupUserWithBudget();

    $dto = new CreateTransactionData(
        categoryId: 99999,
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
    [$user] = setupUserWithBudget();
    $category = Category::factory()->income()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Bonus',
        amount: '5,000,000',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: CarbonImmutable::now(),
    );

    $transaction = $this->action->execute($user, $dto);

    expect((string) $transaction->amount)->toBe('5000000.00');
});
