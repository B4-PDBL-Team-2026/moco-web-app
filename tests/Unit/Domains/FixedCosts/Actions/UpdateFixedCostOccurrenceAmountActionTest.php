<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Category\Models\Category;
use App\Domains\FixedCost\Actions\UpdateFixedCostOccurrenceAmountAction;
use App\Domains\FixedCost\DTOs\UpdateFixedCostOccurrenceAmountData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCost\Models\FixedCostOccurrence;
use App\Domains\FixedCost\Models\FixedCostTemplate;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

function setupUserWithBalance(string $balanceAmount): array
{
    $user = User::factory()->create();

    $category = Category::factory()->expense()->create();

    UserBudgetSetting::query()->create([
        'user_id' => $user->id,
        'cycle_type' => CycleType::MONTHLY->value,
        'initial_balance' => '0.00',
        'flooring_limit' => '0.00',
        'ceiling_limit' => '999999.00',
        'timezone' => 'Asia/Jakarta',
    ]);

    UserBudgetSnapshot::query()->create([
        'user_id' => $user->id,
        'current_balance' => $balanceAmount,
        'reserved_cost' => '0.00',
        'remaining_daily_allowance' => '10.00',
        'daily_allowance_limit' => '10.00',
        'raw_daily_allowance' => '10.00',
        'current_cycle_key' => '2026-03',
        'cycle_start_date' => '2026-03-01',
        'cycle_end_date' => '2026-03-31',
        'remaining_days' => 15,
        'recalculated_at' => now(),
    ]);

    $template = FixedCostTemplate::factory()->create([
        'user_id' => $user->id,

        'category_id' => $category->id,
    ]);

    return [$user, $category, $template];
}

beforeEach(function () {
    $this->action = app(UpdateFixedCostOccurrenceAmountAction::class);
});

it('updates the amount of a PENDING occurrence directly', function () {
    [$user, $category, $template] = setupUserWithBalance('1000.00');

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'status' => FixedCostOccurenceStatus::PENDING->value,
        'amount' => '150000.00',
    ]);

    $this->action->execute($user->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('200000.00'));

    expect($occurrence->fresh()->amount)->toBe('200000.00');
});

it('updates the amount of an OVERDUE occurrence directly', function () {
    [$user, $category, $template] = setupUserWithBalance('1000.00');

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'status' => FixedCostOccurenceStatus::OVERDUE->value,
        'amount' => '150000.00',
    ]);

    $this->action->execute($user->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('200000.00'));

    expect($occurrence->fresh()->amount)->toBe('200000.00');
});

it('updates a PAID occurrence and intelligently syncs the linked transaction', function () {
    [$user, $category, $template] = setupUserWithBalance('500000.00');

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '150000.00',
    ]);

    $transaction = Transaction::query()->create([
        'user_id' => $user->id,

        'category_id' => $category->id,
        'fixed_cost_occurrence_id' => $occurrence->id,
        'type' => TransactionType::EXPENSE->value,
        'source' => TransactionSource::FIXED_COST_PAYMENT->value,
        'name' => 'Paid Gym',
        'amount' => '150000.00',
        'transaction_at' => now(),
    ]);

    $this->action->execute($user->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('200000.00'));

    expect($occurrence->fresh()->amount)->toBe('200000.00')
        ->and($transaction->fresh()->amount)->toBe('200000.00');
});

it('throws BusinessRuleException when increasing a PAID occurrence exceeds current balance', function () {
    [$user, $category, $template] = setupUserWithBalance('50000.00');

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'status' => FixedCostOccurenceStatus::PAID->value,
        'amount' => '150000.00',
    ]);

    $dto = new UpdateFixedCostOccurrenceAmountData('300000.00');

    expect(fn () => $this->action->execute($user->id, $occurrence->id, $dto))
        ->toThrow(BusinessRuleException::class);
});

it('throws ModelNotFoundException when occurrence is VOID', function () {
    [$user, $category, $template] = setupUserWithBalance('1000.00');

    $occurrence = FixedCostOccurrence::factory()->create([
        'user_id' => $user->id,
        'fixed_cost_template_id' => $template->id,
        'status' => FixedCostOccurenceStatus::VOID->value,
        'amount' => '150000.00',
    ]);

    expect(fn () => $this->action->execute($user->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('200000.00')))
        ->toThrow(ModelNotFoundException::class);
});

it('throws InvalidArgumentException when amount is zero', function () {
    [$user, $category, $template] = setupUserWithBalance('1000.00');
    $occurrence = FixedCostOccurrence::factory()->create(['user_id' => $user->id, 'status' => FixedCostOccurenceStatus::PENDING->value]);

    expect(fn () => $this->action->execute($user->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('0')))
        ->toThrow(InvalidArgumentException::class, 'must be greater than zero');
});

it('throws InvalidArgumentException when amount is negative', function () {
    [$user, $category, $template] = setupUserWithBalance('1000.00');
    $occurrence = FixedCostOccurrence::factory()->create(['user_id' => $user->id, 'status' => FixedCostOccurenceStatus::PENDING->value]);

    expect(fn () => $this->action->execute($user->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('-500')))
        ->toThrow(InvalidArgumentException::class, 'must be greater than zero');
});

it('throws ModelNotFoundException when occurrence belongs to another user', function () {
    [$user1, $category, $template] = setupUserWithBalance('1000.00');
    $occurrence = FixedCostOccurrence::factory()->create(['user_id' => $user1->id, 'status' => FixedCostOccurenceStatus::PENDING->value]);

    $otherUser = User::factory()->create();

    expect(fn () => $this->action->execute($otherUser->id, $occurrence->id, new UpdateFixedCostOccurrenceAmountData('200000.00')))
        ->toThrow(ModelNotFoundException::class);
});
