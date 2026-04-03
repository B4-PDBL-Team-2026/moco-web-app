<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\FixedCosts\Actions\CreateFixedCostTemplateAction;
use App\Domains\FixedCosts\Actions\GenerateOccurencesForBudgetWindowAction;
use App\Domains\FixedCosts\DTOs\CreateFixedCostTemplateData;
use App\Models\CustomCategory;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use App\Models\UserBudgetSetting;
use App\Models\UserBudgetSnapshot;
use Illuminate\Database\Eloquent\ModelNotFoundException;

function setupUserForAdd(string $cycleType = 'monthly'): array
{
    $user = User::factory()->create(['has_onboarded' => true]);

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => $cycleType,
        'ceiling_limit' => '500000.00',
        'flooring_limit' => '10000.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000000.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);

    $category = SystemCategory::factory()->create();

    return [$user, $category];
}

function makeDto(array $overrides = [], ?SystemCategory $category = null, string $cycleType = 'monthly'): CreateFixedCostTemplateData
{
    return CreateFixedCostTemplateData::fromArray(array_merge([
        'name' => 'Netflix',
        'amount' => '150000',
        'cycleType' => $cycleType,
        'dueDay' => 15,
        'isActive' => true,
        'categoryType' => SystemCategory::class,
        'categoryId' => $category?->id ?? 1,
    ], $overrides));
}

beforeEach(function () {
    $this->spyGenerate = $this->spy(GenerateOccurencesForBudgetWindowAction::class);

    $this->mockRecalculate = Mockery::mock(RecalculateBudgetSnapshotAction::class);

    $this->mockRecalculate->shouldReceive('execute')
        ->andReturn(new UserBudgetSnapshot)
        ->byDefault();

    app()->instance(RecalculateBudgetSnapshotAction::class, $this->mockRecalculate);

    $this->action = app(CreateFixedCostTemplateAction::class);
});

it('creates a fixed cost template with correct fields', function () {
    [$user, $categoryegory] = setupUserForAdd();

    $this->action->execute($user->id, makeDto([], $categoryegory));

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'cycle_type' => 'monthly',
        'due_day' => 15,
        'is_active' => true,
        'category_type' => SystemCategory::class,
        'category_id' => $categoryegory->id,
    ]);
});

it('returns the created template model', function () {
    [$user, $category] = setupUserForAdd();

    $template = $this->action->execute($user->id, makeDto([], $category));

    expect($template)->toBeInstanceOf(FixedCostTemplate::class)
        ->and($template->id)->not->toBeNull();
});

it('calls GenerateOccurrencesForBudgetWindowAction after creation', function () {
    [$user, $category] = setupUserForAdd();

    $this->action->execute($user->id, makeDto([], $category));

    $this->spyGenerate->shouldHaveReceived('execute');
});

it('calls RecalculateBudgetSnapshotAction after creation (BR §12)', function () {
    [$user, $category] = setupUserForAdd();

    $this->mockRecalculate->shouldReceive('execute')->once()->with($user->id);

    $this->action->execute($user->id, makeDto([], $category));
});

it('creates a weekly fixed cost template under a monthly budget', function () {
    [$user, $category] = setupUserForAdd('monthly');

    $this->action->execute($user->id, makeDto(['cycleType' => 'weekly', 'dueDay' => 3], $category, 'weekly'));

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'cycle_type' => 'weekly',
        'due_day' => 3,
    ]);
});

it('creates a custom-category template when category belongs to user', function () {
    [$user] = setupUserForAdd();
    $custom = CustomCategory::factory()->create(['user_id' => $user->id]);

    $dto = makeDto([
        'categoryType' => CustomCategory::class,
        'categoryId' => $custom->id,
    ]);

    $this->action->execute($user->id, $dto);

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'category_type' => CustomCategory::class,
        'category_id' => $custom->id,
    ]);
});

it('throws ModelNotFoundException when user has no budget settings', function () {
    $user = User::factory()->create();
    $category = SystemCategory::factory()->create();

    expect(fn () => $this->action->execute($user->id, makeDto([], $category)))
        ->toThrow(ModelNotFoundException::class);
});

it('throws InvalidArgumentException when name is empty', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['name' => '   '], $category)))
        ->toThrow(InvalidArgumentException::class, 'Fixed cost name is required.');
});

it('throws InvalidArgumentException when amount is zero', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['amount' => '0'], $category)))
        ->toThrow(InvalidArgumentException::class, 'must be greater than zero');
});

it('throws InvalidArgumentException when amount is negative', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['amount' => '-100'], $category)))
        ->toThrow(InvalidArgumentException::class, 'must be greater than zero');
});

it('throws InvalidArgumentException when weekly due_day is 0', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['cycleType' => 'weekly', 'dueDay' => 0], $category, 'weekly')))
        ->toThrow(InvalidArgumentException::class, 'Weekly due day must be between 1 and 7.');
});

it('throws InvalidArgumentException when weekly due_day is 8', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['cycleType' => 'weekly', 'dueDay' => 8], $category, 'weekly')))
        ->toThrow(InvalidArgumentException::class, 'Weekly due day must be between 1 and 7.');
});

it('throws InvalidArgumentException when monthly due_day is 0', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['dueDay' => 0], $category)))
        ->toThrow(InvalidArgumentException::class, 'Monthly due day must be between 1 and 31.');
});

it('throws InvalidArgumentException when monthly due_day is 32', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['dueDay' => 32], $category)))
        ->toThrow(InvalidArgumentException::class, 'Monthly due day must be between 1 and 31.');
});

it('throws InvalidArgumentException when monthly fixed cost is added to weekly budget', function () {
    [$user, $category] = setupUserForAdd('weekly');

    expect(fn () => $this->action->execute($user->id, makeDto(['cycleType' => 'monthly', 'dueDay' => 15], $category, 'monthly')))
        ->toThrow(BusinessRuleException::class, 'not allowed when budget cycle is weekly');
});

it('throws InvalidArgumentException for invalid system category id', function () {
    [$user] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['categoryId' => 99999])))
        ->toThrow(BusinessRuleException::class, 'Invalid system category.');
});

it('throws InvalidArgumentException when custom category belongs to a different user', function () {
    [$user] = setupUserForAdd();
    $otherUser = User::factory()->create();
    $custom = CustomCategory::factory()->create(['user_id' => $otherUser->id]);

    expect(fn () => $this->action->execute($user->id, makeDto([
        'categoryType' => CustomCategory::class,
        'categoryId' => $custom->id,
    ])))
        ->toThrow(BusinessRuleException::class, 'Invalid custom category.');
});

it('throws InvalidArgumentException for an unsupported category type', function () {
    [$user] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['categoryType' => 'SomeOtherClass'])))
        ->toThrow(BusinessRuleException::class, 'Invalid category type.');
});
