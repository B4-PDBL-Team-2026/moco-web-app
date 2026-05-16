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
        $validated = $this->validated();

        return new UpdateFixedCostTemplateData(
            nameProvided: $this->has('name'),
            name: isset($validated['name']) ? trim((string) $validated['name']) : null,

            amountProvided: $this->has('amount'),
            amount: isset($validated['amount']) ? (string) $validated['amount'] : null,

            cycleTypeProvided: $this->has('cycleType'),
            cycleType: isset($validated['cycleType']) ? CycleType::tryFrom($validated['cycleType']) : null,

            dueDayProvided: $this->has('dueDay'),
            dueDay: isset($validated['dueDay']) ? (int) $validated['dueDay'] : null,

            isActiveProvided: $this->has('isActive'),
            isActive: isset($validated['isActive']) ? (bool) $validated['isActive'] : null,

            categoryIdProvided: $this->has('categoryId'),
            categoryId: isset($validated['categoryId']) ? (int) $validated['categoryId'] : null,
        );
    }
}
