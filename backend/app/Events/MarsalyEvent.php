<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Base for all Marsaly real-time events broadcast on Laravel Reverb.
 * Channels: private-order.{id}, private-merchant.{id}, private-driver.{id},
 *           private-admin
 */
abstract class MarsalyEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function broadcastAs(): string
    {
        $short = class_basename(static::class);
        return 'marsaly.' . strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
    }
}
