<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

/**
 * Requires the user to re-enter their current password before deletion,
 * preventing accidental or hijacked account removal.
 */
class ConfirmDeleteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Add an after-validation hook that verifies the provided password
     * matches the user's stored hash. Doing this here keeps the controller
     * and action free of authentication concerns.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! Hash::check($this->input('password'), $this->user()->password)) {
                    $validator->errors()->add('password', 'The provided password is incorrect.');
                }
            },
        ];
    }
}
