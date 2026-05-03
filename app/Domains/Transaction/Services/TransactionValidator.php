<?php

namespace App\Domains\Transaction\Services;

use App\Commons\Exceptions\BusinessRuleException;
use App\Commons\ValueObjects\Money;
use App\Domains\Category\Models\Category;
use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\DTOs\UpdateTransactionData;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\User\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Validation\UnauthorizedException;

class TransactionValidator
{
    /**
     * @throws BusinessRuleException
     */
    public function validateCreate(User $user, CreateTransactionData $data): void
    {
        $this->ensureValidAmount($data->amount);
        $this->ensureFutureDateNotAllowed($user, $data->transactionAt);
        $this->validateTransactionCategory($data->categoryId, $data->type->value, $user);
    }

    /**
     * @throws BusinessRuleException
     */
    public function validateUpdate(User $user, Transaction $transaction, UpdateTransactionData $data): void
    {
        $this->ensureAuthorized($user, $transaction);

        if ($data->amountProvided && $data->amount !== null) {
            $this->ensureValidAmount($data->amount);
        }

        if ($data->transactionAtProvided && $data->transactionAt !== null) {
            $this->ensureFutureDateNotAllowed($user, $data->transactionAt);
        }

        if ($data->categoryIdProvided && $data->categoryId !== null) {
            $transactionType = $transaction->type instanceof BackedEnum
                ? $transaction->type->value
                : $transaction->type;

            $this->validateTransactionCategory($data->categoryId, $transactionType, $user);
        }
    }

    /**
     * @throws BusinessRuleException
     */
    public function ensureSufficientBalance(string $balance, string $amount): void
    {
        if (Money::lt($balance, Money::normalize($amount))) {
            throw new BusinessRuleException(__('errors.budgeting.balance_insufficient'));
        }
    }

    private function ensureAuthorized(User $user, Transaction $transaction): void
    {
        if ($user->id !== $transaction->user_id) {
            throw new UnauthorizedException(__('errors.authorization.not_authorized'));
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureValidAmount(string $amount): void
    {
        if (Money::lte(Money::normalize($amount), '0')) {
            throw new BusinessRuleException('Transaction amount must be greater than 0.');
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function ensureFutureDateNotAllowed(User $user, CarbonImmutable $date): void
    {
        $user->loadMissing('budgetSetting');
        $userTimezone = $user->budgetSetting->timezone;

        $userToday = now()->timezone($userTimezone)->startOfDay();
        $transactionDate = $date->timezone($userTimezone)->startOfDay();

        if ($transactionDate->greaterThan($userToday)) {
            throw new BusinessRuleException(__('errors.transaction.future_date'));
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function validateTransactionCategory(int $categoryId, string $transactionType, User $user): void
    {
        $category = Category::query()->findOrFail($categoryId);

        if (! $category->is_system && $category->user_id !== $user->id) {
            throw new BusinessRuleException(__('errors.authorization.not_authorized'));
        }

        $categoryTypeValue = $category->type instanceof BackedEnum
            ? $category->type->value
            : $category->type;

        if ($categoryTypeValue !== $transactionType) {
            throw new BusinessRuleException(__('errors.category.mismatch'));
        }
    }
}
