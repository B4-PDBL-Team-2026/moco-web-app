<?php

namespace App\Domains\Budgeting\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Domains\Budgeting\DTOs\UpdateDailyLimitData;
use App\Models\User;
use App\Models\UserBudgetSetting;

class UpdateDailyLimitAction
{
    public function __construct() {}

    /**
     * @throws BusinessRuleException
     */
    public function execute(User $user, UpdateDailyLimitData $data): UserBudgetSetting
    {
        if (MoneyService::lt($data->ceilingLimit, $data->flooringLimit)) {
            throw new BusinessRuleException(__('errors.budget.ceiling_too_low'));
        }

        $setting = $user->budgetSetting;

        $setting->update([
            'flooring_limit' => $data->flooringLimit,
            'ceiling_limit' => $data->ceilingLimit,
        ]);

        return $setting->refresh();
    }
}
