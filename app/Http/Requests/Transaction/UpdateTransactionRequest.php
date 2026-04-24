<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transactions\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['sometimes', 'required', 'decimal:0,2', 'gt:0'],
            'type' => ['sometimes', 'required', Rule::enum(TransactionType::class)],
            'categoryId' => [
                'sometimes',
                'required',
                'integer',
                'exists:categories,id',
            ],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'transactionAt' => ['sometimes', 'required', 'date', 'before_or_equal:now'],
        ];
    }
}
