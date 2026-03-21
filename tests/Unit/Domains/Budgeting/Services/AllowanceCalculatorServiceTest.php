<?php

use App\Domains\Budgeting\DTOs\DailyAllowanceData;
use App\Domains\Budgeting\Services\AllowanceCalculatorService;

it('calculates daily allowance normally and stores raw as actual amount', function () {
    $service = new AllowanceCalculatorService;

    $result = $service->calculate(
        balance: '1000.00',
        reservedCost: '400.00',
        ceilingLimit: '999999.00',
        flooringLimit: '0.00',
        remainingDays: 3,
    );

    expect($result)->toBeInstanceOf(DailyAllowanceData::class)
        ->and($result->amount)->toBe('200.00')
        ->and($result->actualAmount)->toBe('200.00');
});

it('returns flooring and zero actual amount when reserved cost equals balance', function () {
    $service = new AllowanceCalculatorService;

    $result = $service->calculate(
        balance: '500.00',
        reservedCost: '500.00',
        ceilingLimit: '999999.00',
        flooringLimit: '50.00',
        remainingDays: 5,
    );

    expect($result->amount)->toBe('50.00')
        ->and($result->actualAmount)->toBe('0');
});

it('returns flooring and zero actual amount when reserved cost exceeds balance', function () {
    $service = new AllowanceCalculatorService;

    $result = $service->calculate(
        balance: '500.00',
        reservedCost: '700.00',
        ceilingLimit: '999999.00',
        flooringLimit: '50.00',
        remainingDays: 5,
    );

    expect($result->amount)->toBe('50.00')
        ->and($result->actualAmount)->toBe('0');
});

it('applies flooring when raw daily allowance is below flooring', function () {
    $service = new AllowanceCalculatorService;

    $result = $service->calculate(
        balance: '100.00',
        reservedCost: '0.00',
        ceilingLimit: '999999.00',
        flooringLimit: '50.00',
        remainingDays: 10,
    );

    expect($result->amount)->toBe('50.00')
        ->and($result->actualAmount)->toBe('10.00');
});

it('applies ceiling when raw daily allowance exceeds ceiling', function () {
    $service = new AllowanceCalculatorService;

    $result = $service->calculate(
        balance: '1000.00',
        reservedCost: '0.00',
        ceilingLimit: '300.00',
        flooringLimit: '10.00',
        remainingDays: 2,
    );

    expect($result->amount)->toBe('300.00')
        ->and($result->actualAmount)->toBe('500.00');
});

it('returns raw when raw is between flooring and ceiling', function () {
    $service = new AllowanceCalculatorService;

    $result = $service->calculate(
        balance: '600.00',
        reservedCost: '0.00',
        ceilingLimit: '300.00',
        flooringLimit: '50.00',
        remainingDays: 3,
    );

    expect($result->amount)->toBe('200.00')
        ->and($result->actualAmount)->toBe('200.00');
});

it('throws when remaining days is not greater than zero', function () {
    $service = new AllowanceCalculatorService;

    $service->calculate(
        balance: '100.00',
        reservedCost: '0.00',
        ceilingLimit: '1000.00',
        flooringLimit: '0.00',
        remainingDays: 0,
    );
})->throws(InvalidArgumentException::class, 'Remaining days must be greater than 0.');
