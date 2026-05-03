<?php

namespace App\Http\Requests\FixedCost;

use App\Domains\FixedCost\DTOs\UpdateFixedCostOccurrenceAmountData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the amount payload for update fixed cost occurrence amount flow:
 *   cancel → (this request) edit amount → re-confirm.
 *
 * The occurrence must already be in VOID status before this endpoint is called.
 * That constraint is enforced at the action layer, not here.
 */
class UpdateFixedCostOccurrenceAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'Amount must be greater than zero.',
        ];
    }

    public function toDTO(): UpdateFixedCostOccurrenceAmountData
    {
        return new UpdateFixedCostOccurrenceAmountData($this->input('amount'));
    }
}
