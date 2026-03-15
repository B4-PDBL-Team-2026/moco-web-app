<?php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class GetTotalExpenseAction
{
    public function execute(array $filters): float
    {
        return Transaction::where('user_id', Auth::id())
            ->where('type', 'expense')
            ->when($filters['month'], fn($q) => $q->whereMonth('created_at', $filters['month']))
            ->when($filters['year'], fn($q) => $q->whereYear('created_at', $filters['year']))
            ->sum('amount');
    }
}