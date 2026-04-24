<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\Budgeting\DTOs\ResolvedCycleData;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Services\BudgetCycleWindowCalculator;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Action to generate fixed cost occurrences for the current financial cycle.
 * This service iterates through all active fixed cost templates of a user
 * and ensures that an occurrence (bill/transaction record) exists for the
 * current cycle. It handles both weekly and monthly billing logic.
 */
class GenerateOccurencesForBudgetWindowAction
{
    /**
     * @param  BudgetCycleWindowCalculator  $cycleResolverService  Service to determine cycle boundaries.
     */
    public function __construct(
        private readonly BudgetCycleWindowCalculator $cycleResolverService,
    ) {}

    /**
     * Execute the occurrence generation logic.
     *
     * @param  int  $userId  The ID of the user to process.
     * @param  CarbonImmutable|null  $now  The reference point in time (defaults to current time).
     * @param  string  $timezone  The timezone for date calculations.
     */
    public function execute(
        int $userId,
        CarbonImmutable $budgetStartDate,
        CarbonImmutable $budgetEndDate,
        ?CarbonImmutable $now = null,
        string $timezone = 'Asia/Jakarta'
    ): void {
        Log::withContext([
            'user_id' => $userId,
            'budget_start' => $budgetStartDate->toDateString(),
            'budget_end' => $budgetEndDate->toDateString(),
        ]);

        Log::info('[FixedCost][GenerateOccurencesForBudgetWindowAction] Starting budget occurrence generation.');

        $now ??= CarbonImmutable::now($timezone);
        $now = $now->startOfDay();
        $budgetStartDate = $budgetStartDate->startOfDay();
        $budgetEndDate = $budgetEndDate->startOfDay();

        $templates = FixedCostTemplate::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        Log::info('[FixedCost][GenerateOccurrences] Found active templates.', [
            'template_count' => $templates->count(),
        ]);

        foreach ($templates as $template) {
            try {
                $cycleType = $template->cycle_type;

                if ($cycleType === CycleType::MONTHLY) {
                    $this->generateMonthlyOccurences(
                        template: $template,
                        userId: $userId,
                        budgetStartDate: $budgetStartDate,
                        budgetEndDate: $budgetEndDate,
                        now: $now,
                        timezone: $timezone,
                    );

                    continue;
                }

                $this->generateWeeklyOccurrences(
                    template: $template,
                    userId: $userId,
                    budgetStartDate: $budgetStartDate,
                    budgetEndDate: $budgetEndDate,
                    now: $now,
                    timezone: $timezone,
                );

            } catch (Throwable $exception) {
                Log::error('[FixedCost][GenerateOccurrences] Failed to process template.', [
                    'templateId' => $template->id,
                    'cycleType' => $template->cycle_type->value ?? 'unknown',
                    'errorMessage' => $exception->getMessage(),
                ]);
            }

            Log::info('[FixedCost][GenerateOccurrences] Generation process finished.');
        }
    }

    /**
     * @param  CarbonImmutable  $budgetStartDate  The start date of budget cycle
     * @param  CarbonImmutable  $budgetEndDate  The end date of budget cycle
     */
    private function generateMonthlyOccurences(
        FixedCostTemplate $template,
        int $userId,
        CarbonImmutable $budgetStartDate,
        CarbonImmutable $budgetEndDate,
        CarbonImmutable $now,
        string $timezone,
    ): void {
        $monthCursor = $budgetStartDate->startOfMonth();

        $lastMonth = $budgetEndDate->startOfMonth();

        $userRegistrationDate = User::query()
            ->firstWhere('id', $userId)
            ->created_at
            ->toImmutable()
            ->timezone($timezone)
            ->startOfDay();

        while ($monthCursor->lessThanOrEqualTo($lastMonth)) {
            $cycle = $this->cycleResolverService->calculateFor(
                cycleType: CycleType::MONTHLY,
                now: $monthCursor,
                timezone: $timezone,
            );

            $dueDate = $this->resolveDueDate(
                cycleData: $cycle,
                dueDay: $template->due_day,
                cycleType: CycleType::MONTHLY,
            );

            $effectiveWindowStart = $userRegistrationDate->greaterThanOrEqualTo($budgetStartDate) ? $userRegistrationDate : $budgetStartDate;

            if ($this->isInsideBudgetWindow($dueDate, $effectiveWindowStart, $budgetEndDate)) {
                $this->createOccurrenceIfMissing(
                    template: $template,
                    userId: $userId,
                    cycleData: $cycle,
                    dueDate: $dueDate,
                    now: $now,
                );
            }

            $monthCursor = $monthCursor->addMonth()->startOfMonth();
        }
    }

    /**
     * @param  CarbonImmutable  $budgetStartDate  The start date of budget cycle
     * @param  CarbonImmutable  $budgetEndDate  The end date of budget cycle
     */
    private function generateWeeklyOccurrences(
        FixedCostTemplate $template,
        int $userId,
        CarbonImmutable $budgetStartDate,
        CarbonImmutable $budgetEndDate,
        CarbonImmutable $now,
        string $timezone,
    ): void {
        $weekCursor = $budgetStartDate->startOfWeek();

        $userRegistrationDate = User::query()
            ->firstWhere('id', $userId)
            ->created_at
            ->toImmutable()
            ->setTimezone($timezone)
            ->startOfDay();

        $templateStartDate = $template->created_at
            ->toImmutable()
            ->setTimezone($timezone)
            ->startOfDay();

        while ($weekCursor->lessThanOrEqualTo($budgetEndDate)) {
            $cycle = $this->cycleResolverService->calculateFor(
                CycleType::WEEKLY,
                $weekCursor,
                $timezone,
            );

            $dueDate = $this->resolveDueDate(
                cycleData: $cycle,
                dueDay: (int) $template->due_day,
                cycleType: CycleType::WEEKLY,
            );

            $effectiveWindowStart = $budgetStartDate
                ->max($userRegistrationDate)
                ->max($templateStartDate);

            if ($this->isInsideBudgetWindow($dueDate, $effectiveWindowStart, $budgetEndDate)) {
                $this->createOccurrenceIfMissing(
                    template: $template,
                    userId: $userId,
                    cycleData: $cycle,
                    dueDate: $dueDate,
                    now: $now,
                );
            }

            $weekCursor = $weekCursor->addWeek();
        }
    }

    /**
     * Check whether a due date is inside window budget
     */
    private function isInsideBudgetWindow(
        CarbonImmutable $date,
        CarbonImmutable $windowStart,
        CarbonImmutable $windowEnd,
    ): bool {
        return $date->greaterThanOrEqualTo($windowStart) &&
            $date->lessThanOrEqualTo($windowEnd);
    }

    private function createOccurrenceIfMissing(
        FixedCostTemplate $template,
        int $userId,
        ResolvedCycleData $cycleData,
        CarbonImmutable $dueDate,
        CarbonImmutable $now,
    ): void {
        $occurrence = FixedCostOccurrence::query()->firstOrCreate(
            [
                'fixed_cost_template_id' => $template->id,
                'cycle_key' => $cycleData->cycleKey,
            ],
            [
                'user_id' => $userId,
                'cycle_type' => $template->cycle_type->value,
                'cycle_key' => $cycleData->cycleKey,
                'due_date' => $dueDate->toDateString(),
                'status' => $now->greaterThan($dueDate)
                    ? FixedCostOccurenceStatus::OVERDUE->value
                    : FixedCostOccurenceStatus::PENDING->value,
                'amount' => (string) $template->amount,
                'name' => $template->name,
                'category_type' => $template->category_type,
                'category_id' => $template->category_id,
            ]
        );

        Log::info('[FixedCost][GenerateOccurrences] Created new occurrence.', [
            'template_id' => $template->id,
            'cycle_key' => $cycleData->cycleKey,
            'due_date' => $dueDate->toDateString(),
            'status' => $occurrence->status,
        ]);
    }

    /**
     * Resolve the exact due date based on the cycle boundaries and the template's due day.
     *
     * @param  ResolvedCycleData  $cycleData  DTO containing start and end dates of the cycle.
     * @param  int  $dueDay  The day of the month (1-31) or week (1-7) when the cost is due.
     * @param  CycleType  $cycleType  The frequency type (Monthly or Weekly).
     * @return CarbonImmutable The calculated due date.
     */
    private function resolveDueDate(ResolvedCycleData $cycleData, int $dueDay, CycleType $cycleType): CarbonImmutable
    {
        // monthly
        if ($cycleType === CycleType::MONTHLY) {
            // resolve when due day is not available on a specific month
            $normalizedDueDay = min($dueDay, $cycleData->startDate->daysInMonth);

            return $cycleData->startDate->day($normalizedDueDay);
        }

        /**
         * weekly: 1 = Mon ... 7 = Sun
         * keep due day gte 1 and lte 7
         */
        $normalizedDueDay = max(1, min($dueDay, 7));

        return $cycleData->startDate->addDays($normalizedDueDay - 1);
    }
}
