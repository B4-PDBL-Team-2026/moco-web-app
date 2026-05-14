<?php

use App\Commons\ValueObjects\Money;

it('normalizes values including those with comma separators', function () {
    expect(Money::normalize(100))->toBe('100.00')
        ->and(Money::normalize(1500.5))->toBe('1500.50')
        ->and(Money::normalize('1,500.5'))->toBe('1500.50')
        ->and(Money::normalize('1,000,000'))->toBe('1000000.00');
});

it('adds money values accurately', function () {
    expect(Money::add('0.1', '0.2'))->toBe('0.30')
        ->and(Money::add('10.50', '20.25'))->toBe('30.75');
});

it('subtracts money values accurately', function () {
    expect(Money::sub('10.00', '2.50'))->toBe('7.50')
        ->and(Money::sub('0.3', '0.2'))->toBe('0.10');
});

it('multiplies money values accurately', function () {
    expect(Money::mul('5.00', '2.00'))->toBe('10.00')
        ->and(Money::mul('10.50', '0.5'))->toBe('5.25');
});

it('divides money values accurately', function () {
    expect(Money::div('10.00', '2.00'))->toBe('5.00')
        ->and(Money::div('10.00', '3.00'))->toBe('3.33');
});

it('returns the minimum of two money values', function () {
    expect(Money::min('10.00', '20.00'))->toBe('10.00')
        ->and(Money::min('50.00', '5.00'))->toBe('5.00')
        ->and(Money::min('10.00', '10.00'))->toBe('10.00');
});

it('returns the maximum of two money values', function () {
    expect(Money::max('10.00', '20.00'))->toBe('20.00')
        ->and(Money::max('50.00', '5.00'))->toBe('50.00')
        ->and(Money::max('10.00', '10.00'))->toBe('10.00');
});

it('evaluates greater than (gt) correctly', function () {
    expect(Money::gt('1000.00', '500.00'))->toBeTrue()
        ->and(Money::gt('500.00', '1000.00'))->toBeFalse()
        ->and(Money::gt('500.00', '500.00'))->toBeFalse();
});

it('evaluates greater than or equal (gte) correctly', function () {
    expect(Money::gte('1000.00', '500.00'))->toBeTrue()
        ->and(Money::gte('500.00', '500.00'))->toBeTrue()
        ->and(Money::gte('499.99', '500.00'))->toBeFalse();
});

it('evaluates less than (lt) correctly', function () {
    expect(Money::lt('500.00', '1000.00'))->toBeTrue()
        ->and(Money::lt('1000.00', '500.00'))->toBeFalse()
        ->and(Money::lt('500.00', '500.00'))->toBeFalse();
});

it('evaluates less than or equal (lte) correctly', function () {
    expect(Money::lte('500.00', '1000.00'))->toBeTrue()
        ->and(Money::lte('500.00', '500.00'))->toBeTrue()
        ->and(Money::lte('500.01', '500.00'))->toBeFalse();
});

it('evaluates equality (eq) correctly', function () {
    expect(Money::eq('500.00', '500.00'))->toBeTrue()
        ->and(Money::eq('500.0000', '500.00'))->toBeTrue()
        ->and(Money::eq('500.00', '500.01'))->toBeFalse();
});
