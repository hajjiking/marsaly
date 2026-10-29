<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_id'         => $this->order_id,
            'driver_id'        => $this->driver_id,
            'assignment_type'  => $this->assignment_type,
            'state'            => $this->state,
            'fee'              => (float) $this->fee,
            'tip'              => (float) $this->tip,
            'offer_expires_at' => $this->offer_expires_at?->toIso8601String(),
            'arrived_merchant_at' => $this->arrived_merchant_at?->toIso8601String(),
            'picked_up_at'     => $this->picked_up_at?->toIso8601String(),
            'delivered_at'     => $this->delivered_at?->toIso8601String(),
        ];
    }
}
