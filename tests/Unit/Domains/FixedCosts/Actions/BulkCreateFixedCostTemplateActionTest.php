<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\Actions\BulkCreateFixedCostTemplateAction;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Models\CustomCategory;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createUserWithBudgetSetting(CycleType $cycleType = CycleType::MONTHLY): User
{
    $user = User::factory()->create();
    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => $cycleType->value,
        'initial_balance' => '1000',
        'flooring_limit' => '0',
        'ceiling_limit' => '50',
        'timezone' => 'Asia/Jakarta',
    ]);

    return $user;
}

it('creates fixed cost template with system category', function () {
    $user = createUserWithBudgetSetting();
    $category = SystemCategory::factory()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Netflix',
        amount: '100000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 25,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '100000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 25,
        'category_type' => SystemCategory::class,
        'category_id' => $category->id,
        'is_active' => true,
    ]);
});

it('creates fixed cost template with owned custom category', function () {
    $user = createUserWithBudgetSetting();
    $category = CustomCategory::factory()->create(['user_id' => $user->id]);

    $dto = new CreateFixedCostTemplateData(
        name: 'Gym',
        amount: '50000.00',
        cycleType: CycleType::WEEKLY,
        isActive: false,
        categoryId: $category->id,
        dueDay: 3,
        categoryType: CustomCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'name' => 'Gym',
        'is_active' => false,
    ]);
});

it('rejects blank fixed cost name', function () {
    $user = createUserWithBudgetSetting();
    $category = SystemCategory::factory()->create();

    $dto = new CreateFixedCostTemplateData(
        name: '   ',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 10,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Fixed cost name is required.');

it('rejects non positive amount', function () {
    $user = createUserWithBudgetSetting();
    $category = SystemCategory::factory()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Internet',
        amount: '0.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 10,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Fixed cost amount must be greater than zero.');

it('rejects invalid weekly due day', function () {
    $user = createUserWithBudgetSetting();
    $category = SystemCategory::factory()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Weekly Bill',
        amount: '10000.00',
        cycleType: CycleType::WEEKLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 8,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Weekly due day must be between 1 and 7.');

it('rejects invalid monthly due day', function () {
    $user = createUserWithBudgetSetting();
    $category = SystemCategory::factory()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Monthly Bill',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 32,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Monthly due day must be between 1 and 31.');

it('rejects invalid category type', function () {
    $user = createUserWithBudgetSetting();

    $dto = new CreateFixedCostTemplateData(
        name: 'Weird',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: 1,
        dueDay: 10,
        categoryType: 'App\Models\WeirdCategory',
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Invalid category type.');

it('rejects invalid system category id', function () {
    $user = createUserWithBudgetSetting();

    $dto = new CreateFixedCostTemplateData(
        name: 'Bad System Category',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: 999999,
        dueDay: 10,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Invalid category.');

it('rejects custom category owned by another user', function () {
    $user = createUserWithBudgetSetting();
    $otherUser = User::factory()->create();
    $category = CustomCategory::factory()->create(['user_id' => $otherUser->id]);

    $dto = new CreateFixedCostTemplateData(
        name: 'Wrong Owner',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 10,
        categoryType: CustomCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(InvalidArgumentException::class, 'Invalid custom category.');

it('rolls back all inserts when one item is invalid', function () {
    $user = createUserWithBudgetSetting();
    $category = SystemCategory::factory()->create();

    $valid = new CreateFixedCostTemplateData(
        name: 'Valid',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 10,
        categoryType: SystemCategory::class,
    );

    $invalid = new CreateFixedCostTemplateData(
        name: '',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        isActive: true,
        categoryId: $category->id,
        dueDay: 10,
        categoryType: SystemCategory::class,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$valid, $invalid]);

    expect(FixedCostTemplate::query()->count())->toBe(0);
})->throws(InvalidArgumentException::class, 'Fixed cost name is required.');
