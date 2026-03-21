<?php

namespace App\Domains\Budgeting\DTOs;

use Carbon\CarbonImmutable;

final readonly class ResolvedCycleData
{
    public function __construct(
        public string $cycleKey,
        public CarbonImmutable $startDate,
        public CarbonImmutable $endDate,
        public int $remainingDays,
    ) {}
}
