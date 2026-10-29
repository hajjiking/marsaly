<?php

namespace App\Services;

use App\Events\DriverOfferedOrder;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use App\Support\Geo;
use Illuminate\Support\Facades\Log;

/**
 * Auto-assign: when an order becomes `ready`, offer it to the nearest online
 * drivers within the search radius with a countdown. First accept wins; on
 * timeout we re-offer or leave it for manual admin assignment (fallback).
 */
class DriverAssignmentService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function findCandidates(Order $order, int $limit = 3): array
    {
        $merchant = $order->merchant;
        return Driver::query()
            ->where('status', 'approved')
            ->where('availability', 'online')
            ->with('user')
            ->get()
            ->map(function (Driver $d) use ($merchant) {
                $d->distance_to_merchant_km = Geo::distanceKm($d->lat, $d->lng, $merchant->lat, $merchant->lng);
                return $d;
            })
            ->filter(fn (Driver $d) => $d->distance_to_merchant_km <= config('marsaly.assignment.search_radius_km'))
            ->sortBy('distance_to_merchant_km')
            ->take($limit)
            ->all();
    }

    public function offerToNearest(Order $order): void
    {
        $candidates = $this->findCandidates($order);
        if (!$candidates) {
            Log::info("Marsaly: no available driver for order {$order->reference}; awaiting manual assignment.");
            return;
        }

        $timeout = config('marsaly.assignment.offer_timeout_seconds');
        foreach ($candidates as $driver) {
            $delivery = Delivery::create([
                'order_id'          => $order->id,
                'driver_id'         => $driver->id,
                'assignment_type'   => 'auto',
                'state'             => 'offered',
                'offered_at'        => now(),
                'offer_expires_at'  => now()->addSeconds($timeout),
                'fee'               => $order->driver_fee,
            ]);

            event(new DriverOfferedOrder($order, $driver, $timeout));
            $this->notifications->notify(
                $driver->user,
                'driver_offer',
                'notifications.new_delivery_offer',
                ['order_id' => $order->id, 'delivery_id' => $delivery->id, 'expires_in' => $timeout],
            );
        }
    }

    public function acceptOffer(Delivery $delivery, Driver $driver): Order
    {
        $order = $delivery->order;
        // Lock-style guard: only one driver can take it.
        if ($order->fresh()->driver_id !== null) {
            abort(409, __('api.order_already_taken'));
        }
        $delivery->update(['state' => 'assigned', 'accepted_at' => now()]);
        $driver->update(['availability' => 'busy']);

        // Mark other offers declined/expired
        Delivery::where('order_id', $order->id)->where('id', '!=', $delivery->id)
            ->whereIn('state', ['offered'])
            ->update(['state' => 'declined', 'decline_reason' => 'taken_by_other_driver']);

        app(OrderService::class)->changeStatus(
            $order, 'driver_assigned', $driver->user,
            __('notifications.auto_assigned'),
        );

        return $order->fresh();
    }
}
