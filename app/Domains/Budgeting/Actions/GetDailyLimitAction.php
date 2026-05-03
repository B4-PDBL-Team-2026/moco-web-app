<?php

namespace App\Domains\Budgeting\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\User\Models\User;

class GetDailyLimitAction
{
    /**
     * @throws BusinessRuleException
     */
    public function execute(User $user): UserBudgetSetting
    {
        return $user->budgetSetting ?? throw new BusinessRuleException(__('errors.budgeting.budget_setting_not_found'));
    }
}
