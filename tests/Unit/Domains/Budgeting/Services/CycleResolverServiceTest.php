<?php

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Services\CycleResolverService;
use Carbon\CarbonImmutable;

it('resolves monthly cycle correctly', function () {
    $resolver = new CycleResolverService;

    $result = $resolver->resolve(
        CycleType::MONTHLY,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
        'Asia/Jakarta'
    );

    expect($result->cycleKey)->toBe('2026-03')
        ->and($result->startDate->toDateString())->toBe('2026-03-01')
        ->and($result->endDate->toDateString())->toBe('2026-03-31')
        ->and($result->remainingDays)->toBe(12);
});

it('resolves weekly cycle correctly', function () {
    $resolver = new CycleResolverService;

    $result = $resolver->resolve(
        CycleType::WEEKLY,
        CarbonImmutable::parse('2026-03-20', 'Asia/Jakarta'),
        'Asia/Jakarta'
    );

    expect($result->startDate->toDateString())->toBe('2026-03-16')
        ->and($result->endDate->toDateString())->toBe('2026-03-22')
        ->and($result->remainingDays)->toBe(3);
});
