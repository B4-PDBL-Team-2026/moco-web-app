<?php
// app/Actions/Transaction/GetTransactionsAction.php

namespace App\Actions\Transaction;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GetTransactionsAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        $query = Transaction::with('category')
            ->where('user_id', Auth::id());

        // Filter bulan
        if (isset($filters['bulan']) && isset($filters['tahun'])) {
            $query->whereYear('created_at', $filters['tahun'])
                  ->whereMonth('created_at', $filters['bulan']);
        }

        // Pencarian
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Filter kategori
        if (!empty($filters['kategori'])) {
            $query->where('category_id', $filters['kategori']);
        }

        return $query->orderBy('created_at', 'desc')
                     ->paginate(20)
                     ->withQueryString();
    }
}