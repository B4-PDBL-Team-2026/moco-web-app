<?php

namespace App\Domains\Transaction\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Transaction\Models\TransactionBatch;

final readonly class GetBatchTransactionDetailAction
{
    public function execute(int $userId, int $batchId): TransactionBatch
    {
        $batch = TransactionBatch::with(['transactions.category'])
            ->where('user_id', $userId)
            ->find($batchId);

        if (! $batch) {
            throw new BusinessRuleException(__('errors.transaction.not_found'));
        }

        return $batch;
    }
}
