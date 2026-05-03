<?php

namespace App\Http\Resources\FixedCost;

use Illuminate\Http\Resources\Json\JsonResource;

class FixedCostTemplateResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'cycleType' => $this->cycle_type,
            'dueDay' => $this->due_day,
            'isActive' => (bool) $this->is_active,
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'icon' => $this->category->icon ?? null,
                ];
            }),
        ];
    }
}
