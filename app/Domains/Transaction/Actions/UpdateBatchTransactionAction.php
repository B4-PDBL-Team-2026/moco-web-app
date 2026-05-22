<?php

namespace App\Domains\Transaction\Actions;

use App\Commons\Exceptions\BusinessRuleException;
use App\Domains\Budgeting\Models\UserBudgetSnapshot;
use App\Domains\Transaction\DTOs\UpdateBatchTransactionData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use App\Domains\Transaction\Models\TransactionBatch;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpdateBatchTransactionAction
{
    /**
     * @throws Throwable
     * @throws BusinessRuleException
     */
    public function execute(int $userId, int $transactionBatchId, UpdateBatchTransactionData $data): TransactionBatch
    {
        $batch = TransactionBatch::query()
            ->where('user_id', '=', $userId)
            ->findOrFail($transactionBatchId);

        return DB::transaction(function () use ($userId, $batch, $data) {
            // Lock the snapshot to prevent race conditions during balance check
            $snapshot = UserBudgetSnapshot::query()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            // Calculate Old Net Total
            $oldNetTotal = $batch->transactions->reduce(function (float $carry, $tx) {
                $amount = (float) $tx->amount;

                return $tx->type === TransactionType::INCOME->value
                    ? $carry + $amount
                    : $carry - $amount;
            }, 0.0);

            // Calculate New Net Total dynamically from new items
            $newNetTotal = collect($data->items)->reduce(function (float $carry, $item) {
                $amount = (float) $item->amount;

                return $item->type === TransactionType::INCOME
                    ? $carry + $amount
                    : $carry - $amount;
            }, 0.0);

            // Check Balance Constraint (Current Balance + Difference must be >= 0)
            $difference = $newNetTotal - $oldNetTotal;

            if ($snapshot->current_balance + $difference < 0) {
                throw new BusinessRuleException('balance_insufficient');
            }

            // Update the Parent Batch
            $batch->update([
                'name' => $data->name,
                'total_amount' => abs($newNetTotal), // Keep absolute total for DB
                'transaction_at' => $data->transactionAt,
                'note' => $data->note,
            ]);

            // Replace Child Transactions (Full Sync Strategy)
            $batch->transactions()->delete();

            $newTransactions = array_map(function ($item) use ($userId, $batch, $data) {
                return [
                    'transaction_batch_id' => $batch->id,
                    'user_id' => $userId,
                    'category_id' => $item->categoryId,
                    'type' => $item->type->value,
                    'source' => $data->source?->value ?? TransactionSource::MANUAL->value,
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'transaction_at' => $data->transactionAt,
                    'note' => $item->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $data->items);

            $batch->transactions()->createMany($newTransactions);
            $batch->unsetRelation('transactions');

            return $batch->load('transactions.category');
        });
    }
}
