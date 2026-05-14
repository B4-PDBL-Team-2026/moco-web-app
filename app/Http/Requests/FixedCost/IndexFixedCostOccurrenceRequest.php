<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\FixedCost\DTOs\FilterFixedCostOccurrenceData;
use App\Domains\FixedCost\Enums\FixedCostOccurenceStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class IndexFixedCostOccurrenceRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', new Enum(FixedCostOccurenceStatus::class)],
            'startDate' => ['nullable', 'date_format:Y-m-d'],
            'endDate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function toDTO(): FilterFixedCostOccurrenceData
    {
        return new FilterFixedCostOccurrenceData(
            keyword: $this->has('keyword') ? $this->validated('keyword') : null,
            status: $this->has('status')
                ? FixedCostOccurenceStatus::from($this->validated('status'))
                : null,
            startDate: $this->has('startDate') ? $this->validated('startDate') : null,
            endDate: $this->has('endDate') ? $this->validated('endDate') : null,
            page: (int) $this->input('page', 1),
            perPage: (int) $this->input('per_page', 15),
        );
    }
}
