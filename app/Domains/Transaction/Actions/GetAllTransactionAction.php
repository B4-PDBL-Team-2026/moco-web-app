<?php

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

class GetAllTransactionAction
{
    public function execute(int $userId, FilterTransactionData $data): LengthAwarePaginator
    {
        return Transaction::with('category')
            ->where('user_id', $userId)
            ->when($data->month, fn ($query) => $query->whereMonth('transaction_at', $data->month))
            ->when($data->year, fn ($query) => $query->whereYear('transaction_at', $data->year))
            ->when($data->search, fn ($query) => $query->where('name', 'like', "%{$data->search}%"))
            ->when($data->categoryId, fn ($query) => $query
                ->where('category_id', $data->categoryId)
            )
            ->latest('transaction_at')
            ->latest()
            ->paginate($data->perPage);

    }
}
