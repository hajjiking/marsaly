<?php

namespace App\Events;

use App\Models\Driver;

class DriverLocationUpdated extends MarsalyEvent
{
    public function __construct(public Driver $driver, public float $lat, public float $lng, public ?int $orderId = null) {}

    public function broadcastOn(): array
    {
        $channels = [new Channel('private-admin')];
        if ($this->orderId) {
            $channels[] = new Channel("private-order.{$this->orderId}");
        }
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'driver_id'  => $this->driver->id,
            'lat'        => $this->lat,
            'lng'        => $this->lng,
            'order_id'   => $this->orderId,
            'recorded_at'=> now()->toIso8601String(),
        ];
    }
}
