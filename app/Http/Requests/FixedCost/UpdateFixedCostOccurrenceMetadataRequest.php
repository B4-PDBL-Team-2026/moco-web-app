<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\FixedCost\DTOs\UpdateFixedCostOccurrenceMetadataData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates metadata-only updates for a fixed cost occurrence.
 * No financial fields are accepted here — those go through the amount action.
 */
class UpdateFixedCostOccurrenceMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', 'min:1'],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Returns only the whitelisted metadata fields that were actually present.
     */
    public function toDTO(): UpdateFixedCostOccurrenceMetadataData
    {
        return new UpdateFixedCostOccurrenceMetadataData(
            name: $this->has('name') ? $this->validated('name') : null,
            note: $this->has('note') ? $this->validated('note') : null,
        );
    }
}
