<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryType = $this->input('categoryType');

        return [
            'month'        => ['nullable', 'integer', 'between:1,12'],
            'year'         => ['nullable', 'integer', 'digits:4'],
            'search'       => ['nullable', 'string', 'max:100'],

            'categoryType' => [
                'nullable',
                'string',
                Rule::in(['system', 'custom']),
            ],

            'categoryId' => array_filter([
                'nullable',
                'integer',
                $categoryType === 'system'
                    ? Rule::exists('system_categories', 'id')
                    : null,
                $categoryType === 'custom'
                    ? Rule::exists('custom_categories', 'id')
                        ->where('user_id', $this->user()->id)
                    : null,
            ]),

            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}