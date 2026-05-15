<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCost\DTOs\CreateFixedCostTemplateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the HTTP payload for creating a single fixed cost template
 * in post-onboarding usage (not the batch onboarding flow).
 */
class StoreFixedCostTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'cycleType' => [
                'required',
                'string',
                new Enum(CycleType::class),
            ],
            'dueDay' => ['required', 'integer', 'min:1', 'max:31'],
            'isActive' => ['sometimes', 'boolean'],
            'categoryId' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Keys already match the DTO contract — pass through directly.
     */
    public function toDTO(): CreateFixedCostTemplateData
    {
        return new CreateFixedCostTemplateData(
            name: $this->validated('name'),
            amount: (string) $this->validated('amount'),
            cycleType: CycleType::from($this->validated('cycleType')),
            dueDay: (int) $this->validated('dueDay'),
            isActive: (bool) $this->input('isActive', true),
            categoryId: (int) $this->validated('categoryId'),
        );
    }
}
