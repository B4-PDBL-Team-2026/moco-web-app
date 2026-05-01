<?php

namespace App\Http\Requests\User\Auth;

use App\Domains\User\DTOs\Auth\ResetPasswordData;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function toDTO(): ResetPasswordData
    {
        return new ResetPasswordData(
            $this->validated('email'),
            $this->validated('password'),
            $this->validated('token'),
        );
    }
}
