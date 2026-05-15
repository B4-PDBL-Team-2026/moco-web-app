<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\DTOs\FilterTransactionData;
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
                DB::raw("'transaction' as feed_type")
            )
            ->where('t.user_id', '=', $userId)
            ->whereNull('t.transaction_batch_id')
            ->whereNull('t.deleted_at');

        $this->applyCommonFilters($singleTransaction, $data, 't');

        // apply category filters on single transaction
        $singleTransaction
            ->when($data->categoryId, fn (Builder $query) => $query->where('t.category_id', '=', $data->categoryId));

        // batch transaction query
        $batchTransaction = DB::table('transaction_batches as tb')
            ->select(
                'tb.id',
                'tb.name',
                'tb.total_amount as amount',
                'tb.transaction_at',
                'tb.type',
                DB::raw('NULL as category_id'),
                DB::raw('NULL as category_name'),
                DB::raw('NULL as category_icon'),
                DB::raw('NULL as note'),
                DB::raw("'receipt_scan' as source"),
                DB::raw("'batch' as feed_type"),
            )
            ->where('tb.user_id', $userId)
            ->whereNull('tb.deleted_at');

        $this->applyCommonFilters($batchTransaction, $data, 'tb');

        $batchTransaction->when($data->categoryId, function (Builder $query) use ($data) {
            $query->whereIn('tb.id', function (Builder $subQuery) use ($data) {
                $subQuery->select('item.transaction_batch_id')
                    ->from('transactions as item')
                    ->where('item.category_id', '=', $data->categoryId)
                    ->whereNull('item.deleted_at')
                    ->whereNotNull('item.transaction_batch_id');
            });
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
