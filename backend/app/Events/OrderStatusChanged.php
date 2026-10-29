<?php

namespace App\Events;

use App\Models\Order;

class OrderStatusChanged extends MarsalyEvent
{
    public function __construct(public Order $order, public string $from, public string $to) {}

    /** @return array<int, Channel> */
    public function broadcastOn(): array
    {
        return [
            new Channel("private-order.{$this->order->id}"),
            new Channel('private-merchant.' . $this->order->merchant_id),
            new Channel('private-admin'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'   => $this->order->id,
            'reference'  => $this->order->reference,
            'status'     => $this->order->status,
            'from'       => $this->from,
            'to'         => $this->to,
            'driver_id'  => $this->order->driver_id,
            'updated_at' => $this->order->updated_at?->toIso8601String(),
        ];
    }
}
