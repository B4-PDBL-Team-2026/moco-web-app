<?php

namespace App\Http\Requests\Onboarding;

use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\Budgeting\Enums\DeductionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'budgetCycle' => ['required', new Enum(CycleType::class)],
            'allowanceAmount' => ['required', 'numeric', 'min:1'],
            'fixedCosts' => ['sometimes', 'array'],
            'fixedCosts.*' => ['array'],
            'fixedCosts.*.name' => [
                'required_with:fixedCosts.*.amount,fixedCosts.*.deductionType',
                'string',
            ],
            'fixedCosts.*.amount' => [
                'required_with:fixedCosts.*.name,fixedCosts.*.deductionType',
                'numeric',
                'min:0',
            ],
            'fixedCosts.*.deductionType' => [
                'required_with:fixedCosts.*.name,fixedCosts.*.amount',
                new Enum(DeductionType::class),
            ],
            'fixedCosts.*.cycle' => [
                'nullable',
                new Enum(CycleType::class),
            ],
        ];
    }
}
