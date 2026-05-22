<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionBatchResource extends JsonResource
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
            'name' => $this->name,
            'note' => $this->note,
            'totalAmount' => $this->total_amount,
            'type' => $this->type,
            'transactionAt' => $this->transaction_at?->toIso8601String(),
            'items' => $this->whenLoaded('transactions', function () {
                return TransactionResource::collection($this->transactions);
            }),
        ];
    }
}
