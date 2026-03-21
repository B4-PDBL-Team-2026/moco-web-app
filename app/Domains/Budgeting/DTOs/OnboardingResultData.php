<?php

namespace App\Domains\Budgeting\DTOs;

class OnboardingResultData
{
    public function __construct(
        public int $userId,
        public string $cycleType,
        public string $currentBalance,
        public string $reservedCost,
        public string $dailyAllowance,
        public string $cycleKey,
        public string $cycleStartDate,
        public string $cycleEndDate,
        public int $remainingDays,
        public int $fixedCostsCount,
        public bool $hasOnboarded,
    ) {}
}
