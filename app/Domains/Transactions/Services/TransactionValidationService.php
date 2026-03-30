<?php

namespace App\Domains\Transactions\Services;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\Services\MoneyService;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use App\Models\User;
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

    /**
     * @throws BusinessRuleException
     */
    public function resolveAndEnsureCategoryAllowed(string $categoryType, int $categoryId, string $transactionType, User $user): string
    {
        // resolve category
        if ($categoryType === 'system' || $categoryType === SystemCategory::class) {
            $category = SystemCategory::findOrFail($categoryId);
            $categoryType = SystemCategory::class;
        } else {
            $category = CustomCategory::where('id', $categoryId)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $categoryType = CustomCategory::class;
        }

        // validate category type matches transaction type
        $categoryTypeValue = $category->type instanceof \BackedEnum
            ? $category->type->value
            : $category->type;

        if ($categoryTypeValue !== $transactionType) {
            throw new BusinessRuleException('Category type does not match transaction type.');
        }

        return $categoryType;
    }
}
