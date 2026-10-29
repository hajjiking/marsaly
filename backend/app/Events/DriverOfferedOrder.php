<?php

namespace App\Events;

use App\Models\Delivery;

class DriverOfferedOrder extends MarsalyEvent
{
    public function __construct(public Delivery $delivery) {}

    public function broadcastOn(): array
    {
        return [new Channel('private-driver.' . $this->delivery->driver_id)];
    }

    public function broadcastWith(): array
    {
        $order = $this->delivery->order;
        return [
            'delivery_id'      => $this->delivery->id,
            'order_id'         => $order->id,
            'reference'        => $order->reference,
            'merchant_name'    => $order->merchant->business_name,
            'merchant_lat'     => $order->merchant->lat,
            'merchant_lng'     => $order->merchant->lng,
            'dropoff_lat'      => $order->address_snapshot['lat'] ?? null,
            'dropoff_lng'      => $order->address_snapshot['lng'] ?? null,
            'neighborhood'     => $order->address_snapshot['neighborhood'] ?? null,
            'total'            => (float) $order->total,
            'delivery_fee'     => (float) $this->delivery->fee,
            'payment_method'   => $order->payment_method,
            'offer_expires_at' => $this->delivery->offer_expires_at?->toIso8601String(),
        ];
    }
}
