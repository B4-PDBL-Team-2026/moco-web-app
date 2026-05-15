<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCost\DTOs\UpdateFixedCostTemplateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the HTTP payload for a sparse (PATCH) update of a fixed cost template.
 *
 * All fields are optional — only provided fields will be applied.
 * When updating category, both category_type and category_id must be provided together.
 */
class UpdateFixedCostTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', 'min:1'],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'cycleType' => ['sometimes', 'string', Rule::in(array_column(CycleType::cases(), 'value'))],
            'dueDay' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'isActive' => ['sometimes', 'boolean'],
            'categoryId' => [
                'integer',
                'min:1',
                'exists:categories,id',
            ],
        ];
    }

    public function toDTO(): UpdateFixedCostTemplateData
    {
        return new UpdateFixedCostTemplateData(
            name: $this->has('name') ? trim((string) $this->validated('name')) : null,

            amount: $this->has('amount') ? (string) $this->validated('amount') : null,

            cycleType: $this->has('cycleType')
                ? CycleType::from($this->validated('cycleType'))
                : null,

            dueDay: $this->has('dueDay') ? (int) $this->validated('dueDay') : null,

            isActive: $this->has('isActive') ? (bool) $this->validated('isActive') : null,

            categoryId: $this->has('categoryId') ? (int) $this->validated('categoryId') : null,
        );
    }
}
