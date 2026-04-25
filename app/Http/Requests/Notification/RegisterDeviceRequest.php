<?php

namespace App\Http\Requests\Notification;

use App\Domains\Notification\DTOs\RegisterDeviceData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
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
        return [
            'deviceId' => 'required|string',
            'deviceType' => 'required|in:android,ios',
            'fcmToken' => 'required|string',
        ];
    }

    public function toDTO(): RegisterDeviceData
    {
        return new RegisterDeviceData(
            deviceId: $this->input('deviceId'),
            deviceType: $this->input('deviceType'),
            fcmToken: $this->input('fcmToken'),
        );
    }
}
