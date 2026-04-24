<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\Actions\BulkCreateFixedCostTemplateAction;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Models\Category;
use App\Models\FixedCostTemplate;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
    $category = Category::factory()->expense()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Netflix',
        amount: '100000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 25,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '100000.00',
        'cycle_type' => CycleType::MONTHLY->value,
        'due_day' => 25,
        'category_id' => $category->id,
        'is_active' => true,
    ]);
});

it('creates fixed cost template with owned custom category', function () {
    $user = createUserWithBudgetSetting();
    $category = Category::factory()->custom($user)->expense()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Gym',
        amount: '50000.00',
        cycleType: CycleType::WEEKLY,
        dueDay: 3,
        isActive: false,
        categoryId: $category->id,
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
    $category = Category::factory()->expense()->create();

    $dto = new CreateFixedCostTemplateData(
        name: '   ',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(BusinessRuleException::class);

it('rejects non positive amount', function () {
    $user = createUserWithBudgetSetting();
    $category = Category::factory()->expense()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Internet',
        amount: '0.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(BusinessRuleException::class);

it('rejects invalid weekly due day', function () {
    $user = createUserWithBudgetSetting();
    $category = Category::factory()->expense()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Weekly Bill',
        amount: '10000.00',
        cycleType: CycleType::WEEKLY,
        dueDay: 8,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(BusinessRuleException::class);

it('rejects invalid monthly due day', function () {
    $user = createUserWithBudgetSetting();
    $category = Category::factory()->expense()->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Monthly Bill',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 32,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(BusinessRuleException::class);

it('rejects invalid category type', function () {
    $user = createUserWithBudgetSetting();

    $dto = new CreateFixedCostTemplateData(
        name: 'Weird',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: 1,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(ModelNotFoundException::class);

it('rejects invalid system category id', function () {
    $user = createUserWithBudgetSetting();

    $dto = new CreateFixedCostTemplateData(
        name: 'Bad System Category',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: 999999,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(ModelNotFoundException::class);

it('rejects custom category owned by another user', function () {
    $user = createUserWithBudgetSetting();
    $otherUser = User::factory()->create();
    $category = Category::factory()->custom($otherUser)->create();

    $dto = new CreateFixedCostTemplateData(
        name: 'Wrong Owner',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$dto]);
})->throws(BusinessRuleException::class);

it('rolls back all inserts when one item is invalid', function () {
    $user = createUserWithBudgetSetting();
    $category = Category::factory()->expense()->create();

    $valid = new CreateFixedCostTemplateData(
        name: 'Valid',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: $category->id,
    );

    $invalid = new CreateFixedCostTemplateData(
        name: '',
        amount: '10000.00',
        cycleType: CycleType::MONTHLY,
        dueDay: 10,
        isActive: true,
        categoryId: $category->id,
    );

    app(BulkCreateFixedCostTemplateAction::class)->execute($user->id, [$valid, $invalid]);

    expect(FixedCostTemplate::query()->count())->toBe(0);
})->throws(BusinessRuleException::class);
