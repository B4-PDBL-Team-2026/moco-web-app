<?php
// app/Http/Controllers/TransactionController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth; 
use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\DeleteTransactionAction;
use App\Actions\Transaction\GetTotalExpenseAction;
use App\Actions\Transaction\GetTransactionsAction;
use App\Actions\Transaction\UpdateTransactionAction;
use App\DTOs\Transaction\TransactionData;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Traits\WebResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    use WebResponse;
    
    /**
     * Menampilkan daftar transaksi (riwayat).
     */
    public function index(
        Request $request,
        GetTransactionsAction $getTransactions,
        GetTotalExpenseAction $getTotalExpense
    ) {
        $filters = [
            'bulan'   => $request->input('bulan', date('m')),
            'tahun'   => $request->input('tahun', date('Y')),
            'search'  => $request->input('search'),
            'kategori'=> $request->input('kategori'),
        ];

        $transactions = $getTransactions->execute($filters);
        $totalExpense = $getTotalExpense->execute($filters);
        $categories   = Category::all();

        return view('transactions.index', compact(
            'transactions',
            'totalExpense',
            'categories',
            'filters'
        ));
    }

    /**
     * Menampilkan form tambah transaksi.
     */
    public function create()
    {
        $categories = Category::all();
        return view('transactions.create', compact('categories'));
    }

    /**
     * Menyimpan transaksi baru.
     */
    public function store(StoreTransactionRequest $request, CreateTransactionAction $action)
    {
        $dto = TransactionData::fromRequest($request);
        $action->execute($dto);

        // Trigger update saldo (bisa via event)
        return $this->redirectSuccess('transactions.index', 'Transaksi berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit transaksi (detail).
     */
    public function edit($id)
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        $categories = Category::all();

        return view('transactions.edit', compact('transaction', 'categories'));
    }

    /**
     * Memperbarui transaksi.
     */
    public function update(
        $id,
        UpdateTransactionRequest $request,
        UpdateTransactionAction $action
    ) {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        $dto = TransactionData::fromRequest($request);
        $action->execute($transaction, $dto);

        // Trigger update saldo (selisih)
        return $this->redirectSuccess('transactions.edit', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Menghapus transaksi.
     */
    public function destroy($id, DeleteTransactionAction $action)
    {
        $transaction = Transaction::where('user_id', Auth::id())->findOrFail($id);
        $action->execute($transaction);

        return $this->redirectSuccess('transactions.index', 'Transaksi berhasil dihapus.');
    }

    /**
     * (Opsional) Arahkan show ke edit.
     */
    public function show($id)
    {
        return redirect()->route('transactions.edit', $id);
    }
}