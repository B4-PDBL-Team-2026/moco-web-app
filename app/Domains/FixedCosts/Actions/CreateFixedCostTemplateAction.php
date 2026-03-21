<?php

namespace App\Domains\FixedCosts\Actions;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCosts\DTOs\FixedCostTemplateData;
use App\Models\CustomCategory;
use App\Models\FixedCostTemplate;
use App\Models\SystemCategory;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

final class CreateFixedCostTemplateAction
{
    /**
     * @param  list<FixedCostTemplateData>  $fixedCosts
     *
     * @throws Throwable
     */
    public function execute(int $userId, array $fixedCosts): void
    {
        DB::transaction(function () use ($userId, $fixedCosts): void {
            foreach ($fixedCosts as $item) {
                $this->validate($userId, $item);

                FixedCostTemplate::query()->create([
                    'user_id' => $userId,
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'cycle_type' => $item->cycleType->value,
                    'due_day' => $item->dueDay,
                    'is_active' => $item->isActive,
                    'category_type' => $item->categoryType,
                    'category_id' => $item->categoryId,
                ]);
            }
        });
    }

    private function validate(int $userId, FixedCostTemplateData $item): void
    {
        if (trim($item->name) === '') {
            throw new InvalidArgumentException('Fixed cost name is required.');
        }

        if ((float) $item->amount <= 0) {
            throw new InvalidArgumentException('Fixed cost amount must be greater than zero.');
        }

        if ($item->cycleType === CycleType::WEEKLY && ($item->dueDay < 1 || $item->dueDay > 7)) {
            throw new InvalidArgumentException('Weekly due day must be between 1 and 7.');
        }

        if ($item->cycleType === CycleType::MONTHLY && ($item->dueDay < 1 || $item->dueDay > 31)) {
            throw new InvalidArgumentException('Monthly due day must be between 1 and 31.');
        }

        $allowedCategory = [
            SystemCategory::class,
            CustomCategory::class,
        ];

        if (! in_array($item->categoryType, $allowedCategory, true)) {
            throw new InvalidArgumentException('Invalid category type.');
        }

        if ($item->categoryType === SystemCategory::class) {
            $isExists = SystemCategory::query()->whereKey($item->categoryId)->exists();

            if (! $isExists) {
                throw new InvalidArgumentException('Invalid category.');
            }

            return;
        }

        $customCategoryExists = CustomCategory::query()
            ->whereKey($item->categoryId)
            ->where('user_id', '=', $userId)->exists();

        if (! $customCategoryExists) {
            throw new InvalidArgumentException('Invalid custom category.');
        }
    }
}
