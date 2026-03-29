<?php

namespace App\Domains\Transactions\Services;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use Carbon\CarbonImmutable;

class TransactionValidationService
{
    public function ensureSufficientBalance(string $balance, string $amount): void
    {
        if (MoneyService::lt($balance, $amount)) {
            throw new BusinessRuleException('Insufficient balance.');
        }
    }

    public function ensureFutureDateNotAllowed(CarbonImmutable $date): void
    {
        if ($date->isFuture()) {
            throw new BusinessRuleException('Future transaction date is not allowed.');
        }
    }

    public function ensureTypeNotChanged(string $oldType, string $newType): void
    {
        if ($oldType !== $newType) {
            throw new BusinessRuleException('Transaction type cannot be changed.');
        }
    }
}
