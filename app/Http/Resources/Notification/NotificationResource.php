<?php

namespace App\Http\Resources\Notification;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->data['title'] ?? null,
            'message' => $this->data['message'] ?? null,
            'code' => $this->data['code'] ?? null,
            'occurrenceId' => $this->data['id'] ?? null,
            'isRead' => $this->read_at !== null,
            'readAt' => $this->read_at,
            'createdAt' => $this->created_at,
        ];
    }
}
