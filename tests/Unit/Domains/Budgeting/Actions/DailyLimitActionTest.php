<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Actions\GetDailyLimitAction;
use App\Domains\Budgeting\Actions\RecalculateBudgetSnapshotAction;
use App\Domains\Budgeting\Actions\UpdateDailyLimitAction;
use App\Domains\Budgeting\DTOs\UpdateDailyLimitData;
use App\Domains\Budgeting\Models\UserBudgetSetting;
use App\Domains\User\Models\User;
use Mockery\MockInterface;

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

        $exception = catchException(
            fn () => $action->execute($user),
            BusinessRuleException::class
        );

        /** @var BusinessRuleException $exception */
        expect($exception)
            ->getTranslationKey()->toBe(__('errors.budget.budget_setting_not_found'))
            ->getTranslationParams()->toBe([])
            ->getHttpStatus()->toBe(422);
    });
});

describe('UpdateDailyLimitAction', function () {
    it('successfully updates setting and forcefully triggers recalculation', function () {
        $user = User::factory()->create();
        UserBudgetSetting::factory()->create([
            'user_id' => $user->id,
            'flooring_limit' => 5000,
            'ceiling_limit' => 10000,
        ]);

        $this->mock(RecalculateBudgetSnapshotAction::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($user->id, null, true);
        });

        $action = app(UpdateDailyLimitAction::class);
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

        $action = app(UpdateDailyLimitAction::class);

        $dto = new UpdateDailyLimitData(flooringLimit: '50000', ceilingLimit: '10000');

        $exception = catchException(
            fn () => $action->execute($user, $dto),
            BusinessRuleException::class
        );

        /** @var BusinessRuleException $exception */
        expect($exception)
            ->getTranslationKey()->toBe('errors.budget.ceiling_too_low')
            ->getTranslationParams()->toBe([])
            ->getHttpStatus()->toBe(422);
    });
});
