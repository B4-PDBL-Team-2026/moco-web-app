<?php

use App\Domains\FixedCosts\Actions\DeleteFixedCostTemplateAction;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use App\Models\User;

function setupForDelete(): array
{
    $user = User::factory()->create();
    $cat = SystemCategory::factory()->create();
    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Electricity',
        'amount' => '300000.00',
        'cycle_type' => 'monthly',
        'due_day' => 10,
        'category_type' => SystemCategory::class,
        'category_id' => $cat->id,
    ]);

    return [$user, $template, $cat];
}

function addOccurrence(
    User $user,
    FixedCostTemplate $template,
    string $status,
    string $cycleKey = '2026-03',
    string $dueDate = '2026-03-15'
): FixedCostOccurrence {
    return FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'cycle_key' => $cycleKey,
        'cycle_type' => 'monthly',
        'due_date' => $dueDate,
        'status' => $status,
        'amount' => '300000.00',
        'name' => 'Electricity',
        'category_type' => SystemCategory::class,
        'category_id' => $template->category_id,
    ]);
}

beforeEach(function () {
    $this->action = app(DeleteFixedCostTemplateAction::class);
});

it('soft-deletes the template (sets deleted_at)', function () {
    [$user, $template] = setupForDelete();

    $this->action->execute($user->id, $template->id);

    $this->assertSoftDeleted('fixed_cost_templates', ['id' => $template->id]);
});

it('template is no longer retrievable via normal queries after deletion', function () {
    [$user, $template] = setupForDelete();

    $this->action->execute($user->id, $template->id);

    expect(FixedCostTemplate::find($template->id))->toBeNull();
});

it('voids PENDING occurrences and sets voided_at', function () {
    [$user, $template] = setupForDelete();
    $occ = addOccurrence($user, $template, FixedCostOccurenceStatus::PENDING->value);

    $this->action->execute($user->id, $template->id);

    $fresh = $occ->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::VOID)
        ->and($fresh->voided_at)->not->toBeNull();
});

it('voids OVERDUE occurrences', function () {
    [$user, $template] = setupForDelete();
    $occ = addOccurrence($user, $template, FixedCostOccurenceStatus::OVERDUE->value);

    $this->action->execute($user->id, $template->id);

    expect($occ->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID);
});

it('does NOT touch PAID occurrences (preserved for history)', function () {
    [$user, $template] = setupForDelete();
    $paid = addOccurrence($user, $template, FixedCostOccurenceStatus::PAID->value);

    $this->action->execute($user->id, $template->id);

    expect($paid->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID)
        ->and($paid->fresh()->voided_at)->toBeNull();
});

it('does NOT re-void already VOID occurrences', function () {
    [$user, $template] = setupForDelete();
    $alreadyVoid = addOccurrence($user, $template, FixedCostOccurenceStatus::VOID->value);
    // Set a specific voided_at to detect if it gets overwritten
    $originalVoidedAt = now()->subDays(5);
    $alreadyVoid->update(['voided_at' => $originalVoidedAt]);

    $this->action->execute($user->id, $template->id);

    // voided_at should remain from the original voiding, not be re-stamped
    $fresh = $alreadyVoid->fresh();
    expect($fresh->status)->toBe(FixedCostOccurenceStatus::VOID)
        ->and($fresh->voided_at->toDateString())->toBe($originalVoidedAt->toDateString());
});

it('handles deletion with multiple occurrences of mixed statuses correctly', function () {
    [$user, $template] = setupForDelete();

    $paid = addOccurrence($user, $template, FixedCostOccurenceStatus::PAID->value, '2026-01', '2026-01-15');
    $overdue = addOccurrence($user, $template, FixedCostOccurenceStatus::OVERDUE->value, '2026-02', '2026-02-15');
    $pending = addOccurrence($user, $template, FixedCostOccurenceStatus::PENDING->value, '2026-03', '2026-03-15');

    $this->action->execute($user->id, $template->id);

    expect($pending->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID)
        ->and($overdue->fresh()->status)->toBe(FixedCostOccurenceStatus::VOID)
        ->and($paid->fresh()->status)->toBe(FixedCostOccurenceStatus::PAID);
});

it('throws ModelNotFoundException when template belongs to another user', function () {
    [$_, $template] = setupForDelete();
    $otherUser = User::factory()->create();

    expect(fn () => $this->action->execute($otherUser->id, $template->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException when template is already soft-deleted', function () {
    [$user, $template] = setupForDelete();
    $template->delete();

    expect(fn () => $this->action->execute($user->id, $template->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('throws ModelNotFoundException for a non-existent template id', function () {
    $user = User::factory()->create();

    expect(fn () => $this->action->execute($user->id, 99999))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
