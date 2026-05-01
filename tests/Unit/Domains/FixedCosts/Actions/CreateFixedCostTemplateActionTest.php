<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\CreateFixedCostTemplateAction;
use App\Domains\FixedCost\Actions\GenerateOccurencesForBudgetWindowAction;
use App\Domains\FixedCost\DTOs\CreateFixedCostTemplateData;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use App\Models\CustomCategory;
use Carbon\Carbon;
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
        'initial_balance' => '0',
    ]);

    UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '1000000.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
        'reserved_cost' => '0',
    ]);

    $category = Category::factory()->expense()->create();

    return [$user, $category];
}

function makeDto(array $overrides = [], ?Category $category = null, string $cycleType = 'monthly'): CreateFixedCostTemplateData
{
    return CreateFixedCostTemplateData::fromArray(array_merge([
        'name' => 'Netflix',
        'amount' => '150000',
        'cycleType' => $cycleType,
        'dueDay' => 15,
        'isActive' => true,
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
    [$user, $category] = setupUserForAdd();

    $this->action->execute($user->id, makeDto([], $category));

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'cycle_type' => 'monthly',
        'due_day' => 15,
        'is_active' => true,

        'category_id' => $category->id,
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

it('calls RecalculateBudgetSnapshotAction after creation', function () {
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

it('integration: increases reserved cost when a monthly fixed cost template is added', function () {
    app()->forgetInstance(GenerateOccurencesForBudgetWindowAction::class);
    app()->forgetInstance(RecalculateBudgetSnapshotAction::class);
    $this->travelTo(Carbon::parse('2026-03-10'));

    $realAction = app(CreateFixedCostTemplateAction::class);

    [$user, $category] = setupUserForAdd('monthly');

    UserBudgetSnapshot::where('user_id', $user->id)->update(['reserved_cost' => 0]);
    $initialReservedCost = 0.0;

    $realAction->execute($user->id, makeDto([
        'amount' => '150000',
        'cycleType' => 'monthly',
        'dueDay' => 15,
    ], $category, 'monthly'));

    $freshSnapshot = UserBudgetSnapshot::where('user_id', $user->id)->first();
    $newReservedCost = (float) $freshSnapshot->reserved_cost;

    expect($newReservedCost)->toBeGreaterThan($initialReservedCost)
        ->and($newReservedCost)->toBe($initialReservedCost + 150000);

});

it('integration: increases reserved cost when a weekly fixed cost template is added', function () {
    app()->forgetInstance(GenerateOccurencesForBudgetWindowAction::class);
    app()->forgetInstance(RecalculateBudgetSnapshotAction::class);
    $realAction = app(CreateFixedCostTemplateAction::class);

    $this->travelTo(Carbon::parse('2026-03-01'));
    [$user, $category] = setupUserForAdd('monthly');

    $initialSnapshot = UserBudgetSnapshot::where('user_id', $user->id)->first();
    $initialReservedCost = (float) ($initialSnapshot->reserved_cost ?? 0);

    $realAction->execute($user->id, makeDto([
        'amount' => '50000',
        'cycleType' => 'weekly',
        'dueDay' => 3,
    ], $category, 'weekly'));

    $freshSnapshot = UserBudgetSnapshot::where('user_id', $user->id)->first();

    $newReservedCost = (float) $freshSnapshot->reserved_cost;

    expect($newReservedCost)->toBeGreaterThan($initialReservedCost)
        ->and($newReservedCost)->toBe(200000.0);
});

it('creates a custom-category template when category belongs to user', function () {
    [$user] = setupUserForAdd();
    $custom = Category::factory()->custom($user)->expense()->create();

    $dto = makeDto([
        'categoryType' => CustomCategory::class,
        'categoryId' => $custom->id,
    ]);

    $this->action->execute($user->id, $dto);

    $this->assertDatabaseHas('fixed_cost_templates', [
        'user_id' => $user->id,

        'category_id' => $custom->id,
    ]);
});

it('throws ModelNotFoundException when user has no budget settings', function () {
    $user = User::factory()->create();
    $category = Category::factory()->expense()->create();

    expect(fn () => $this->action->execute($user->id, makeDto([], $category)))
        ->toThrow(ModelNotFoundException::class);
});

it('throws BusinessRuleException when name is empty', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['name' => '   '], $category)))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when amount is zero', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['amount' => '0'], $category)))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when amount is negative', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['amount' => '-100'], $category)))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when weekly due_day is 0', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['cycleType' => 'weekly', 'dueDay' => 0], $category, 'weekly')))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when weekly due_day is 8', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['cycleType' => 'weekly', 'dueDay' => 8], $category, 'weekly')))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when monthly due_day is 0', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['dueDay' => 0], $category)))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when monthly due_day is 32', function () {
    [$user, $category] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['dueDay' => 32], $category)))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when monthly fixed cost is added to weekly budget', function () {
    [$user, $category] = setupUserForAdd('weekly');

    expect(fn () => $this->action->execute($user->id, makeDto(['cycleType' => 'monthly', 'dueDay' => 15], $category, 'monthly')))
        ->toThrow(BusinessRuleException::class);
});

it('throws ModelNotFoundException for invalid system category id', function () {
    [$user] = setupUserForAdd();

    expect(fn () => $this->action->execute($user->id, makeDto(['categoryId' => 99999])))
        ->toThrow(ModelNotFoundException::class);
});

it('throws BusinessRuleException when custom category belongs to a different user', function () {
    [$user] = setupUserForAdd();
    $otherUser = User::factory()->create();
    $custom = Category::factory()->custom($otherUser)->create();

    expect(fn () => $this->action->execute($user->id, makeDto([
        'categoryType' => CustomCategory::class,
        'categoryId' => $custom->id,
    ])))
        ->toThrow(BusinessRuleException::class);
});
