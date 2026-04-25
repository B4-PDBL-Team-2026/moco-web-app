<?php

namespace App\Http\Controllers\Api\Notification;

use App\Domains\Notification\Actions\RegisterUserDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\RegisterDeviceRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    use ApiResponse;

    /**
     * Register user device for push mobile notification
     */
    public function registerDevice(
        RegisterDeviceRequest $request,
        RegisterUserDeviceAction $action
    ): JsonResponse {
        $action->execute(auth()->user(), $request->toDTO());

        return $this->success(message: 'Device registered successfully.');
    }
}
