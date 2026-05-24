<?php

namespace App\Http\Requests\User\Auth;

use App\Domains\User\DTOs\Auth\LoginUserData;
use Illuminate\Foundation\Http\FormRequest;

class LoginWithGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'google_token' => ['required', 'string'],
        ];
    }
}
