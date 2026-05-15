<?php

namespace App\Http\Requests\Category;

use App\Domains\Category\DTOs\UpdateCustomCategoryData;
use App\Domains\Transaction\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the HTTP payload for updating an existing custom category.
 * All fields are optional (PATCH behavior).
 */
class UpdateCustomCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'string', new Enum(TransactionType::class)],
        ];
    }

    /**
     * Map the validated request data into a strongly-typed DTO.
     */
    public function toDTO(): UpdateCustomCategoryData
    {
        $type = $this->validated('type');

        return new UpdateCustomCategoryData(
            name: $this->validated('name'),
            icon: $this->validated('icon'),
            type: $type !== null ? TransactionType::from($type) : null,
        );
    }
}
