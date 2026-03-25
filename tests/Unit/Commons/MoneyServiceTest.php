<?php

use App\Commons\MoneyService;

test('it normalizes value to two decimal string', function () {
    expect(MoneyService::normalize(100))->toBe('100.00')
        ->and(MoneyService::normalize(1500.5))->toBe('1500.50');
});

test('it adds money values accurately', function () {
    $result = MoneyService::add('0.1', '0.2');

    expect($result)->toBe('0.30');
});

test('it compares financial values correctly', function () {
    // Greater Than
    expect(MoneyService::gt('1000.00', '500.00'))->toBeTrue();

    // Less Than or Equal
    expect(MoneyService::lte('500.00', '1000.00'))->toBeTrue();
});
