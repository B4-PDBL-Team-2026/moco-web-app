<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\UpdateFixedCostTemplateAction;
use App\Domains\FixedCost\DTOs\UpdateFixedCostTemplateData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

function setupUserForUpdate(string $cycleType = 'monthly'): array
{
    $user = User::factory()->create(['has_onboarded' => true]);

    UserBudgetSetting::factory()->create([
        'user_id' => $user->id,
        'cycle_type' => $cycleType,
        'ceiling_limit' => '500000.00',
        'flooring_limit' => '10000.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    // Udah pake Category biasa (System Category)
    $category = Category::factory()->expense()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'amount' => '150000.00',
        'cycle_type' => 'monthly',
        'due_day' => 15,
        'is_active' => true,
        'category_id' => $category->id,
    ]);

    return [$user, $template, $category];
}

function makeOccurrenceForUpdate(
    User $user,
    FixedCostTemplate $template,
    string $status,
    string $amount = '150000.00',
    string $cycleKey = '2026-03',
    string $dueDate = '2026-03-15',
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
        'category_id' => $template->category_id,
    ]);
}

beforeEach(function () {
    $this->action = app(UpdateFixedCostTemplateAction::class);
});

it('updates the template name immediately', function () {
    [$user, $template] = setupUserForUpdate();

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['name' => 'Spotify']));

    expect($template->fresh()->name)->toBe('Spotify');
});

it('updates is_active immediately', function () {
    [$user, $template] = setupUserForUpdate();

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['isActive' => false]));

    expect($template->fresh()->is_active)->toBeFalse();
});

it('updates category immediately', function () {
    [$user, $template] = setupUserForUpdate();
    $newCat = Category::factory()->expense()->create();

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray([
        'categoryId' => $newCat->id,
    ]));

    expect($template->fresh()->category_id)->toBe($newCat->id);
});

it('updates template amount and propagates to pending occurrences when no paid occurrences exist', function () {
    [$user, $template] = setupUserForUpdate();
    $occ = makeOccurrenceForUpdate($user, $template, FixedCostOccurenceStatus::PENDING->value);

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['amount' => '200000']));

    expect($template->fresh()->amount)->toBe('200000.00')
        ->and($occ->fresh()->amount)->toBe('200000.00');
});

it('updates template due_day and updates pending occurrences name if name changes', function () {
    [$user, $template] = setupUserForUpdate();
    makeOccurrenceForUpdate($user, $template, FixedCostOccurenceStatus::PENDING->value);

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray([
        'dueDay' => 20,
        'name' => 'Netflix HD',
    ]));

    expect($template->fresh()->due_day)->toBe(20);
});

it('propagates name change to pending occurrences', function () {
    [$user, $template] = setupUserForUpdate();
    $occ = makeOccurrenceForUpdate($user, $template, FixedCostOccurenceStatus::PENDING->value);

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['name' => 'Netflix HD']));

    expect($occ->fresh()->name)->toBe('Netflix HD');
});

it('does not propagate amount to void occurrences (only pending/overdue)', function () {
    [$user, $template] = setupUserForUpdate();
    $voidOcc = makeOccurrenceForUpdate($user, $template, FixedCostOccurenceStatus::VOID->value, '150000.00');

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['amount' => '200000']));

    expect($voidOcc->fresh()->amount)->toBe('150000.00');
});

it('updates template amount but does NOT propagate to pending occurrences when a paid occurrence exists', function () {
    [$user, $template] = setupUserForUpdate();
    makeOccurrenceForUpdate(
        $user, $template, FixedCostOccurenceStatus::PAID->value, '150000.00', '2026-02', '2026-02-15'
    );

    $pendingOcc = makeOccurrenceForUpdate(
        $user, $template, FixedCostOccurenceStatus::PENDING->value, '150000.00', '2026-03', '2026-03-15'
    );

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['amount' => '200000']));

    expect($template->fresh()->amount)->toBe('200000.00')
        ->and($pendingOcc->fresh()->amount)->toBe('150000.00');
});

it('updates template due_day but does NOT propagate when a paid occurrence exists (BR §18)', function () {
    [$user, $template] = setupUserForUpdate();
    makeOccurrenceForUpdate($user, $template, FixedCostOccurenceStatus::PAID->value);

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['dueDay' => 25]));

    expect($template->fresh()->due_day)->toBe(25);
});

it('applies non-financial fields (name) even when paid occurrence exists', function () {
    [$user, $template] = setupUserForUpdate();
    makeOccurrenceForUpdate($user, $template, FixedCostOccurenceStatus::PAID->value);

    $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['name' => 'Netflix 4K']));

    expect($template->fresh()->name)->toBe('Netflix 4K');
});

it('throws BusinessRuleException when updated name is empty', function () {
    [$user, $template] = setupUserForUpdate();

    expect(fn () => $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['name' => ''])))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when updated amount is zero', function () {
    [$user, $template] = setupUserForUpdate();

    expect(fn () => $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['amount' => '0'])))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when weekly due_day exceeds 7', function () {
    [$user, $template] = setupUserForUpdate('weekly');
    $template->update(['cycle_type' => 'weekly', 'due_day' => 1]); // Prepare weekly template

    expect(fn () => $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray([
        'cycleType' => 'weekly',
        'dueDay' => 8,
    ])))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when changing to monthly cycle on a weekly budget', function () {
    [$user, $template] = setupUserForUpdate('weekly');
    $template->update(['cycle_type' => 'weekly', 'due_day' => 1]);

    expect(fn () => $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['cycleType' => 'monthly'])))
        ->toThrow(BusinessRuleException::class);
});

it('throws BusinessRuleException when custom category belongs to another user', function () {
    [$user, $template] = setupUserForUpdate();
    $otherUser = User::factory()->create();

    // Bikin category yg punyanya user lain (custom category user lain)
    $otherCategory = Category::factory()->custom($otherUser)->expense()->create();

    expect(fn () => $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray([
        'categoryId' => $otherCategory->id,
    ])))
        ->toThrow(BusinessRuleException::class);
});

it('throws ModelNotFoundException when template belongs to another user', function () {
    [$_, $template] = setupUserForUpdate();
    $otherUser = User::factory()->create();

    UserBudgetSetting::factory()->create([
        'user_id' => $otherUser->id,
        'cycle_type' => 'monthly',
    ]);

    expect(fn () => $this->action->execute($otherUser->id, $template->id, UpdateFixedCostTemplateData::fromArray(['name' => 'X'])))
        ->toThrow(ModelNotFoundException::class);
});

it('throws ModelNotFoundException for a soft-deleted template', function () {
    [$user, $template] = setupUserForUpdate();
    $template->delete();

    expect(fn () => $this->action->execute($user->id, $template->id, UpdateFixedCostTemplateData::fromArray(['name' => 'X'])))
        ->toThrow(ModelNotFoundException::class);
});
