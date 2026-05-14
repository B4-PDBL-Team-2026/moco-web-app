<?php

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCost\Services\FixedCostValidator;

it('allows monthly fixed cost on a monthly budget cycle', function () {
    $validator = new FixedCostValidator;

    expect(fn () => $validator->validateCycleCompatibility(CycleType::MONTHLY, CycleType::MONTHLY))
        ->not->toThrow(InvalidArgumentException::class);
});

it('allows weekly fixed cost on a monthly budget cycle', function () {
    $validator = new FixedCostValidator;

    expect(fn () => $validator->validateCycleCompatibility(CycleType::MONTHLY, CycleType::WEEKLY))
        ->not->toThrow(InvalidArgumentException::class);
});

it('allows weekly fixed cost on a weekly budget cycle', function () {
    $validator = new FixedCostValidator;

    expect(fn () => $validator->validateCycleCompatibility(CycleType::WEEKLY, CycleType::WEEKLY))
        ->not->toThrow(InvalidArgumentException::class);
});

it('rejects monthly fixed cost on a weekly budget cycle', function () {
    $validator = new FixedCostValidator;

    expect(fn () => $validator->validateCycleCompatibility(CycleType::WEEKLY, CycleType::MONTHLY))
        ->toThrow(BusinessRuleException::class);
});
