<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'user_Id' => $this->user_id,
            'order_id' => $this->order_id,
            'amount' => number_format($this->amount),
            'token' => $this->token,
            'ref_number' => $this->ref_number,
            'status' => $this->status ? 'success' : 'failure',
            'created_at' => verta($this->created_at)->format('%B %d, %Y'),
        ];
    }
}
