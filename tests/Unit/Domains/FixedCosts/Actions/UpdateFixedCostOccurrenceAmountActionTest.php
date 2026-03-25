<?php

use App\Domains\FixedCosts\Actions\UpdateFixedCostOccurrenceAmountAction;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostOccurrenceAmountData;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;

function setupForAmountUpdate(): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);

    // Starts VOID — as required by BR §17 (caller must cancel first)
    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => FixedCostOccurenceStatus::VOID->value,
        'amount' => '150000.00',
        'name' => 'Gym',
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
        'voided_at' => now()->subMinutes(5),
    ]);

    return [$user, $occurrence];
}

beforeEach(function () {
    $this->action = app(UpdateFixedCostOccurrenceAmountAction::class);
});

it('updates the amount of a VOID occurrence', function () {
    [$user, $occ] = setupForAmountUpdate();

    $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000.00'));

    expect($occ->fresh()->amount)->toBe('200000.00');
});

it('clears voided_at after amount update (ready for re-confirmation)', function () {
    [$user, $occ] = setupForAmountUpdate();

    $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000.00'));

    expect($occ->fresh()->voided_at)->toBeNull();
});

it('occurrence remains in VOID status after amount update', function () {
    // Status should stay VOID — the caller must follow up with ConfirmFixedCostPaymentAction
    [$user, $occ] = setupForAmountUpdate();

    $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000.00'));

    expect($occ->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('accepts a minimal positive amount (1 unit)', function () {
    [$user, $occ] = setupForAmountUpdate();

    $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('0.01'));

    expect($occ->fresh()->amount)->toBe('0.01');
});

it('throws ModelNotFoundException when occurrence is PAID (cancel must be called first)', function () {
    [$user, $occ] = setupForAmountUpdate();
    $occ->update(['status' => FixedCostOccurenceStatus::PAID->value]);

    expect(fn () => $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000')))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when occurrence is PENDING', function () {
    [$user, $occ] = setupForAmountUpdate();
    $occ->update(['status' => FixedCostOccurenceStatus::PENDING->value]);

    expect(fn () => $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000')))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when occurrence is OVERDUE', function () {
    [$user, $occ] = setupForAmountUpdate();
    $occ->update(['status' => FixedCostOccurenceStatus::OVERDUE->value]);

    expect(fn () => $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000')))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws InvalidArgumentException when amount is zero', function () {
    [$user, $occ] = setupForAmountUpdate();

    expect(fn () => $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('0')))
        ->toThrow(InvalidArgumentException::class, 'must be greater than zero');
});

it('throws InvalidArgumentException when amount is negative', function () {
    [$user, $occ] = setupForAmountUpdate();

    expect(fn () => $this->action->execute($user->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('-500')))
        ->toThrow(InvalidArgumentException::class, 'must be greater than zero');
});

it('throws ModelNotFoundException when occurrence belongs to another user', function () {
    [$_, $occ] = setupForAmountUpdate();
    $otherUser = User::factory()->create();

    expect(fn () => $this->action->execute($otherUser->id, $occ->id, new UpdateFixedCostOccurrenceAmountData('200000')))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException for a non-existent occurrence id', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id, 99999, new UpdateFixedCostOccurrenceAmountData('200000')))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
