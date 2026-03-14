<?php
namespace App\Http\Requests;
use App\Enums\FixedCostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OnboardingRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'siklus' => 'required|in:mingguan,bulanan',
            'nominal_uang_saku' => 'required|numeric|min:1', // User tidak bisa lanjut jika 0
            'fixed_costs' => 'array',
            'fixed_costs.*.nama' => 'required|string',
            'fixed_costs.*.nominal' => 'required|numeric|min:0',
           'fixed_costs.*.status' => ['required', new Enum(FixedCostStatus::class)],
        ];
    }
}

