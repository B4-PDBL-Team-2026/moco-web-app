<?php
// app/Actions/Transaction/GetTotalExpenseAction.php

namespace App\Actions\Transaction;

use App\Models\Transaction; 

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GetTotalExpenseAction
{
    public function execute(array $filters): float
    {
        $query = Transaction::where('user_id', Auth::id())
            ->where('type', 'pengeluaran');

        if (isset($filters['bulan']) && isset($filters['tahun'])) {
            $query->whereYear('created_at', $filters['tahun'])
                  ->whereMonth('created_at', $filters['bulan']);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['kategori'])) {
            $query->where('category_id', $filters['kategori']);
        }

        return (float) $query->sum('amount');
    }
}