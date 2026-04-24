<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Transactions\Actions\CreateExpenseTransactionAction;
use App\Domains\Transactions\DTOs\CreateTransactionData;
use App\Domains\Transactions\Enums\TransactionType;
use App\Domains\Transactions\Services\TransactionValidator;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);
    $this->mockRecalculate->shouldReceive('execute')->andReturn(new UserBudgetSnapshot);

    $validationService = app(TransactionValidator::class);

    $this->action = new CreateExpenseTransactionAction(
        $this->mockRecalculate,
        $validationService
    );
});

it('successfully creates an expense transaction and triggers recalculation', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $category = Category::factory()->expense()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,

        name: 'Groceries',
        amount: '200.00',
        type: TransactionType::EXPENSE,
        note: 'Weekly groceries',
        transactionAt: CarbonImmutable::parse('2026-04-04 06:00:00', 'UTC'),
    );

    $transaction = $this->action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class);

    $this->mockRecalculate->shouldHaveReceived('execute');

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'name' => 'Groceries',
        'type' => TransactionType::EXPENSE->value,
        'transaction_at' => '2026-04-04 06:00:00',
    ]);
});

it('successfully creates expense transaction using a Custom Category', function () {
    [$user] = setupUserWithBudget();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '5000000.00',
    ]);

    $customCategory = Category::factory()->custom($user)->expense()->create();

    $date = CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC');

    $dto = new CreateTransactionData(
        categoryId: $customCategory->id,
        name: 'Project Freelance',
        amount: '1500000.00',
        type: TransactionType::EXPENSE,
        note: 'Bikin web',
        transactionAt: $date,
    );

    $transaction = $this->action->execute($user, $dto);

    expect($transaction)->toBeInstanceOf(Transaction::class);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'category_id' => $customCategory->id,
        'type' => TransactionType::EXPENSE->value,
        'transaction_at' => '2026-03-30 10:00:00',
    ]);

    $this->mockRecalculate->shouldHaveReceived('execute');
});

it('parses transaction date from local timezone offset to UTC correctly', function () {
    // user at Asia/Jakarta (+07:00)
    $payload = [
        'categoryId' => 1,
        'categoryType' => 'system',
        'name' => 'Makan Siang',
        'amount' => '50000',
        'type' => TransactionType::EXPENSE->value,
        'note' => 'Sate ayam',
        'transactionAt' => '2026-04-04T13:00:00+07:00',
    ];

    $dto = CreateTransactionData::fromArray($payload);

    // should parsed into UTC timezone
    expect($dto->transactionAt->format('Y-m-d H:i:s'))->toBe('2026-04-04 06:00:00')
        ->and($dto->transactionAt->timezone->getName())->toBe('UTC');
});

it('throws an BusinessRuleException if expense amount is zero or negative', function () {
    $user = User::factory()->create();
    $category = Category::factory()->income()->create();

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '5000000.00',
    ]);

    $dto = new CreateTransactionData(
        categoryId: $category->id,
        name: 'Hacker Attempt',
        amount: '-5000000.00',
        type: TransactionType::EXPENSE,
        note: 'Mencoba hack saldo',
        transactionAt: CarbonImmutable::parse('2026-03-30 10:00:00', 'UTC'),
    );

    $this->action->execute($user, $dto);

    $this->mockRecalculate->shouldNotHaveReceived('execute');
})->throws(BusinessRuleException::class);

it('throws BusinessRuleException when category type does not match transaction type expense', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    // Category is EXPENSE but transaction type is INCOME — should fail
    $category = Category::factory()->custom($user)->expense()->create();

    $dto = new CreateTransactionData(
        categoryId: $category->id,

        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: now()->toImmutable(),
    );

    $this->action->execute($user, $dto);

})->throws(BusinessRuleException::class);

it('throws ModelNotFoundException when category does not exist', function () {
    $user = User::factory()->create();

    UserBudgetSetting::factory()->create(['user_id' => $user->id]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000.00',
    ]);

    $dto = new CreateTransactionData(
        categoryId: 999999, // non-existent

        name: 'Salary',
        amount: '500.00',
        type: TransactionType::INCOME,
        note: null,
        transactionAt: now()->toImmutable(),
    );

    $this->action->execute($user, $dto);
})->throws(ModelNotFoundException::class);
