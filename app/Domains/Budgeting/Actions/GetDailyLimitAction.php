<?php

namespace App\Domains\Budgeting\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Models\UserBudgetSetting;

class GetDailyLimitAction
{
    /**
     * @throws BusinessRuleException
     */
    public function execute(User $user): UserBudgetSetting
    {
        return $user->budgetSetting ?? throw new BusinessRuleException(__('errors.budget.budget_setting_not_found'));
    }
}
