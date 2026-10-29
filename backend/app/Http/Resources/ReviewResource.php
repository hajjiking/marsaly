<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'order_id'      => $this->order_id,
            'target'        => $this->target,
            'rating'        => (float) $this->rating,
            'comment'       => $this->comment,
            'merchant_reply'=> $this->merchant_reply,
            'replied_at'    => $this->replied_at?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
            'user'          => $this->when(
                $request->user()?->hasRole('merchant', 'admin', 'driver'),
                ['id' => $this->user_id, 'name' => $this->user?->name ?: 'Client']
            ),
        ];
    }
}
