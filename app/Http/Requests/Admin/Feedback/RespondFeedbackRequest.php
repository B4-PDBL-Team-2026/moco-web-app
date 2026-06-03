<?php

namespace App\Http\Requests\Admin\Feedback;

use Illuminate\Foundation\Http\FormRequest;

class RespondFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_reply' => ['required', 'string', 'min:5'],
        ];
    }
}