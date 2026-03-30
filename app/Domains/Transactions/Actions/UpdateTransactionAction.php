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
        private readonly UpdateTransactionAmountAction $updateTransactionAmountAction,
        private readonly UpdateTransactionDateAction $updateTransactionDateAction,
        private readonly UpdateTransactionMetadataAction $updateTransactionMetadataAction,
    ) {}

    /**
     * Rule 25: Changing transaction type (income <-> expense) is not allowed.
     * Rule 23: Amount change triggers recalculation.
     * Rule 26: Date change does not trigger recalculation; future dates rejected.
     * Rule 27: Metadata change does not trigger recalculation.
     *
     * @throws ValidationException|UnauthorizedException|Throwable
     */
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $dto): Transaction
    {
        // Authorization check
        if ($user->id !== $transaction->user_id) {
            throw new UnauthorizedException('You are not authorized to perform this action.');
        }

        // Rule 25: reject type change
        if ($dto->typeProvided) {
            throw ValidationException::withMessages([
                'type' => ['Changing transaction type is not allowed.'],
            ]);
        }

        // Rule 27: validate category type matches transaction type
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

        // Rule 23: amount change triggers recalculation
        if ($dto->amountProvided && $dto->amount !== null) {
            $transaction = $this->updateTransactionAmountAction->execute(
                user: $user,
                transaction: $transaction,
                newAmount: $dto->amount,
            );
        }

        // Rule 26: date change, no recalculation
        if ($dto->transactionDateProvided && $dto->transactionDate !== null) {
            $transaction = $this->updateTransactionDateAction->execute(
                user: $user,
                transaction: $transaction,
                newDate: $dto->transactionDate,
            );
        }

        // Rule 27: metadata change, no recalculation
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
            $transaction = $this->updateTransactionMetadataAction->execute(
                user: $user,
                transaction: $transaction,
                fields: $metadataFields,
            );
        }

        return $transaction;
    }
}
