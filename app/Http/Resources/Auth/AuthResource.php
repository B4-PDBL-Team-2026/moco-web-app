<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user' => $this['user'],
            'token' => $this['token'],
            'requiresOnboarding' => (bool) $this['requiresOnboarding'],
        ];
    }
}
