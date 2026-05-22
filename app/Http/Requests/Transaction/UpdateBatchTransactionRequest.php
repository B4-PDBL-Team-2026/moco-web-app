<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transaction\DTOs\UpdateBatchTransactionData;
use App\Domains\Transaction\DTOs\UpdateBatchTransactionItemData;
use App\Domains\Transaction\Enums\TransactionSource;
use App\Domains\Transaction\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBatchTransactionRequest extends FormRequest
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

            // Nested validation for full items replacement
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'gt:0'],
            'items.*.categoryId' => ['required', 'integer', 'exists:categories,id'],
            'items.*.type' => ['required', Rule::enum(TransactionType::class)],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDTO(): UpdateBatchTransactionData
    {
        $validated = $this->validated();

        $items = array_map(
            fn (array $item) => new UpdateBatchTransactionItemData(
                name: $item['name'],
                amount: (string) $item['amount'],
                categoryId: (int) $item['categoryId'],
                type: TransactionType::from($item['type']),
                note: $item['note'] ?? null,
            ),
            $validated['items']
        );

        return new UpdateBatchTransactionData(
            name: $validated['name'],
            note: $validated['note'] ?? null,
            transactionAt: CarbonImmutable::parse($validated['transactionAt'])->utc(),
            source: isset($validated['source']) ? TransactionSource::from($validated['source']) : null,
            items: $items,
        );
    }
}
