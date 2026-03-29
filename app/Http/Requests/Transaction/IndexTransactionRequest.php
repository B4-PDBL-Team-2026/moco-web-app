<?php
namespace App\Http\Requests\Transaction;
use Illuminate\Foundation\Http\FormRequest;

class IndexTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month'      => ['nullable', 'integer', 'between:1,12'],
            'year'       => ['nullable', 'integer', 'digits:4'],
            'search'     => ['nullable', 'string', 'max:100'],
            'categoryId' => ['nullable', 'integer'],
            'perPage'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}