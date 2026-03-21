<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\Budgeting\DTOs\ResolvedCycleData;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Services\CycleResolverService;
use App\Domains\FixedCosts\Enums\FixedCostOccurenceStatus;
use App\Models\FixedCostOccurrence;
use App\Models\FixedCostTemplate;
use Carbon\CarbonImmutable;

final readonly class GenerateCurrentCycleOccurrencesAction
{
    public function __construct(
        private CycleResolverService $cycleResolverService,
    ) {}

    public function execute(int $userId, ?CarbonImmutable $now = null, string $timezone = 'Asia/Jakarta'): void
    {
        $now ??= CarbonImmutable::now($timezone);

        $templates = FixedCostTemplate::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        foreach ($templates as $template) {
            /** @var CycleType $cycleType */
            $cycleType = $template->cycle_type;

            $cycle = $this->cycleResolverService->resolve($cycleType, $now, $timezone);
            $dueDate = $this->resolveDueDate($cycle, (int) $template->due_day, $cycleType);

            FixedCostOccurrence::query()->firstOrCreate(
                [
                    'fixed_cost_template_id' => $template->id,
                    'cycle_key' => $cycle->cycleKey,
                ],
                [
                    'user_id' => $userId,
                    'cycle_type' => $cycleType->value,
                    'cycle_key' => $cycle->cycleKey,
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
        }
    }

    private function resolveDueDate(ResolvedCycleData $cycleData, int $dueDay, CycleType $cycleType): CarbonImmutable
    {
        if ($cycleType === CycleType::MONTHLY) {
            // monthly
            $safeDay = min($dueDay, $cycleData->startDate->daysInMonth);

            return $cycleData->startDate->day($safeDay);
        }

        // weekly: 1 = Mon ... 7 = Sun
        $safeDay = max(1, min($dueDay, 7));

        return $cycleData->startDate->addDays($safeDay - 1);
    }
}
