<?php

namespace App\Http\Resources\FixedCost;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixedCostOccurrenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'templateId' => $this->fixed_cost_template_id,
            'name' => $this->name,
            'amount' => $this->amount,
            'dueDate' => $this->due_date->toDateString(),
            'status' => $this->status,
            'paidAt' => $this->paid_at ? $this->paid_at->toIso8601String() : null,
            'voidedAt' => $this->voided_at ? $this->voided_at->toIso8601String() : null,
            'cycleType' => $this->cycle_type,
            'note' => $this->note,
        ];
    }
}
