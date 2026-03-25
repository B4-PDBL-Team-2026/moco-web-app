<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\Budgeting\Enums\CycleType;
use App\Models\CustomCategory;
use App\Models\SystemCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates the HTTP payload for creating a single fixed cost template
 * in post-onboarding usage (not the batch onboarding flow).
 */
class StoreFixedCostTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gate handled by auth middleware.
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
            'categoryType' => [
                'required',
                'string',
                Rule::in([SystemCategory::class, CustomCategory::class]),
            ],
            'categoryId' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'cycleType.in' => 'Cycle type must be one of: '.implode(', ', array_column(CycleType::cases(), 'value')).'.',
            'categoryType.in' => 'Category type must be a valid morphable class.',
            'dueDay.min' => 'Due day must be at least 1.',
            'dueDay.max' => 'Due day cannot exceed 31.',
            'amount.gt' => 'Amount must be greater than zero.',
        ];
    }

    /**
     * Keys already match the DTO contract — pass through directly.
     */
    public function toDto(): array
    {
        return [
            'name' => $this->input('name'),
            'amount' => $this->input('amount'),
            'cycleType' => $this->input('cycleType'),
            'dueDay' => (int) $this->input('dueDay'),
            'isActive' => $this->boolean('isActive', true),
            'categoryType' => $this->input('categoryType'),
            'categoryId' => (int) $this->input('categoryId'),
        ];
    }
}
