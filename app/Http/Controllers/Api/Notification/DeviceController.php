<?php

namespace App\Http\Controllers\Api\Notification;

use App\Domains\Notification\Actions\GetAllRegisteredDeviceAction;
use App\Domains\Notification\Actions\RegisterUserDeviceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\RegisterDeviceRequest;
use App\Http\Resources\Notification\UserDeviceResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;

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
        Log::info('[Device Controller] Incoming device registration for controller: '.$request->toDTO()->deviceId);
        $action->execute(auth()->user(), $request->toDTO());

        return $this->successResponse(message: 'Device registered successfully.');
    }

    /**
     * Get user registered devices for push notification target.
     *
     * @response array{data: array<UserDeviceResource>, success: bool, message: string}
     */
    public function getAllRegisteredDevice(GetAllRegisteredDeviceAction $action)
    {
        $result = UserDeviceResource::collection($action->execute(auth()->id()));

        return $this->successResponse($result, 'Retrieved successfully.');
    }
}
