<?php

use App\Commons\ValueObjects\Money;

test('it normalizes value to two decimal string', function () {
    expect(Money::normalize(100))->toBe('100.00')
        ->and(Money::normalize(1500.5))->toBe('1500.50');
});

test('it adds money values accurately', function () {
    $result = Money::add('0.1', '0.2');

    expect($result)->toBe('0.30');
});

test('it compares financial values correctly', function () {
    // Greater Than
    expect(Money::gt('1000.00', '500.00'))->toBeTrue();

    // Less Than or Equal
    expect(Money::lte('500.00', '1000.00'))->toBeTrue();
});
