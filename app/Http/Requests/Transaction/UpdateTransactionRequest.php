<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
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
                Rule::exists('categories', 'id')
                    ->where(fn ($query) => $query->where('user_id', $this->user()->id)),
            ],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'transactionDate' => ['sometimes', 'required', 'date'],
        ];
    }
}
