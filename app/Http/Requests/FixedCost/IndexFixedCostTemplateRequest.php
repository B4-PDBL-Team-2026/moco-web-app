<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCost\DTOs\FilterFixedCostTemplateData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates query-string parameters for GET /api/fixed-costs.
 *
 * All parameters are optional — omitting them returns unfiltered,
 * paginated results with defaults applied in FilterFixedCostTemplateData.
 */
class IndexFixedCostTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dueDay' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:31'],
            'cycleType' => ['sometimes', 'nullable', 'string', Rule::in(array_column(CycleType::cases(), 'value'))],
            'isActive' => ['sometimes', 'nullable', 'boolean'],
            'perPage' => ['sometimes', 'integer', 'min:1', 'max:'.FilterFixedCostTemplateData::MAX_PER_PAGE],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function toDTO(): FilterFixedCostTemplateData
    {
        return FilterFixedCostTemplateData::fromArray($this->only([
            'keyword',
            'dueDay',
            'cycleType',
            'isActive',
            'perPage',
            'page',
        ]));
    }
}
