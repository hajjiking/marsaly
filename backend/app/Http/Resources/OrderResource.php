<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'reference'        => $this->reference,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'payment_status'   => $this->payment_status,
            'subtotal'         => (float) $this->subtotal,
            'delivery_fee'     => (float) $this->delivery_fee,
            'discount_total'   => (float) $this->discount_total,
            'total'            => (float) $this->total,
            'distance_km'      => (float) $this->distance_km,
            'estimated_minutes'=> $this->estimated_minutes ? (float) $this->estimated_minutes : null,
            'prep_time_minutes'=> $this->prep_time_minutes ? (float) $this->prep_time_minutes : null,
            'promo_code'       => $this->promo_code,
            'client_note'      => $this->client_note,
            'cancel_reason'    => $this->cancel_reason,
            'reject_reason'    => $this->reject_reason,
            'address_snapshot' => $this->address_snapshot,
            // delivery OTP only revealed to the involved driver/admin
            'delivery_otp'     => $this->when(
                in_array($request->user()?->role?->name, ['driver', 'admin'], true),
                $this->delivery_otp
            ),
            'rating_merchant'  => $this->rating_merchant,
            'rating_driver'    => $this->rating_driver,
            'created_at'       => $this->created_at?->toIso8601String(),
            'accepted_at'      => $this->accepted_at?->toIso8601String(),
            'ready_at'         => $this->ready_at?->toIso8601String(),
            'picked_up_at'     => $this->picked_up_at?->toIso8601String(),
            'delivered_at'     => $this->delivered_at?->toIso8601String(),
            'items'            => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history'   => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'merchant'         => new MerchantResource($this->whenLoaded('merchant')),
            'driver'           => $this->whenLoaded('driver', fn () => [
                'id' => $this->driver->id,
                'name' => $this->driver->user?->name,
                'phone' => $this->driver->user?->phone,
                'vehicle_type' => $this->driver->vehicle_type,
                'plate_number' => $this->driver->plate_number,
                'rating_avg' => round((float) $this->driver->rating_avg, 2),
                'lat' => $this->driver->lat,
                'lng' => $this->driver->lng,
            ]),
            'delivery'         => new DeliveryResource($this->whenLoaded('delivery')),
        ];
    }
}
