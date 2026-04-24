<?php

namespace App\Domains\Budgeting\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\DTOs\UpdateDailyLimitData;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateDailyLimitAction
{
    public function __construct(
        private readonly RecalculateBudgetSnapshotAction $recalculateBudgetSnapshotAction,
    ) {}

    /**
     * @throws BusinessRuleException
     * @throws Throwable
     */
    public function execute(User $user, UpdateDailyLimitData $data): UserBudgetSetting
    {
        if (MoneyService::lt($data->ceilingLimit, $data->flooringLimit)) {
            throw new BusinessRuleException(__('errors.budget.ceiling_too_low'));
        }

        $setting = $user->budgetSetting;

        return DB::transaction(function () use ($setting, $data) {
            $setting->update([
                'flooring_limit' => $data->flooringLimit,
                'ceiling_limit' => $data->ceilingLimit,
            ]);

            $this->recalculateBudgetSnapshotAction->execute(
                userId: $setting->user_id,
                forceUpdateLimit: true,
            );

            return $setting->refresh();
        });
    }
}
