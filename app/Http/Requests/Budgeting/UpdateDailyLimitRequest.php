<?php

namespace App\Http\Requests\Budgeting;

use App\Domains\Budgeting\DTOs\UpdateDailyLimitData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyLimitRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'flooringLimit' => ['required', 'numeric', 'min:1'],
            'ceilingLimit' => ['required', 'numeric', 'gte:flooringLimit'],
        ];
    }

    public function toDTO(): UpdateDailyLimitData
    {
        return new UpdateDailyLimitData(
            flooringLimit: (string) $this->input('flooringLimit'),
            ceilingLimit: (string) $this->input('ceilingLimit'),
        );
    }
}
