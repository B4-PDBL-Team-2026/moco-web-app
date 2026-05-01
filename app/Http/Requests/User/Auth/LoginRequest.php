<?php

namespace App\Http\Requests\User\Auth;

use App\Domains\User\DTOs\Auth\LoginUserData;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function toDTO(): LoginUserData
    {
        return new LoginUserData(
            $this->validated('email'),
            $this->validated('password'),
        );
    }
}
