<?php

namespace App\Domains\Transactions\Actions;

use App\Domains\Transactions\DTOs\FilterTransactionData;
use App\Models\Transaction;
use Illuminate\Pagination\LengthAwarePaginator;

class GetAllTransactionAction
{
    public function execute(int $userId, FilterTransactionData $data): LengthAwarePaginator
    {
        return Transaction::with('category')
            ->where('user_id', $userId)
            ->when($data->month, fn ($query) => $query->whereMonth('transaction_date', $data->month))
            ->when($data->year, fn ($query) => $query->whereYear('transaction_date', $data->year))
            ->when($data->search, fn ($query) => $query->where('name', 'like', "%{$data->search}%"))
<<<<<<< HEAD
            ->when($data->categoryId, fn ($query) => $query
                ->where('category_id', $data->categoryId)
                ->when($data->categoryType, fn ($query) => $query->where('category_type', $data->categoryType))
            )
=======
            ->when($data->categoryId, fn ($query) => $query->where('category_id', $data->categoryId))
            ->latest('transaction_date')
>>>>>>> d8096e3 (fix(transactions): ensure transactions are ordered by transaction_date)
            ->latest()
            ->paginate($data->perPage);

    }
}
