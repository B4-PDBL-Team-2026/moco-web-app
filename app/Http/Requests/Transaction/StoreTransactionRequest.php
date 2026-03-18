<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'transactionDate' => ['required', 'date'],
            'categoryId' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('user_id', auth()->id())),
            ],
        ];
    }
}
