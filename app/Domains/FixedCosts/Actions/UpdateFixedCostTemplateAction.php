<?php

namespace App\Domains\FixedCosts\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\MoneyService;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\UpdateFixedCostTemplateData;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Domains\FixedCosts\Services\FixedCostValidator;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\UserBudgetSetting;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Updates a fixed cost template, respecting Business Rule §18:
 *
 * If a change to `amount` or `due_day` is requested AND a paid/confirmed
 * occurrence already exists in the current or any past cycle, those fields
 * are deferred — the template is updated but any already-settled occurrence
 * is left untouched. Only future (pending/overdue) occurrences that have NOT
 * been paid will reflect the new values immediately.
 *
 * Changes to `name`, `is_active`, `category_type`, and `category_id` are
 * always applied immediately to the template (not propagated to past occurrences
 * because occurrences store a snapshot of those values at creation time).
 */
final readonly class UpdateFixedCostTemplateAction
{
    public function __construct(
        private FixedCostValidator $fixedCostValidator,
    ) {}

    /**
     * @param  int  $userId  The authenticated user's ID.
     * @param  int  $templateId  The template to update.
     * @param  UpdateFixedCostTemplateData  $data  Sparse update payload.
     *
     * @throws ModelNotFoundException If the template does not belong to the user.
     * @throws InvalidArgumentException If validation rules are violated.
     * @throws Throwable If the DB transaction fails.
     */
    public function execute(int $userId, int $templateId, UpdateFixedCostTemplateData $data): void
    {
        $template = FixedCostTemplate::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->findOrFail($templateId);

        $budgetCycle = UserBudgetSetting::query()
            ->where('user_id', $userId)
            ->firstOrFail(['cycle_type'])
            ->cycle_type;

        $this->validatePayload($userId, $data, $budgetCycle);

        DB::transaction(function () use ($template, $data): void {
            $hasPaidOccurrenceThisCycleOrEarlier = $this->hasPaidOccurrence($template->id);

            // fields that are ALWAYS applied to the template
            $templateUpdates = [];

            if ($data->name !== null) {
                $templateUpdates['name'] = $data->name;
                // always propagate metadata changes
                FixedCostOccurrence::query()
                    ->where('fixed_cost_template_id', $template->id)
                    ->whereIn('status', [
                        FixedCostOccurenceStatus::PENDING->value,
                        FixedCostOccurenceStatus::OVERDUE->value,
                    ])
                    ->update(['name' => $data->name]);
            }

            if ($data->isActive !== null) {
                $templateUpdates['is_active'] = $data->isActive;
            }

            if ($data->categoryId !== null && $data->categoryType !== null) {
                $templateUpdates['category_id'] = $data->categoryId;
                $templateUpdates['category_type'] = $data->categoryType;
            }

            // Fields deferred when a paid occurrence already exists (BR §18)
            $isAmountChanging = $data->amount !== null
                && ! MoneyService::eq($data->amount, (string) $template->amount);

            $isDueDayChanging = $data->dueDay !== null
                && $data->dueDay !== (int) $template->due_day;

            $isCycleTypeChanging = $data->cycleType !== null
                && $data->cycleType !== $template->cycle_type;

            $sensitiveFieldsRequested = $isAmountChanging || $isDueDayChanging || $isCycleTypeChanging;

            if ($sensitiveFieldsRequested && ! $hasPaidOccurrenceThisCycleOrEarlier) {
                // Safe to apply immediately, no settled occurrences exist.
                if ($data->amount !== null) {
                    $templateUpdates['amount'] = $data->amount;
                }
                if ($data->dueDay !== null) {
                    $templateUpdates['due_day'] = $data->dueDay;
                }
                if ($data->cycleType !== null) {
                    $templateUpdates['cycle_type'] = $data->cycleType->value;
                }

                // Propagate to any unsettled occurrences immediately.
                $this->updatePendingOccurrences($template->id, $data);
            } elseif ($sensitiveFieldsRequested && $hasPaidOccurrenceThisCycleOrEarlier) {
                // BR §18: defer — template stores new value (for next cycle generation),
                // existing unsettled occurrences in the *current* cycle are left alone
                // because they were generated from the old template. The next cycle's
                // GenerateOccurrencesForBudgetWindowAction will pick up the new values.
                if ($data->amount !== null) {
                    $templateUpdates['amount'] = $data->amount;
                }
                if ($data->dueDay !== null) {
                    $templateUpdates['due_day'] = $data->dueDay;
                }
                if ($data->cycleType !== null) {
                    $templateUpdates['cycle_type'] = $data->cycleType->value;
                }
                // Intentionally not propagating to current-cycle pending occurrences.
            }

            if (! empty($templateUpdates)) {
                $template->update($templateUpdates);
            }
        });
    }

    /**
     * Determines whether any paid occurrence exists for this template
     * in the current cycle or any previous cycle.
     *
     * BR §18 uses "current or previous cycle" as the guard condition.
     */
    private function hasPaidOccurrence(int $templateId): bool
    {
        return FixedCostOccurrence::query()
            ->where('fixed_cost_template_id', $templateId)
            ->where('status', FixedCostOccurenceStatus::PAID->value)
            ->exists();
    }

    /**
     * Propagate amount/due_day changes to pending/overdue occurrences.
     *
     * Only called when there are no paid occurrences (i.e., it is safe to update).
     */
    private function updatePendingOccurrences(int $templateId, UpdateFixedCostTemplateData $data): void
    {
        $updatable = FixedCostOccurrence::query()
            ->where('fixed_cost_template_id', $templateId)
            ->whereIn('status', [
                FixedCostOccurenceStatus::PENDING->value,
                FixedCostOccurenceStatus::OVERDUE->value,
            ]);

        $occurrenceUpdates = [];

        if ($data->amount !== null) {
            $occurrenceUpdates['amount'] = $data->amount;
        }

        if (! empty($occurrenceUpdates)) {
            $updatable->update($occurrenceUpdates);
        }

        // Note: due_day changes affect the *due_date* on occurrences, which
        // requires re-resolving the date per cycle window. We leave that to
        // the next occurrence-generation pass (GenerateOccurrencesForBudgetWindowAction)
        // rather than recalculating here, keeping this action free of cycle-window math.
    }

    /**
     * Validates the incoming update payload.
     */
    private function validatePayload(int $userId, UpdateFixedCostTemplateData $data, CycleType $budgetCycle): void
    {
        if ($data->name === '') {
            throw new InvalidArgumentException('Fixed cost name cannot be empty.');
        }

        if ($data->amount !== null && MoneyService::lte($data->amount, '0')) {
            throw new InvalidArgumentException('Fixed cost amount must be greater than zero.');
        }

        $effectiveCycleType = $data->cycleType;

        if ($data->dueDay !== null) {
            $resolvedCycle = $effectiveCycleType ?? $budgetCycle;

            if ($resolvedCycle === CycleType::WEEKLY && ($data->dueDay < 1 || $data->dueDay > 7)) {
                throw new InvalidArgumentException('Weekly due day must be between 1 and 7.');
            }

            if ($resolvedCycle === CycleType::MONTHLY && ($data->dueDay < 1 || $data->dueDay > 31)) {
                throw new InvalidArgumentException('Monthly due day must be between 1 and 31.');
            }
        }

        if ($effectiveCycleType !== null) {
            $this->fixedCostValidator->validateCycleCompatibility(
                budgetCycle: $budgetCycle,
                fixedCostCycle: $effectiveCycleType,
            );
        }

        if ($data->categoryId !== null || $data->categoryType !== null) {
            if ($data->categoryId === null || $data->categoryType === null) {
                throw new BusinessRuleException('Both categoryId and categoryType must be provided together.');
            }

            $this->fixedCostValidator->validateCategory($userId, $data->categoryId, $data->categoryType);
        }
    }
}
