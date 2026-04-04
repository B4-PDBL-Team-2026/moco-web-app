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
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $data): Transaction
    {
        // Authorization check
        if ($user->id !== $transaction->user_id) {
            throw new UnauthorizedException('You are not authorized to perform this action.');
        }

        // Rule 27: validate category type matches transaction type
        $resolvedCategoryType = null;

        // Rule 27: validate category type matches transaction type
        if ($data->categoryIdProvided && $data->categoryId !== null) {
            if ($data->categoryType === 'system') {
                $category = SystemCategory::find($data->categoryId);
            } else {
                $category = CustomCategory::where('id', $data->categoryId)
                    ->where('user_id', $user->id)
                    ->first();
            }

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

            $resolvedCategoryType = $category->getMorphClass();
        }

        // Rule 23: amount change triggers recalculation
        if ($data->amountProvided && $data->amount !== null) {
            $transaction = $this->updateTransactionAmountAction->execute(
                user: $user,
                transaction: $transaction,
                newAmount: $data->amount,
            );
        }

        // Rule 26: date change, no recalculation
        if ($data->transactionAtProvided && $data->transactionAt !== null) {
            $transaction = $this->updateTransactionDateAction->execute(
                user: $user,
                transaction: $transaction,
                newDate: $data->transactionAt,
            );
        }

        // Rule 27: metadata change, no recalculation
        $metadataFields = [];

        if ($data->nameProvided) {
            $metadataFields['name'] = $data->name;
        }

        if ($data->noteProvided) {
            $metadataFields['note'] = $data->note;
        }

        if ($data->categoryIdProvided) {
            $metadataFields['categoryId'] = $data->categoryId;

            if ($resolvedCategoryType) {
                $metadataFields['categoryType'] = $resolvedCategoryType;
            }
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
