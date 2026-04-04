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
            'categoryType' => ['required', 'string', Rule::in(['system', 'custom'])],

            'categoryId' => [
                'required',
                'integer',
                $categoryType === 'system'
                    ? Rule::exists('system_categories', 'id')
                    : Rule::exists('custom_categories', 'id')->where('user_id', $this->user()->id),
            ],

            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'transactionAt' => ['required', 'date', 'before_or_equal:now'],
        ];
    }
}
