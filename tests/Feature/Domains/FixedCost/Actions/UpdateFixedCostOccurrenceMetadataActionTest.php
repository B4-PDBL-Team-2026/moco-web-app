<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\UpdateFixedCostOccurrenceMetadataAction;
use App\Domains\FixedCost\DTOs\UpdateFixedCostOccurrenceMetadataData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

function setupForMetadata(): array
{
    $user = User::factory()->create();
    $cat = Category::factory()->expense()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'category_id' => $cat->id,
    ]);

    $snapshot = UserBudgetSnapshot::factory()->create([
        'user_id' => $user->id,
        'current_balance' => '500000.00',
        'reserved_cost' => '150000.00',
        'remaining_daily_allowance' => '30000.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 10,
    ]);

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => '2026-03',
        'cycle_type' => 'monthly',
        'due_date' => '2026-03-15',
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '150000.00',
        'name' => 'Old Name',
        'category_id' => $cat->id,
    ]);

    return [$user, $occurrence, $snapshot];
}

function makeMetadataDto(array $overrides = []): UpdateFixedCostOccurrenceMetadataData
{
    $name = array_key_exists('name', $overrides) ? $overrides['name'] : null;
    $note = array_key_exists('note', $overrides) ? $overrides['note'] : null;

    return new UpdateFixedCostOccurrenceMetadataData(
        name: $name,
        note: $note,
    );
}

beforeEach(function () {
    $this->action = app(UpdateFixedCostOccurrenceMetadataAction::class);
});

it('updates the occurrence name', function () {
    [$user, $occurrence] = setupForMetadata();

    $this->action->execute($user->id, $occurrence->id, makeMetadataDto(['name' => 'New Name']));

    expect($occurrence->fresh()->name)->toBe('New Name');
});

it('updates the occurrence note', function () {
    [$user, $occurrence] = setupForMetadata();

    $this->action->execute($user->id, $occurrence->id, makeMetadataDto(['note' => 'Paid via transfer']));

    expect($occurrence->fresh()->note)->toBe('Paid via transfer');
});

it('updates both name and note at once', function () {
    [$user, $occurrence] = setupForMetadata();

    $this->action->execute($user->id, $occurrence->id, makeMetadataDto([
        'name' => 'Updated',
        'note' => 'Some note',
    ]));

    expect($occurrence->fresh()->name)->toBe('Updated')
        ->and($occurrence->fresh()->note)->toBe('Some note');
});

it('accepts a null note to clear it or ignore it', function () {
    [$user, $occurrence] = setupForMetadata();
    $occurrence->update(['note' => 'Existing note']);

    $this->action->execute($user->id, $occurrence->id, makeMetadataDto(['note' => null]));

    expect($occurrence->fresh()->note)->toBe('Existing note');
});

it('does not change amount, status, or financial fields (BR §15)', function () {
    [$user, $occurrence, $snapshot] = setupForMetadata();

    $this->action->execute($user->id, $occurrence->id, makeMetadataDto(['name' => 'Changed Name']));

    // Financial fields must remain identical
    expect((string) $occurrence->fresh()->amount)->toBe('150000.00')
        ->and($occurrence->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);

    // Snapshot must be untouched
    $freshSnap = $snapshot->fresh();
    expect((string) $freshSnap->current_balance)->toBe('500000.00')
        ->and((string) $freshSnap->reserved_cost)->toBe('150000.00')
        ->and((string) $freshSnap->remaining_daily_allowance)->toBe('30000.00');
});

it('works on occurrences of any status (no status restriction)', function () {
    foreach (FixedCostOccurenceStatus::cases() as $status) {
        [$user, $occurrence] = setupForMetadata();
        $occurrence->update(['status' => $status->value]);

        $this->action->execute($user->id, $occurrence->id, makeMetadataDto(['name' => 'Updated for '.$status->value]));

        expect($occurrence->fresh()->name)->toBe('Updated for '.$status->value);
    }
});

it('throws BusinessRuleException when name is set to an empty string', function () {
    [$user, $occurrence] = setupForMetadata();

    expect(fn () => $this->action->execute($user->id, $occurrence->id, makeMetadataDto(['name' => '   '])))
        ->toThrow(BusinessRuleException::class, 'error.validation.empty');
});

it('throws ModelNotFoundException when occurrence belongs to another user', function () {
    [$_, $occurrence] = setupForMetadata();
    $otherUser = User::factory()->create();

    expect(fn () => $this->action->execute($otherUser->id, $occurrence->id, makeMetadataDto(['name' => 'X'])))
        ->toThrow(ModelNotFoundException::class);
});

it('throws ModelNotFoundException for a non-existent occurrence id', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id, 99999, makeMetadataDto(['name' => 'X'])))
        ->toThrow(ModelNotFoundException::class);
});
