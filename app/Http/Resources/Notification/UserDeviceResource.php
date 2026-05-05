<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class UserDeviceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user_id,
            'deviceId' => $this->device_id,
            'fcmToken' => $this->fcm_token,
            'deviceType' => $this->device_type,
        ];
    }
}
