<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class GetTransactionsAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return Transaction::with('category')
            ->where('user_id', Auth::id())
            ->when($filters['month'], fn($q) => $q->whereMonth('created_at', $filters['month']))
            ->when($filters['year'], fn($q) => $q->whereYear('created_at', $filters['year']))
            ->when($filters['search'], fn($q) => $q->where('name', 'like', "%{$filters['search']}%"))
            ->when($filters['category'], fn($q) => $q->where('category_id', $filters['category']))
            ->latest()
            ->paginate(10);
    }
}