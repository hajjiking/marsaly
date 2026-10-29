<?php

namespace App\Events;

/** Generic dashboard event: new order, new merchant/driver registration, ticket. */
class NewAdminEvent extends MarsalyEvent
{
    public function __construct(public string $kind, public array $payload = []) {}

    public function broadcastOn(): array
    {
        return [new Channel('private-admin')];
    }

    public function broadcastWith(): array
    {
        return ['kind' => $this->kind] + $this->payload;
    }
}
