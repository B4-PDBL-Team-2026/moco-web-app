<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\GetDailyLimitAction;
use App\Domains\Budgeting\Actions\UpdateDailyLimitAction;
use App\Domains\Budgeting\DTOs\UpdateDailyLimitData;
use App\Models\User;
use App\Models\UserBudgetSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

describe('GetDailyLimitAction', function () {
    it('returns budget setting when it exists', function () {
        $user = User::factory()->create();
        $setting = UserBudgetSetting::factory()->create(['user_id' => $user->id]);

        $action = new GetDailyLimitAction;
        $result = $action->execute($user);

        expect($result->id)->toBe($setting->id)
            ->and($result)->toBeInstanceOf(UserBudgetSetting::class);
    });

    it('throws BusinessRuleException when budget setting is not found', function () {
        $user = User::factory()->create();

        $action = new GetDailyLimitAction;

        expect(fn () => $action->execute($user))
            ->toThrow(BusinessRuleException::class, __('errors.budget.budget_setting_not_found'));
    });
});

describe('UpdateDailyLimitAction', function () {
    it('successfully updates user budget setting', function () {
        $user = User::factory()->create();
        UserBudgetSetting::factory()->create([
            'user_id' => $user->id,
            'flooring_limit' => 5000,
            'ceiling_limit' => 10000,
        ]);

        $action = new UpdateDailyLimitAction;
        $dto = new UpdateDailyLimitData(flooringLimit: '20000', ceilingLimit: '50000');

        $result = $action->execute($user, $dto);

        expect((string) $result->flooring_limit)->toBe('20000.00')
            ->and((string) $result->ceiling_limit)->toBe('50000.00');

        $this->assertDatabaseHas('user_budget_settings', [
            'user_id' => $user->id,
            'flooring_limit' => 20000,
            'ceiling_limit' => 50000,
        ]);
    });

    it('throws BusinessRuleException when ceiling limit is lower than flooring limit', function () {
        $user = User::factory()->create();
        UserBudgetSetting::factory()->create(['user_id' => $user->id]);

        $action = new UpdateDailyLimitAction;

        $dto = new UpdateDailyLimitData(flooringLimit: '50000', ceilingLimit: '10000');

        expect(fn () => $action->execute($user, $dto))
            ->toThrow(BusinessRuleException::class, __('errors.budget.ceiling_too_low'));
    });
});
