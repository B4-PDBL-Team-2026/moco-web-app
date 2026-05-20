<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Domains\Transaction\Enums\TransactionFeedType;
use App\Domains\Transaction\Enums\TransactionSource;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GetAllTransactionAction
{
    public function execute(int $userId, FilterTransactionData $data): LengthAwarePaginator
    {
        // single transaction query
        $singleTransaction = DB::table('transactions as t')
            ->leftJoin('categories as c', 't.category_id', '=', 'c.id')
            ->select(
                't.id',
                't.name',
                't.amount',
                't.transaction_at',
                't.type',
                't.source',
                't.category_id',
                't.note',
                'c.name as category_name',
                'c.icon as category_icon',
                DB::raw("'".TransactionFeedType::SINGLE->value."' as feed_type")
            )
            ->where('t.user_id', '=', $userId)
            ->whereNull('t.transaction_batch_id')
            ->whereNull('t.deleted_at');

        $this->applyCommonFilters($singleTransaction, $data, 't');

        // apply category filters on single transaction records
        $singleTransaction->when($data->categoryId, function (Builder $query) use ($data) {
            $query->where('t.category_id', '=', $data->categoryId);
        });

        // apply type filters on single transaction records
        $singleTransaction->when($data->transactionType, function (Builder $query) use ($data) {
            $query->where('t.type', '=', $data->transactionType->value);
        });

        // exclude single record transaction when user request batch only query
        $singleTransaction->when($data->transactionFeedType === TransactionFeedType::BATCH, function (Builder $query) {
            $query->whereRaw('1 = 0');
        });

        // batch transaction query
        $batchTransaction = DB::table('transaction_batches as tb')
            ->select(
                'tb.id',
                'tb.name',
                'tb.total_amount as amount',
                'tb.transaction_at',
                'tb.note',
                DB::raw('NULL as type'),
                DB::raw('NULL as category_id'),
                DB::raw('NULL as category_name'),
                DB::raw('NULL as category_icon'),
                DB::raw("'".TransactionSource::BATCH->value."' as source"),
                DB::raw("'".TransactionFeedType::BATCH->value."' as feed_type"),
            )
            ->where('tb.user_id', $userId)
            ->whereNull('tb.deleted_at');

        $this->applyCommonFilters($batchTransaction, $data, 'tb');

        // apply category filters on batch transaction records
        $batchTransaction->when($data->categoryId, function (Builder $query) use ($data) {
            $query->whereIn('tb.id', function (Builder $subQuery) use ($data) {
                $subQuery->select('item.transaction_batch_id')
                    ->from('transactions as item')
                    ->where('item.category_id', '=', $data->categoryId)
                    ->whereNull('item.deleted_at')
                    ->whereNotNull('item.transaction_batch_id');
            });
        });

        // apply transaction type filters on batch transaction records
        $batchTransaction->when($data->transactionType, function (Builder $query) use ($data) {
            $query->whereIn('tb.id', function (Builder $subQuery) use ($data) {
                $subQuery->select('item.transaction_batch_id')
                    ->from('transactions as item')
                    ->where('item.type', '=', $data->transactionType->value)
                    ->whereNull('item.deleted_at')
                    ->whereNotNull('item.transaction_batch_id');
            });
        });

        // exclude batch record transaction when user request batch only query
        $batchTransaction->when(
            $data->transactionFeedType === TransactionFeedType::SINGLE, function (Builder $query) {
                $query->whereRaw('1 = 0');
            });

        return $singleTransaction
            ->union($batchTransaction)
            ->orderBy('transaction_at', 'desc')
            ->paginate($data->perPage);
    }

    /**
     * Reusable filter logic for both queries
     */
    private function applyCommonFilters(Builder $query, FilterTransactionData $data, string $alias): void
    {
        $query->when($data->month, fn (Builder $subQuery) => $subQuery->whereMonth("{$alias}.transaction_at", $data->month))
            ->when($data->year, fn (Builder $subQuery) => $subQuery->whereYear("{$alias}.transaction_at", $data->year))
            ->when($data->search, function (Builder $subQuery) use ($data, $alias) {
                $operator = $subQuery->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $subQuery->where("{$alias}.name", $operator, "{$data->search}%");
            });
    }
}
