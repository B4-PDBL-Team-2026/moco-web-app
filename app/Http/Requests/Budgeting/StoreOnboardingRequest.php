<?php

namespace App\Http\Requests\Budgeting;

use App\Domains\Budgeting\DTOs\CompleteOnboardingData;
use App\Domains\Budgeting\Enums\CycleType;
use App\Domains\FixedCost\DTOs\CreateFixedCostTemplateData;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'budgetCycle' => ['required', Rule::enum(CycleType::class)],
            'initialBalance' => ['required', 'numeric', 'min:0'],
            'flooringLimit' => ['required', 'numeric', 'min:0'],
            'ceilingLimit' => ['required', 'numeric', 'gte:flooringLimit'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone'],

            'fixedCosts' => ['present', 'array'],
            'fixedCosts.*' => ['array'],
            'fixedCosts.*.name' => [
                'required',
                'string',
            ],
            'fixedCosts.*.amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'fixedCosts.*.cycleType' => [
                'required',
                new Enum(CycleType::class),
                function (string $attribute, mixed $value, Closure $fail) {
                    $budgetCycle = request()->input('budgetCycle');

                    if ($budgetCycle === CycleType::WEEKLY->value && $value === CycleType::MONTHLY->value) {
                        $fail('Monthly fixed cost is not allowed when budget cycle is weekly.');
                    }
                },
            ],
            'fixedCosts.*.categoryId' => [
                'required',
                'integer',
            ],
            'fixedCosts.*.isActive' => [
                'sometimes',
                'boolean',
            ],
            'fixedCosts.*.dueDay' => [
                'required',
                'integer',
                'min:1',
                function (string $attribute, mixed $value, Closure $fail) {
                    preg_match('/fixedCosts\.(\d+)\.dueDay/', $attribute, $matches);
                    $index = $matches[1];

                    if ($index === null) {
                        return;
                    }
                    $cycleType = request()->input("fixedCosts.$index.cycleType");

                    if ($cycleType === CycleType::WEEKLY->value && (int) $value > 7) {
                        $fail('The dueDay for weekly fixed cost must be between 1 and 7.');
                    }

                    if ($cycleType === CycleType::MONTHLY->value && (int) $value > 31) {
                        $fail('The dueDay for monthly fixed cost must be between 1 and 31.');
                    }
                },
            ],
        ];
    }

    public function toDTO(): CompleteOnboardingData
    {
        return new CompleteOnboardingData(
            cycleType: CycleType::from($this->validated('budgetCycle')),
            initialBalance: $this->validated('initialBalance'),
            flooringLimit: $this->validated('flooringLimit'),
            ceilingLimit: $this->validated('ceilingLimit'),
            fixedCosts: array_map(
                fn (array $fixedCost) => new CreateFixedCostTemplateData(
                    name: $fixedCost['name'],
                    amount: $fixedCost['amount'],
                    cycleType: CycleType::from($fixedCost['cycleType']),
                    dueDay: $fixedCost['dueDay'],
                    isActive: $fixedCost['isActive'] ?? true,
                    categoryId: $fixedCost['categoryId'],
                ),
                $this->validated('fixedCosts') ?? []
            ),
            timezone: $this->validated('timezone'),
        );
    }
}
