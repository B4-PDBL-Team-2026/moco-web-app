<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GetAllTransactionAction
{
    public function execute(User $user, array $filters): LengthAwarePaginator
    {
        return Transaction::with('category')
            ->where('user_id', $user->id)
            ->when($filters['month'], fn ($query) => $query->whereMonth('transaction_date', $filters['month']))
            ->when($filters['year'], fn ($query) => $query->whereYear('created_at', $filters['year']))
            ->when($filters['search'], fn ($query) => $query->where('name', 'like', "%{$filters['search']}%"))
            ->when($filters['category'], fn ($query) => $query->where('category_id', $filters['category']))
            ->latest()
            ->paginate(10);
    }
}
