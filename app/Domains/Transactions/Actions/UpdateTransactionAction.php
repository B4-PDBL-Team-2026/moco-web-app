<?php

namespace App\Domains\Transactions\Actions;

use App\Domains\Transactions\DTOs\UpdateTransactionData;
use App\Models\Transaction;
use App\Models\User;
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
     * Rule 25: Changing transaction type (income ↔ expense) is not allowed.
     * Rule 23: Amount change triggers recalculation.
     * Rule 26: Date change does not trigger recalculation; future dates rejected.
     * Rule 27: Metadata change does not trigger recalculation.
     *
     * @throws ValidationException|Throwable
     */
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $dto): Transaction
    {
        // Rule 25: reject type change attempts
        if ($dto->typeProvided) {
            throw ValidationException::withMessages([
                'type' => ['Changing transaction type is not allowed.'],
            ]);
        }

        // Rule 23 & 24: handle amount update separately (triggers recalculation)
        if ($dto->amountProvided && $dto->amount !== null) {
            $transaction = $this->updateTransactionAmountAction->execute(
                user: $user,
                transaction: $transaction,
                newAmount: $dto->amount,
            );
        }

        // Rule 26: handle date update separately (no recalculation)
        if ($dto->transactionDateProvided && $dto->transactionDate !== null) {
            $transaction = $this->updateTransactionDateAction->execute(
                user: $user,
                transaction: $transaction,
                newDate: $dto->transactionDate,
            );
        }

        // Rule 27: handle metadata update separately (no recalculation)
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