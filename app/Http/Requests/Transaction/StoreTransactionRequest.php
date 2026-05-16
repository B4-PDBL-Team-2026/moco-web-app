<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transaction\DTOs\CreateTransactionData;
use App\Domains\Transaction\Enums\TransactionType;
use Carbon\CarbonImmutable;
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

    public function toDTO(): CreateTransactionData
    {
        $validated = $this->validated();

        return new CreateTransactionData(
            categoryId: (int) $validated['categoryId'],
            name: $validated['name'],
            amount: (string) $validated['amount'],
            type: TransactionType::from($validated['type']),
            note: $validated['note'] ?? null,
            transactionAt: CarbonImmutable::parse($validated['transactionAt'])->utc(),
        );
    }
}
