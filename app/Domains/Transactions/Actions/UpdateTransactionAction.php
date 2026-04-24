<?php

namespace App\Domains\Transactions\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Transactions\DTOs\UpdateTransactionData;
use App\Domains\Transactions\Services\TransactionValidator;
use App\Models\Transaction;
use App\Models\User;
use Throwable;

class UpdateTransactionAction
{
    public function __construct(
        private readonly UpdateTransactionAmountAction $updateTransactionAmountAction,
        private readonly UpdateTransactionDateAction $updateTransactionDateAction,
        private readonly UpdateTransactionMetadataAction $updateTransactionMetadataAction,
        private readonly TransactionValidator $transactionValidationService,
    ) {}

    /**
     * @throws Throwable
     * @throws BusinessRuleException
     */
    public function execute(User $user, Transaction $transaction, UpdateTransactionData $data): Transaction
    {
        $this->transactionValidationService->validateUpdate($user, $transaction, $data);

        if ($data->amountProvided && $data->amount !== null) {
            $transaction = $this->updateTransactionAmountAction->execute(
                user: $user,
                transaction: $transaction,
                newAmount: $data->amount,
            );
        }

        if ($data->transactionAtProvided && $data->transactionAt !== null) {
            $transaction = $this->updateTransactionDateAction->execute(
                user: $user,
                transaction: $transaction,
                newDate: $data->transactionAt,
            );
        }

        $metadataFields = [];
        if ($data->nameProvided) {
            $metadataFields['name'] = $data->name;
        }
        if ($data->noteProvided) {
            $metadataFields['note'] = $data->note;
        }
        if ($data->categoryIdProvided) {
            $metadataFields['categoryId'] = $data->categoryId;
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
