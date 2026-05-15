<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transaction\DTOs\CreateBatchTransactionData;
use App\Domains\Transaction\DTOs\CreateBatchTransactionItemData;
use App\Domains\Transaction\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'transactionAt' => ['required', 'date', 'before_or_equal:now'],

            // Nested validation for items
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'gt:0'],
            'items.*.categoryId' => ['required', 'integer', 'exists:categories,id'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDTO(): CreateBatchTransactionData
    {
        $items = array_map(
            fn (array $item) => new CreateBatchTransactionItemData(
                name: $item['name'],
                amount: (string) $item['amount'],
                categoryId: (int) $item['categoryId'],
                note: $item['note'] ?? null,
            ),
            $this->validated('items')
        );

        return new CreateBatchTransactionData(
            name: $this->validated('name'),
            type: TransactionType::from($this->validated('type')),
            transactionAt: $this->validated('transactionAt'),
            items: $items,
        );
    }
}
