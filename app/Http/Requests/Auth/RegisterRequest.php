<?php

namespace App\Http\Requests\Auth;

use App\Domains\Auth\DTOs\RegisterUserData;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDTO(): RegisterUserData
    {
        return new RegisterUserData(
            name: $this->validated('name'),
            email: $this->validated('email'),
            password: $this->validated('password'),
        );
    }
}
