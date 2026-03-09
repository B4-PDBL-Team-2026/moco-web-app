<?php
// app/Http/Requests/Transaction/UpdateTransactionRequest.php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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