<?php
// app/Http/Requests/Transaction/StoreTransactionRequest.php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Atur sesuai kebutuhan (misal: user terautentikasi)
    }

    public function rules(): array
    {
        return [
            'amount'           => 'required|numeric|min:0',
            'name'             => 'required|string|max:30',
            'type'             => 'required|in:pengeluaran,pemasukan',
            'category_id'      => 'required|exists:categories,id',
            'transaction_date' => 'required|date',
            'note'             => 'nullable|string|max:255',
        ];
    }
}