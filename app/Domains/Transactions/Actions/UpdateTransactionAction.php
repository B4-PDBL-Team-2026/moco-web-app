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
     * @throws ValidationException|UnauthorizedException|Throwable
     */
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $dto): Transaction
    {
        // Authorization check 
        if ($user->id !== $transaction->user_id) {
            throw new UnauthorizedException('You are not authorized to perform this action.');
        }

        if ($dto->typeProvided) {
            throw ValidationException::withMessages([
                'type' => ['Transaction type cannot be changed after creation.'],
            ]);
        }

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

        // Amount update
        if ($dto->amountProvided && $dto->amount !== null) {
            $transaction = $this->updateTransactionAmountAction->execute(
                user: $user,
                transaction: $transaction,
                newAmount: $dto->amount,
            );
        }

        // Date update
        if ($dto->transactionDateProvided && $dto->transactionDate !== null) {
            $transaction = $this->updateTransactionDateAction->execute(
                user: $user,
                transaction: $transaction,
                newDate: $dto->transactionDate,
            );
        }

        // Metadata update
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