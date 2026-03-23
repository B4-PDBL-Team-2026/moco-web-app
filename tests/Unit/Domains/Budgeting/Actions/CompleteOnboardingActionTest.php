<?php

use App\Domains\Budgeting\Actions\CompleteOnboardingAction;
use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\FixedCostTemplateData;
use App\Domains\Transactions\Enums\TransactionSource;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('completes onboarding without fixed costs', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '1000.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    $result = app(CompleteOnboardingAction::class)->execute($user->id, $dto);

    expect($result->userId)->toBe($user->id)
        ->and($result->fixedCostsCount)->toBe(0)
        ->and($result->hasOnboarded)->toBeTrue();

    $this->assertDatabaseHas('user_budget_settings', [
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
    ]);

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'source' => TransactionSource::INITIAL_BALANCE->value,
        'amount' => '1000.00',
    ]);

    $this->assertDatabaseHas('user_budget_snapshots', [
        'user_id' => $user->id,
    ]);

    expect($user->fresh()->has_onboarded)->toBeTrue();
});

it('completes onboarding with fixed costs and returns computed result', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '1000.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [
            new FixedCostTemplateData(
                name: 'Rent',
                amount: '400.00',
                cycleType: CycleType::MONTHLY,
                isActive: true,
                categoryId: $category->id,
                dueDay: 25,
                categoryType: SystemCategory::class,
            ),
        ],
        timezone: 'Asia/Jakarta',
    );

    $result = app(CompleteOnboardingAction::class)->execute($user->id, $dto);

    expect($result->fixedCostsCount)->toBe(1)
        ->and($result->currentBalance)->not->toBe('')
        ->and($result->reservedCost)->not->toBe('')
        ->and($result->dailyAllowance)->not->toBe('');
});

it('allows weekly fixed costs within a monthly budget cycle', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '1000.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [
            new FixedCostTemplateData(
                name: 'Weekly Trash',
                amount: '50.00',
                cycleType: CycleType::WEEKLY,
                isActive: true,
                categoryId: $category->id,
                dueDay: 3,
                categoryType: SystemCategory::class,
            ),
        ],
        timezone: 'Asia/Jakarta',
    );

    $result = app(CompleteOnboardingAction::class)->execute($user->id, $dto);

    expect($result->hasOnboarded)->toBeTrue()
        ->and($result->fixedCostsCount)->toBe(1);
});

it('allows weekly fixed costs within a weekly budget cycle', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::WEEKLY,
        initialBalance: '1000.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [
            new FixedCostTemplateData(
                name: 'Weekly Groceries',
                amount: '200.00',
                cycleType: CycleType::WEEKLY,
                isActive: true,
                categoryId: $category->id,
                dueDay: 1,
                categoryType: SystemCategory::class,
            ),
        ],
        timezone: 'Asia/Jakarta',
    );

    $result = app(CompleteOnboardingAction::class)->execute($user->id, $dto);

    expect($result->hasOnboarded)->toBeTrue()
        ->and($result->fixedCostsCount)->toBe(1);
});

it('rejects monthly fixed costs within a weekly budget cycle', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::WEEKLY,
        initialBalance: '1000.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [
            new FixedCostTemplateData(
                name: 'Monthly Rent',
                amount: '1500.00',
                cycleType: CycleType::MONTHLY,
                isActive: true,
                categoryId: $category->id,
                dueDay: 25,
                categoryType: SystemCategory::class,
            ),
        ],
        timezone: 'Asia/Jakarta',
    );

    app(CompleteOnboardingAction::class)->execute($user->id, $dto);
})->throws(InvalidArgumentException::class, 'Monthly fixed cost is not allowed when budget cycle is weekly.');

it('does not create duplicate initial balance transaction when onboarding is called twice', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '1000.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    $action = app(CompleteOnboardingAction::class);

    $action->execute($user->id, $dto);
    $action->execute($user->id, $dto);

    expect(
        Transaction::query()
            ->where('user_id', $user->id)
            ->where('source', TransactionSource::INITIAL_BALANCE->value)
            ->count()
    )->toBe(1);
});

it('rejects negative initial balance', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '-1.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    app(CompleteOnboardingAction::class)->execute($user->id, $dto);
})->throws(InvalidArgumentException::class, 'Initial balance must be greater than 0.');

it('rejects negative flooring limit', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '100.00',
        flooringLimit: '-1.00',
        ceilingLimit: '999999.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    app(CompleteOnboardingAction::class)->execute($user->id, $dto);
})->throws(InvalidArgumentException::class, 'Flooring limit cannot be negative.');

it('rejects ceiling lower than flooring', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '100.00',
        flooringLimit: '50.00',
        ceilingLimit: '10.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    app(CompleteOnboardingAction::class)->execute($user->id, $dto);
})->throws(InvalidArgumentException::class, 'Ceiling limit must be greater than or equal to flooring limit.');

it('rejects zero initial balance', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '0.00',
        flooringLimit: '0.00',
        ceilingLimit: '999999.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    app(CompleteOnboardingAction::class)->execute($user->id, $dto);
})->throws(InvalidArgumentException::class, 'Initial balance must be greater than 0.');

it('rejects null ceiling limit', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '1000.00',
        flooringLimit: '50.00',
        ceilingLimit: null, // TANPA BATAS ATAS
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    $result = app(CompleteOnboardingAction::class)->execute($user->id, $dto);

    expect($result->hasOnboarded)->toBeTrue();

    // Pastikan masuk ke tabel setting sebagai null
    $this->assertDatabaseHas('user_budget_settings', [
        'user_id' => $user->id,
        'ceiling_limit' => null,
    ]);
})->throws(TypeError::class);

it('completes onboarding successfully when ceiling limit exactly equals flooring limit', function () {
    $user = User::factory()->create();

    $dto = new CompleteOnboardingData(
        cycleType: CycleType::MONTHLY,
        initialBalance: '1000.00',
        flooringLimit: '50.00',
        ceilingLimit: '50.00',
        fixedCosts: [],
        timezone: 'Asia/Jakarta',
    );

    $result = app(CompleteOnboardingAction::class)->execute($user->id, $dto);

    expect($result->hasOnboarded)->toBeTrue();

    $this->assertDatabaseHas('user_budget_settings', [
        'user_id' => $user->id,
        'flooring_limit' => '50.00',
        'ceiling_limit' => '50.00',
    ]);
});
