<?php

namespace App\Http\Controllers\Api\Notification;

use App\Domains\Notification\Actions\RegisterUserDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\RegisterDeviceRequest;
use App\Http\Responses\ApiResponse;

class DeviceController extends Controller
{
    /**
     * Register user device for push mobile notification
     *
     * @response array{success: bool, message: string}
     */
    public function registerDevice(
        RegisterDeviceRequest $request,
        RegisterUserDeviceAction $action
    ): ApiResponse {
        $action->execute(auth()->user(), $request->toDTO());

        return $this->successResponse(message: 'Device registered successfully.');
    }
}
