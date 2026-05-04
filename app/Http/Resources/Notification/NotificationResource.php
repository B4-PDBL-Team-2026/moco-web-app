<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'isRead' => $this->read_at !== null,
            'readAt' => $this->read_at->toIso8601String(),
            'createdAt' => $this->created_at,
            'payload' => [
                'notificationCode' => $this->data['code'],
                'occurrenceId' => $this->data['id'],
            ],
        ];
    }
}
