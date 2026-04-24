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
        $categoryType = $this->input('categoryType');

        return [
            'categoryId' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'transactionAt' => ['required', 'date', 'before_or_equal:now'],
        ];
    }
}
