<?php

namespace App\Http\Resources\Transaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionFeedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'feedType' => $this->feed_type,
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'type' => $this->type,
            'source' => $this->feed_type === 'transaction' ?
                $this->source
                : null,
            'note' => $this->feed_type === 'transaction' ?
                $this->note
                : null,
            'transactionAt' => date('c', strtotime($this->transaction_at)),
            'category' => $this->feed_type === 'transaction' ? [
                'id' => $this->category_id,
                'name' => $this->category_name,
                'icon' => $this->category_icon,
            ] : null,
        ];
    }
}
