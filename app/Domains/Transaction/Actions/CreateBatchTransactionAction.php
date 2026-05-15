<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\DTOs\CreateBatchTransactionData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Models\TransactionBatch;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateBatchTransactionAction
{
    /**
     * Store a batch of transactions originating from a receipt scan.
     * Ensures atomic insertion of the batch parent and all child items.
     * * @return TransactionBatch The created batch with loaded items.
     * @throws Throwable
     */
    public function execute(int $userId, CreateBatchTransactionData $data): TransactionBatch
    {
        return DB::transaction(function () use ($userId, $data) {
            // Calculate total amount dynamically from items to avoid frontend math discrepancies
            $totalAmount = collect($data->items)->sum(fn ($item) => (float) $item->amount);

            // Create the parent Batch record
            $batch = TransactionBatch::query()->create([
                'user_id' => $userId,
                'name' => $data->name,
                'total_amount' => $totalAmount,
                'transaction_at' => $data->transactionAt,
            ]);

            // Prepare child transaction records
            $transactions = array_map(function ($item) use ($userId, $data, $batch) {
                return [
                    'transaction_batch_id' => $batch->id,
                    'user_id' => $userId,
                    'category_id' => $item->categoryId,
                    'type' => $data->type->value,
                    'source' => TransactionSource::RECEIPT_SCAN->value,
                    'name' => $item->name,
                    'amount' => $item->amount,
                    'transaction_at' => $data->transactionAt,
                    'note' => $item->note,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $data->items);

            // Bulk insert for performance
            $batch->transactions()->insert($transactions);

            return $batch->load('transactions.category');
        });
    }
}
