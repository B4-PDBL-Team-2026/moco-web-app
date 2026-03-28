<?php

namespace App\Domains\Transactions\Actions;

use App\Domains\Transactions\DTOs\UpdateTransactionData;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Throwable;

class UpdateTransactionAction
{
    public function __construct(
        private readonly UpdateTransactionAmountAction $updateAmountAction,
        private readonly UpdateTransactionDateAction $updateDateAction,
        private readonly UpdateTransactionMetadataAction $updateMetadataAction,
    ) {}

    /**
     * Orchestrates partial updates by delegating to specialized actions.
     * Rule 25: Type change is strictly forbidden.
     * Rule 23: Amount change triggers recalculation.
     * Rule 26: Date change does NOT trigger recalculation.
     * Rule 27: Metadata change does NOT trigger recalculation.
     *
     * @throws ValidationException|UnauthorizedException|Throwable
     */
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $dto): Transaction
    {
        if ($user->id !== $transaction->user_id) {
            throw new UnauthorizedException('You are not authorized to perform this action.');
        }

        // Rule 25: reject type change upfront before any mutation
        if ($dto->typeProvided) {
            throw ValidationException::withMessages([
                'type' => ['Transaction type cannot be changed after creation.'],
            ]);
        }

        // Validate new categoryId is compatible with existing transaction type
        if ($dto->categoryIdProvided && $dto->categoryId !== null) {
            $category = SystemCategory::find($dto->categoryId)
                ?? CustomCategory::where('id', $dto->categoryId)
                    ->where('user_id', $user->id)
                    ->first();

            if (! $category) {
                throw ValidationException::withMessages([
                    'categoryId' => ['Category not found.'],
                ]);
            }

            if ($category->type->value !== $transaction->type->value) {
                throw ValidationException::withMessages([
                    'categoryId' => ['Category type does not match the transaction type.'],
                ]);
            }
        }

        // Delegate amount update (triggers recalculation — Rule 23)
        if ($dto->amountProvided && $dto->amount !== null) {
            $transaction = $this->updateAmountAction->execute($user, $transaction, $dto->amount);
        }

        // Delegate date update (no recalculation — Rule 26)
        if ($dto->transactionDateProvided && $dto->transactionDate !== null) {
            $transaction = $this->updateDateAction->execute($user, $transaction, $dto->transactionDate);
        }

        // Delegate metadata update (no recalculation — Rule 27)
        $metadataFields = [];
        if ($dto->nameProvided) {
            $metadataFields['name'] = $dto->name;
        }
        if ($dto->noteProvided) {
            $metadataFields['note'] = $dto->note;
        }
        if ($dto->categoryIdProvided) {
            $metadataFields['categoryId'] = $dto->categoryId;
        }

        if (! empty($metadataFields)) {
            $transaction = $this->updateMetadataAction->execute($user, $transaction, $metadataFields);
        }

        return $transaction;
    }
}