<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use App\Support\OrderStatusMachine;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private readonly NotificationService $notifications) {}

    /**
     * The single entry point for every status change. Validates the transition
     * server-side, stamps timestamps, logs history, fires events (real-time +
     * push via listeners) — see docs/ORDER_FLOW.md.
     */
    public function changeStatus(Order $order, string $to, User $actor, ?string $note = null): Order
    {
        $from = $order->status;

        DB::transaction(function () use ($order, $to, $actor, $note, $from) {
            OrderStatusMachine::assertTransition($from, $to, $actor->role->name);

            $order->status = $to;
            $patch = [];
            if ($to === 'accepted')      $patch['accepted_at'] = now();
            if ($to === 'ready')         $patch['ready_at'] = now();
            if ($to === 'picked_up')     $patch['picked_up_at'] = now();
            if ($to === 'delivered')     $patch['delivered_at'] = now();
            if ($to === 'cancelled')     $patch['cancelled_at'] = now();
            if ($note && in_array($to, ['rejected', 'cancelled'], true)) {
                $patch[$to === 'rejected' ? 'reject_reason' : 'cancel_reason'] = $note;
            }
            $order->fill($patch)->save();

            $order->statusHistory()->create([
                'from_status' => $from,
                'to_status'   => $to,
                'actor_id'    => $actor->id,
                'actor_role'  => $actor->role->name,
                'note'        => $note,
            ]);
        });

        event(new OrderStatusChanged($order->fresh(['merchant.user', 'driver.user', 'client', 'items']), $from, $to));

        return $order;
    }
}
