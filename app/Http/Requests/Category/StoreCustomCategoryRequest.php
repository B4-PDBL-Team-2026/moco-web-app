<?php

namespace App\Http\Requests\Category;

use App\Domains\Category\DTOs\CreateCustomCategoryData;
use App\Domains\Transaction\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the HTTP payload for creating a custom category.
 */
class StoreCustomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Assume authentication is handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', new Enum(TransactionType::class)],
        ];
    }

    public function messages(): array
    {
        $allowedValues = implode(', ', array_column(TransactionType::cases(), 'value'));

        return [
            'type.in' => __('errors.validation.invalid_type', [
                'values' => $allowedValues,
            ]),
        ];
    }

    /**
     * Map the validated request data into a strongly-typed DTO.
     */
    public function toDTO(): CreateCustomCategoryData
    {
        return new CreateCustomCategoryData(
            name: $this->validated('name'),
            icon: $this->validated('icon'),
            type: TransactionType::from($this->validated('type')),
        );
    }
}
