<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTransactionRequest extends FormRequest
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
        $categoryType = $this->input('categoryType');

        return [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'digits:4'],
            'search' => ['nullable', 'string', 'max:100'],
            'categoryId' => [
                'nullable',
                'integer',
                $categoryType === 'system'
                    ? Rule::exists('system_categories', 'id')
                    : Rule::exists('custom_categories', 'id')->where('user_id', $this->user()->id),
            ],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
