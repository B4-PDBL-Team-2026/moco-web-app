<?php

namespace App\Http\Resources\Budgeting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyLimitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'flooringLimit' => $this->flooring_limit,
            'ceilingLimit' => $this->ceiling_limit,
        ];
    }
}
