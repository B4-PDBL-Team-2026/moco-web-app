<?php

use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\GetAllFixedCostOccurrencesAction;
use App\Domains\FixedCost\DTOs\FilterFixedCostOccurrenceData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

function setupOccurrenceTest(): array
{
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,
    ]);

    $snapshot = UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 15,
    ]);

    return [$user, $template, $cat, $snapshot];
}

function occurrenceOn(User $user, FixedCostTemplate $template, string $dueDate, string $cycleKey, string $cycleType = 'monthly', array $overrides = []): FixedCostOccurrence
{
    return FixedCostOccurrence::factory()->create(array_merge([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => $cycleKey,
        'cycle_type' => $cycleType,
        'due_date' => $dueDate,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '100000.00',
        'name' => 'Test',
        'category_id' => $template->category_id,
    ], $overrides));
}

function makeFilter(array $overrides = []): FilterFixedCostOccurrenceData
{
    return new FilterFixedCostOccurrenceData(
        keyword: $overrides['keyword'] ?? null,
        status: $overrides['status'] ?? null,
        startDate: $overrides['startDate'] ?? null,
        endDate: $overrides['endDate'] ?? null,
        page: $overrides['page'] ?? 1,
        perPage: $overrides['perPage'] ?? 15,
    );
}

beforeEach(function () {
    $this->action = app(GetAllFixedCostOccurrencesAction::class);
});

it('returns length aware paginator instance', function () {
    [$user, $template] = setupOccurrenceTest();

    $results = $this->action->execute($user->id, makeFilter());

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class);
});

it('returns monthly occurrences whose due_date falls within the budget window', function () {
    [$user, $template] = setupOccurrenceTest();

    $occ = occurrenceOn($user, $template, '2026-03-15', '2026-03', 'monthly');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items)->toHaveCount(1)
        ->and($items->first()->id)->toBe($occ->id);
});

it('returns weekly occurrences whose due_date falls within the monthly budget window', function () {
    [$user, $template] = setupOccurrenceTest();

    $week10 = occurrenceOn($user, $template, '2026-03-03', '2026-W10', 'weekly');
    $week11 = occurrenceOn($user, $template, '2026-03-10', '2026-W11', 'weekly');
    $week13 = occurrenceOn($user, $template, '2026-03-24', '2026-W13', 'weekly');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items)->toHaveCount(3);
    expect($items->pluck('id')->sort()->values())->toEqual(
        collect([$week10->id, $week11->id, $week13->id])->sort()->values()
    );
});

it('returns both monthly and weekly occurrences within the same budget window', function () {
    [$user, $template] = setupOccurrenceTest();

    $monthly = occurrenceOn($user, $template, '2026-03-15', '2026-03', 'monthly');
    $weekly = occurrenceOn($user, $template, '2026-03-10', '2026-W11', 'weekly');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items)->toHaveCount(2);
    expect($items->pluck('id')->sort()->values())->toEqual(
        collect([$monthly->id, $weekly->id])->sort()->values()
    );
});

it('returns occurrences ordered by due_date ascending', function () {
    [$user, $template] = setupOccurrenceTest();

    $late = occurrenceOn($user, $template, '2026-03-25', '2026-W13', 'weekly');
    $early = occurrenceOn($user, $template, '2026-03-05', '2026-W10', 'weekly');
    $mid = occurrenceOn($user, $template, '2026-03-15', '2026-03', 'monthly');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items->pluck('id')->toArray())->toBe([$early->id, $mid->id, $late->id]);
});

it('includes occurrences with due_date exactly on cycle_start_date', function () {
    [$user, $template] = setupOccurrenceTest();

    $occ = occurrenceOn($user, $template, '2026-03-01', '2026-03');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items->first()->id)->toBe($occ->id);
});

it('includes occurrences with due_date exactly on cycle_end_date', function () {
    [$user, $template] = setupOccurrenceTest();

    $occ = occurrenceOn($user, $template, '2026-03-31', '2026-03');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items->first()->id)->toBe($occ->id);
});

it('includes previous overdue occurrences (carried over debt)', function () {
    [$user, $template] = setupOccurrenceTest();

    $pastPending = occurrenceOn($user, $template, '2026-02-15', '2026-02');
    $pastPending->update(['status' => FixedCostOccurenceStatus::OVERDUE->value]);

    $current = occurrenceOn($user, $template, '2026-03-15', '2026-03');

    $results = $this->action->execute($user->id, makeFilter());
    $items = $results->getCollection();

    expect($items)->toHaveCount(2)
        ->and($items->pluck('id')->toArray())->toBe([$pastPending->id, $current->id]);
});

it('excludes previous PAID or VOID occurrences', function () {
    [$user, $template] = setupOccurrenceTest();

    $pastPaid = occurrenceOn($user, $template, '2026-02-15', '2026-02');
    $pastPaid->update(['status' => FixedCostOccurenceStatus::PAID->value]);

    $pastVoid = occurrenceOn($user, $template, '2026-01-28', '2026-01');
    $pastVoid->update(['status' => FixedCostOccurenceStatus::VOID->value]);

    $results = $this->action->execute($user->id, makeFilter());

    expect($results->total())->toBe(0);
});

it('excludes past occurrences if they are not pending or overdue', function () {
    [$user, $template] = setupOccurrenceTest();

    $occ = occurrenceOn($user, $template, '2026-02-28', '2026-02');
    $occ->update(['status' => FixedCostOccurenceStatus::PAID->value]);

    $results = $this->action->execute($user->id, makeFilter());

    expect($results->total())->toBe(0);
});

it('excludes occurrences with due_date after cycle_end_date', function () {
    [$user, $template] = setupOccurrenceTest();

    occurrenceOn($user, $template, '2026-04-01', '2026-04');

    $results = $this->action->execute($user->id, makeFilter());

    expect($results->total())->toBe(0);
});

it('does not return occurrences belonging to another user', function () {
    [$user, $template] = setupOccurrenceTest();

    $otherUser = User::factory()->create();
    $otherTemplate = FixedCostTemplate::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $template->category_id,
    ]);
    UserBudgetSnapshot::factory()->create([
        'user_id' => $otherUser->id,
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 15,
    ]);

    occurrenceOn($otherUser, $otherTemplate, '2026-03-15', '2026-03');

    $results = $this->action->execute($user->id, makeFilter());

    expect($results->total())->toBe(0);
});

it('returns an empty paginator when no occurrences exist in the window', function () {
    [$user] = setupOccurrenceTest();

    $results = $this->action->execute($user->id, makeFilter());

    expect($results->total())->toBe(0);
});

it('throws ModelNotFoundException when user has no budget snapshot (not onboarded)', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id, makeFilter()))
        ->toThrow(ModelNotFoundException::class);
});

it('filters occurrences by keyword', function () {
    [$user, $template1] = setupOccurrenceTest();

    $template2 = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $template1->category_id,
    ]);

    $occ1 = occurrenceOn($user, $template1, '2026-03-10', '2026-03', overrides: ['name' => 'Netflix Subs']);
    occurrenceOn($user, $template2, '2026-03-15', '2026-03', overrides: ['name' => 'Spotify']);

    $results = $this->action->execute($user->id, makeFilter(['keyword' => 'Net']));
    $items = $results->getCollection();

    expect($items)->toHaveCount(1)
        ->and($items->first()->id)->toBe($occ1->id);
});

it('filters occurrences by status', function () {
    [$user, $template1] = setupOccurrenceTest();

    $template2 = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $template1->category_id,
    ]);

    occurrenceOn($user, $template1, '2026-03-10', '2026-03', overrides: [
        'status' => FixedCostOccurenceStatus::PENDING->value,
    ]);

    $paid = occurrenceOn($user, $template2, '2026-03-15', '2026-03', overrides: [
        'status' => FixedCostOccurenceStatus::PAID->value,
    ]);

    $results = $this->action->execute($user->id, makeFilter(['status' => FixedCostOccurenceStatus::PAID]));
    $items = $results->getCollection();

    expect($items)->toHaveCount(1)
        ->and($items->first()->id)->toBe($paid->id);
});

it('filters occurrences by strict date range bypassing snapshot limits', function () {
    [$user, $template] = setupOccurrenceTest();

    // Snapshot is March, but we are explicitly querying for May
    $mayOcc = occurrenceOn($user, $template, '2026-05-10', '2026-05');
    $marchOcc = occurrenceOn($user, $template, '2026-03-15', '2026-03');

    $results = $this->action->execute($user->id, makeFilter([
        'startDate' => '2026-05-01',
        'endDate' => '2026-05-31',
    ]));
    $items = $results->getCollection();

    expect($items)->toHaveCount(1)
        ->and($items->first()->id)->toBe($mayOcc->id);
});
