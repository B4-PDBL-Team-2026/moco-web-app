<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transaction\DTOs\UpdateTransactionData;
use App\Domains\Transaction\Enums\TransactionType;
use Carbon\CarbonImmutable;
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

    public function toDTO(): UpdateTransactionData
    {
        $validated = $this->validated();

        return new UpdateTransactionData(
            nameProvided: $this->has('name'),
            name: $validated['name'] ?? null,

            amountProvided: $this->has('amount'),
            amount: isset($validated['amount']) ? (string) $validated['amount'] : null,

            categoryIdProvided: $this->has('categoryId'),
            categoryId: isset($validated['categoryId']) ? (int) $validated['categoryId'] : null,

            noteProvided: $this->has('note'),
            note: $validated['note'] ?? null,

            transactionAtProvided: $this->has('transactionAt'),
            transactionAt: isset($validated['transactionAt'])
                ? CarbonImmutable::parse($validated['transactionAt'])->utc()
                : null,
        );
    }
}
