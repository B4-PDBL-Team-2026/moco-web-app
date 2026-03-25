<?php

use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCosts\Services\FixedCostProvisionService;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;
use Carbon\Carbon;

function makeUserRegisteredOn(string $date): User
{
    return User::factory()->create([
        'created_at' => Carbon::parse($date)->startOfDay(),
    ]);
}

function makeTemplate(User $user, SystemCategory $cat): FixedCostTemplate
{
    return FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'amount' => '200000.00',
        'cycle_type' => 'monthly',
        'due_day' => 15,
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);
}

function makeOccurrence(
    User $user,
    FixedCostTemplate $template,
    string $status,
    string $dueDate,
    string $cycleKey = '2026-03',
    string $amount = '200000.00',
): FixedCostOccurrence {
    return FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => $cycleKey,
        'cycle_type' => 'monthly',
        'due_date' => $dueDate,
        'status' => $status,
        'amount' => $amount,
        'name' => 'Netflix',
        'category_type' => SystemCategory::class,
        'category_id' => $template->category_id,
    ]);
}

beforeEach(function () {
    $this->service = new FixedCostProvisionService;
    $this->category = SystemCategory::factory()->create();
});

it('sums pending occurrences due on or after registration date', function () {
    $user = makeUserRegisteredOn('2026-03-01');

    $template1 = makeTemplate($user, $this->category);
    $template2 = makeTemplate($user, $this->category);

    makeOccurrence($user, $template1, FixedCostOccurenceStatus::PENDING->value, '2026-03-15');
    makeOccurrence($user, $template2, FixedCostOccurenceStatus::PENDING->value, '2026-03-20', '2026-03', '100000.00');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('300000.00');
});

it('includes overdue occurrences due on or after registration date', function () {
    $user = makeUserRegisteredOn('2026-03-01');
    $template = makeTemplate($user, $this->category);

    makeOccurrence($user, $template, FixedCostOccurenceStatus::OVERDUE->value, '2026-03-10');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('200000.00');
});

it('excludes paid occurrences from the reserved cost', function () {
    $user = makeUserRegisteredOn('2026-03-01');
    $template = makeTemplate($user, $this->category);

    makeOccurrence($user, $template, FixedCostOccurenceStatus::PAID->value, '2026-03-15');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('0.00');
});

it('excludes void occurrences from the reserved cost', function () {
    $user = makeUserRegisteredOn('2026-03-01');
    $template = makeTemplate($user, $this->category);

    makeOccurrence($user, $template, FixedCostOccurenceStatus::VOID->value, '2026-03-15');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('0.00');
});

it('excludes occurrences due before registration date', function () {
    $user = makeUserRegisteredOn('2026-03-10');
    $template = makeTemplate($user, $this->category);

    makeOccurrence($user, $template, FixedCostOccurenceStatus::PENDING->value, '2026-03-05');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('0.00');
});

it('includes occurrences due exactly on registration date', function () {
    $user = makeUserRegisteredOn('2026-03-10');
    $template = makeTemplate($user, $this->category);

    makeOccurrence($user, $template, FixedCostOccurenceStatus::PENDING->value, '2026-03-10');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('200000.00');
});

it('includes overdue occurrence that happened after registration', function () {
    $user = makeUserRegisteredOn('2026-03-01');
    $template = makeTemplate($user, $this->category);

    makeOccurrence($user, $template, FixedCostOccurenceStatus::OVERDUE->value, '2026-03-05');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('200000.00');
});

it('only counts occurrences belonging to the given cycle_key', function () {
    $user = makeUserRegisteredOn('2026-02-01');
    $template = makeTemplate($user, $this->category);

    // Ini AMAN pakai 1 template karena cycle_key-nya beda ('2026-02' vs '2026-03')
    makeOccurrence($user, $template, FixedCostOccurenceStatus::PENDING->value, '2026-02-15', '2026-02');
    makeOccurrence($user, $template, FixedCostOccurenceStatus::PENDING->value, '2026-03-15', '2026-03');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('200000.00');
});

it('returns 0.00 when there are no qualifying occurrences', function () {
    $user = makeUserRegisteredOn('2026-03-01');
    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('0.00');
});

it('does not count occurrences belonging to another user', function () {
    $user1 = makeUserRegisteredOn('2026-03-01');
    $user2 = makeUserRegisteredOn('2026-03-01');
    $template = makeTemplate($user2, $this->category);

    makeOccurrence($user2, $template, FixedCostOccurenceStatus::PENDING->value, '2026-03-15');

    $result = $this->service->calculateReservedCost($user1->id, '2026-03');

    expect($result)->toBe('0.00');
});

it('correctly sums multiple occurrences using bcmath precision', function () {
    $user = makeUserRegisteredOn('2026-03-01');

    $template1 = makeTemplate($user, $this->category);
    $template2 = makeTemplate($user, $this->category);

    makeOccurrence($user, $template1, FixedCostOccurenceStatus::PENDING->value, '2026-03-10', '2026-03', '99999.99');
    makeOccurrence($user, $template2, FixedCostOccurenceStatus::PENDING->value, '2026-03-20', '2026-03', '0.01');

    $result = $this->service->calculateReservedCost($user->id, '2026-03');

    expect($result)->toBe('100000.00');
});
