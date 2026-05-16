<?php

namespace App\Http\Requests\Transaction;

use App\Domains\Transaction\DTOs\FilterTransactionData;
use App\Domains\Transaction\Enums\TransactionFeedType;
use App\Domains\Transaction\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'digits:4'],
            'search' => ['nullable', 'string', 'max:100'],
            'transactionType' => ['nullable', 'string', new Enum(TransactionType::class)],
            'transactionFeedType' => ['nullable', 'string', new Enum(TransactionFeedType::class)],
            'categoryId' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Transform validated request data into a pure DTO.
     */
    public function toDTO(): FilterTransactionData
    {
        $validated = $this->validated();

        return new FilterTransactionData(
            month: isset($validated['month']) ? (int) $validated['month'] : null,
            year: isset($validated['year']) ? (int) $validated['year'] : null,
            search: $validated['search'] ?? null,
            categoryId: isset($validated['categoryId']) ? (int) $validated['categoryId'] : null,
            perPage: isset($validated['perPage']) ? (int) $validated['perPage'] : 10,
            transactionType: isset($validated['transactionType'])
                ? TransactionType::from($validated['transactionType'])
                : null,
            transactionFeedType: isset($validated['transactionFeedType'])
                ? TransactionFeedType::from($validated['transactionFeedType'])
                : null,
        );
    }
}
