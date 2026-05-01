<?php

use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\ListCurrentCycleOccurrencesAction;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

function setupForList(): array
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

function occurrenceOn(User $user, FixedCostTemplate $template, string $dueDate, string $cycleKey, string $cycleType = 'monthly'): FixedCostOccurrence
{
    return FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => $cycleKey,
        'cycle_type' => $cycleType,
        'due_date' => $dueDate,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '100000.00',
        'name' => 'Test',

        'category_id' => $template->category_id,
    ]);
}

beforeEach(function () {
    $this->action = app(ListCurrentCycleOccurrencesAction::class);
});

it('returns monthly occurrences whose due_date falls within the budget window', function () {
    [$user, $template] = setupForList();

    $occ = occurrenceOn($user, $template, '2026-03-15', '2026-03', 'monthly');

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($occ->id);
});

it('returns weekly occurrences whose due_date falls within the monthly budget window', function () {
    // This is the key test: weekly cycle_keys (2026-W10, W11, W12, W13) must all be
    // included even though the snapshot's current_cycle_key is "2026-03" (monthly).
    [$user, $template] = setupForList();

    $week10 = occurrenceOn($user, $template, '2026-03-03', '2026-W10', 'weekly');
    $week11 = occurrenceOn($user, $template, '2026-03-10', '2026-W11', 'weekly');
    $week13 = occurrenceOn($user, $template, '2026-03-24', '2026-W13', 'weekly');

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(3);
    expect($results->pluck('id')->sort()->values())->toEqual(
        collect([$week10->id, $week11->id, $week13->id])->sort()->values()
    );
});

it('returns both monthly and weekly occurrences within the same budget window', function () {
    [$user, $template] = setupForList();

    $monthly = occurrenceOn($user, $template, '2026-03-15', '2026-03', 'monthly');
    $weekly = occurrenceOn($user, $template, '2026-03-10', '2026-W11', 'weekly');

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->sort()->values())->toEqual(
        collect([$monthly->id, $weekly->id])->sort()->values()
    );
});

it('returns occurrences ordered by due_date ascending', function () {
    [$user, $template] = setupForList();

    $late = occurrenceOn($user, $template, '2026-03-25', '2026-W13', 'weekly');
    $early = occurrenceOn($user, $template, '2026-03-05', '2026-W10', 'weekly');
    $mid = occurrenceOn($user, $template, '2026-03-15', '2026-03', 'monthly');

    $results = $this->action->execute($user->id);

    expect($results->pluck('id')->toArray())->toBe([$early->id, $mid->id, $late->id]);
});

it('includes occurrences with due_date exactly on cycle_start_date', function () {
    [$user, $template] = setupForList();

    $occ = occurrenceOn($user, $template, '2026-03-01', '2026-03');

    $results = $this->action->execute($user->id);

    expect($results->first()->id)->toBe($occ->id);
});

it('includes occurrences with due_date exactly on cycle_end_date', function () {
    [$user, $template] = setupForList();

    $occ = occurrenceOn($user, $template, '2026-03-31', '2026-03');

    $results = $this->action->execute($user->id);

    expect($results->first()->id)->toBe($occ->id);
});

it('includes previous overdue occurrences (carried over debt)', function () {
    [$user, $template] = setupForList();

    // Past occurrence, but still pending -> MUST be included
    $pastPending = occurrenceOn($user, $template, '2026-02-15', '2026-02');
    $pastPending->update(['status' => FixedCostOccurenceStatus::OVERDUE->value]);

    // Current occurrence -> MUST be included
    $current = occurrenceOn($user, $template, '2026-03-15', '2026-03');

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(2)
        ->and($results->pluck('id')->toArray())->toBe([$pastPending->id, $current->id]);
});

it('excludes previous PAID or VOID occurrences', function () {
    [$user, $template] = setupForList();

    // Past occurrence, already paid -> Must NOT be included
    $pastPaid = occurrenceOn($user, $template, '2026-02-15', '2026-02');
    $pastPaid->update(['status' => FixedCostOccurenceStatus::PAID->value]);

    // Past occurrence, voided -> Must NOT be included
    $pastVoid = occurrenceOn($user, $template, '2026-01-28', '2026-01');
    $pastVoid->update(['status' => FixedCostOccurenceStatus::VOID->value]);

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(0);
});

it('excludes past occurrences if they are not pending or overdue', function () {
    [$user, $template] = setupForList();

    $occ = occurrenceOn($user, $template, '2026-02-28', '2026-02'); // previous month
    $occ->update(['status' => FixedCostOccurenceStatus::PAID->value]); // Make it paid

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(0);
});

it('excludes occurrences with due_date after cycle_end_date', function () {
    [$user, $template] = setupForList();

    occurrenceOn($user, $template, '2026-04-01', '2026-04'); // next month

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(0);
});

it('does not return occurrences belonging to another user', function () {
    [$user, $template] = setupForList();

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

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(0);
});

it('returns an empty collection when no occurrences exist in the window', function () {
    [$user] = setupForList();

    $results = $this->action->execute($user->id);

    expect($results)->toHaveCount(0);
});

it('throws ModelNotFoundException when user has no budget snapshot (not onboarded)', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id))
        ->toThrow(ModelNotFoundException::class);
});
