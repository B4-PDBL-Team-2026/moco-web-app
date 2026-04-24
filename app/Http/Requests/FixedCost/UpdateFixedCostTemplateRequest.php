<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\Budgeting\Enums\CycleType;
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

    public function messages(): array
    {
        return [
            'amount.gt' => 'Amount must be greater than zero.',
            'cycleType.in' => 'Cycle type must be one of: '.implode(', ', array_column(CycleType::cases(), 'value')).'.',
        ];
    }

    public function toDto(): array
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = $this->input('name');
        }
        if ($this->has('amount')) {
            $data['amount'] = $this->input('amount');
        }
        if ($this->has('cycleType')) {
            $data['cycleType'] = $this->input('cycleType');
        }
        if ($this->has('dueDay')) {
            $data['dueDay'] = (int) $this->input('dueDay');
        }
        if ($this->has('isActive')) {
            $data['isActive'] = $this->boolean('isActive');
        }
        if ($this->has('categoryId')) {
            $data['categoryId'] = (int) $this->input('categoryId');
        }

        return $data;
    }
}
