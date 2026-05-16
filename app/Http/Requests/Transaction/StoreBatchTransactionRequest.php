<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transaction\DTOs\CreateBatchTransactionData;
use App\Domains\Transaction\DTOs\CreateBatchTransactionItemData;
use App\Domains\Transaction\Enums\TransactionSource;
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
            'transactionAt' => ['required', 'date', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:1000'],
            'source' => ['nullable', Rule::enum(TransactionSource::class)],

            // Nested validation for items
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'gt:0'],
            'items.*.categoryId' => ['required', 'integer', 'exists:categories,id'],
            'items.*.type' => ['required', Rule::enum(TransactionType::class)],
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
                type: TransactionType::tryFrom($item['type']),
                note: $item['note'] ?? null,
            ),
            $this->validated('items')
        );

        return new CreateBatchTransactionData(
            name: $this->validated('name'),
            note: $this->validated('note') ?? null,
            transactionAt: $this->validated('transactionAt'),
            source: $this->has('source') ?
                TransactionSource::tryFrom($this->validated('source'))
                : TransactionSource::MANUAL,
            items: $items,
        );
    }
}
