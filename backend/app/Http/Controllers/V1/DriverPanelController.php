<?php

namespace App\Http\Controllers\V1;

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\CashSettlement;
use App\Models\Delivery;
use App\Models\Driver;
use App\Models\Order;
use App\Services\DriverAssignmentService;
use App\Services\OrderService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Driver app endpoints: availability, GPS pings, offers, delivery flow and cash.
 */
class DriverPanelController extends Controller
{
    private function driver(Request $request): Driver
    {
        $driver = Driver::with('user')->where('user_id', $request->user()->id)->first();
        if (!$driver) {
            return abort(404, __('api.not_found'));
        }
        if ($driver->status !== 'approved') {
            return abort(403, __('api.driver_not_approved'));
        }
        return $driver;
    }

    /** GET /me — profile + live stats. */
    public function me(Request $request)
    {
        $driver = $this->driver($request);
        $today = now()->startOfDay();

        $deliveredToday = Delivery::where('driver_id', $driver->id)
            ->where('state', 'completed')
            ->whereDate('delivered_at', $today)->count();

        $earningsToday = (float) Delivery::where('driver_id', $driver->id)
            ->where('state', 'completed')->whereDate('delivered_at', $today)
            ->sum('fee');

        return ApiResponse::success([
            'driver' => [
                'id' => $driver->id,
                'status' => $driver->status,
                'availability' => $driver->availability,
                'vehicle_type' => $driver->vehicle_type,
                'vehicle_model' => $driver->vehicle_model,
                'plate_number' => $driver->plate_number,
                'rating_avg' => $driver->rating_avg,
                'deliveries_count' => $driver->deliveries_count,
                'lat' => $driver->lat, 'lng' => $driver->lng,
            ],
            'stats' => [
                'delivered_today' => $deliveredToday,
                'earnings_today' => round($earningsToday, 2),
                'cash_collected_total' => $driver->cash_collected_total,
                'cash_owed_to_platform' => $driver->cash_owed_to_platform,
            ],
        ]);
    }

    /** POST /availability {online: bool} */
    public function setAvailability(Request $request)
    {
        $data = $request->validate(['online' => 'required|boolean']);
        $driver = $this->driver($request);

        // A busy driver can only go online after settling/cancelling active work.
        $newAvail = $data['online'] ? 'online' : 'offline';
        if ($driver->availability === 'busy' && !$data['online']) {
            $active = Delivery::where('driver_id', $driver->id)
                ->whereIn('state', ['offered', 'assigned'])->count();
            if ($active > 0) {
                return ApiResponse::error(__('api.order_locked'), 409);
            }
        }
        $driver->update(['availability' => $newAvail]);

        return ApiResponse::success(['availability' => $driver->availability], __('api.updated'));
    }

    /** POST /location {lat, lng, accuracy?, order_id?} — frequent GPS pings. */
    public function location(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between=-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'order_id' => 'nullable|integer',
        ]);
        $driver = $this->driver($request);

        DB::transaction(function () use ($driver, $data) {
            $driver->update(['lat' => $data['lat'], 'lng' => $data['lng'], 'last_location_at' => now()]);
            $driver->locations()->create([
                'driver_id' => $driver->id,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'accuracy' => $data['accuracy'] ?? null,
                'recorded_at' => now(),
            ]);
        });

        // Broadcast to whoever tracks this driver / its active order.
        event(new DriverLocationUpdated(
            $driver, (float) $data['lat'], (float) $data['lng'],
            isset($data['order_id']) ? (int) $data['order_id'] : null,
        ));

        return ApiResponse::success(null);
    }

    /** GET /offers — pending offers with countdown info. */
    public function offers(Request $request)
    {
        $driver = $this->driver($request);
        $offers = Delivery::with(['order.merchant', 'order.items'])
            ->where('driver_id', $driver->id)
            ->where('state', 'offered')
            ->where('offer_expires_at', '>', now())
            ->latest()
            ->get()
            ->map(fn (Delivery $d) => $this->offerPayload($d));

        return ApiResponse::success($offers);
    }

    /** POST /deliveries/{delivery}/accept */
    public function accept(Delivery $delivery, Request $request, DriverAssignmentService $assign)
    {
        $driver = $this->driver($request);
        $this->authorizeOffer($delivery, $driver);

        if ($delivery->state !== 'offered') {
            return ApiResponse::error(__('api.offer_expired'), 409);
        }
        if ($delivery->offer_expires_at && $delivery->offer_expires_at->isPast()) {
            $delivery->update(['state' => 'declined', 'decline_reason' => 'expired']);
            return ApiResponse::error(__('api.offer_expired'), 409);
        }

        $order = $assign->acceptOffer($delivery, $driver);

        return ApiResponse::success(['order_id' => $order->id], __('api.ok'));
    }

    /** POST /deliveries/{delivery}/decline {reason?} */
    public function decline(Delivery $delivery, Request $request)
    {
        $driver = $this->driver($request);
        $this->authorizeOffer($delivery, $driver);

        $data = $request->validate(['reason' => 'nullable|string|max:255']);
        if (!in_array($delivery->state, ['offered', 'assigned'], true)) {
            return ApiResponse::error(__('api.offer_expired'), 409);
        }
        $delivery->update(['state' => 'declined', 'decline_reason' => $data['reason'] ?? 'driver_declined']);

        // Free the driver if they had already accepted then bailed before pickup.
        if ($driver->availability === 'busy') {
            $remaining = Delivery::where('driver_id', $driver->id)
                ->whereIn('state', ['assigned'])->count();
            if ($remaining === 0) $driver->update(['availability' => 'online']);
        }

        return ApiResponse::success(null, __('api.ok'));
    }

    /** GET /active — current assigned deliveries (the driver's task list). */
    public function active(Request $request)
    {
        $driver = $this->driver($request);
        $rows = Delivery::with(['order.merchant', 'order.items', 'order.client', 'order.statusHistory'])
            ->where('driver_id', $driver->id)
            ->whereIn('state', ['assigned'])
            ->latest()
            ->get()
            ->map(fn (Delivery $d) => [
                'delivery' => [
                    'id' => $d->id, 'state' => $d->state, 'fee' => $d->fee,
                    'accepted_at' => $d->accepted_at?->toIso8601String(),
                    'arrived_merchant_at' => $d->arrived_merchant_at?->toIso8601String(),
                    'picked_up_at' => $d->picked_up_at?->toIso8601String(),
                ],
                'order' => \App\Http\Resources\OrderResource::make($d->order)->resolve(),
            ]);

        return ApiResponse::success($rows);
    }

    /** GET /history */
    public function history(Request $request)
    {
        $driver = $this->driver($request);
        $rows = Delivery::with('order.merchant')
            ->where('driver_id', $driver->id)
            ->whereIn('state', ['completed', 'cancelled', 'declined'])
            ->latest('delivered_at')
            ->paginate(min((int) $request->input('per_page', 20), 50));

        $rows->getCollection()->transform(fn (Delivery $d) => [
            'delivery_id' => $d->id,
            'state' => $d->state,
            'order_id' => $d->order_id,
            'reference' => $d->order?->reference,
            'merchant' => $d->order?->merchant?->name,
            'fee' => $d->fee,
            'tip' => $d->tip,
            'delivered_at' => $d->delivered_at?->toIso8601String(),
        ]);

        return ApiResponse::success($rows);
    }

    /**
     * POST /deliveries/{delivery}/action {action: arrived_at_merchant|picked_up|delivered, otp?, photo?}
     * Drives the order state machine: picked_up requires nothing special; delivered
     * verifies the client's delivery OTP (proof of delivery).
     */
    public function action(Delivery $delivery, Request $request, OrderService $orders)
    {
        $driver = $this->driver($request);
        $this->authorizeOffer($delivery, $driver);

        $data = $request->validate([
            'action' => 'required|in:arrived_at_merchant,picked_up,on_the_way,delivered',
            'otp' => 'nullable|string|size:4',
            'photo' => 'nullable|image|max:5120',
        ]);

        $order = $delivery->order()->with('client')->firstOrFail();

        switch ($data['action']) {
            case 'arrived_at_merchant':
                if (!$delivery->arrived_merchant_at) {
                    $delivery->update(['arrived_merchant_at' => now()]);
                }
                return ApiResponse::success(['state' => $delivery->state], __('api.ok'));

            case 'picked_up':
                if ($order->status !== 'ready' && $order->status !== 'driver_assigned') {
                    return ApiResponse::error(__('api.invalid_transition', ['from' => $order->status, 'to' => 'picked_up']), 409);
                }
                $orders->changeStatus($order, 'picked_up', $request->user());
                $delivery->update(['picked_up_at' => now()]);
                return ApiResponse::success(['order_status' => $order->fresh()->status], __('api.ok'));

            case 'on_the_way':
                if ($order->status !== 'picked_up') {
                    return ApiResponse::error(__('api.invalid_transition', ['from' => $order->status, 'to' => 'on_the_way']), 409);
                }
                $orders->changeStatus($order, 'on_the_way', $request->user());
                return ApiResponse::success(['order_status' => $order->fresh()->status], __('api.ok'));

            case 'delivered':
                if ($order->status !== 'on_the_way') {
                    return ApiResponse::error(__('api.invalid_transition', ['from' => $order->status, 'to' => 'delivered']), 409);
                }
                // Proof of delivery: client OTP (COD orders always require it).
                if ($order->delivery_otp && $data['otp'] !== $order->delivery_otp) {
                    return ApiResponse::error(__('api.delivery_otp_invalid'), 422);
                }
                $photoPath = null;
                if ($request->hasFile('photo')) {
                    $photoPath = $request->file('photo')->store('delivery_proof', 'public');
                }

                DB::transaction(function () use ($order, $delivery, $request, $photoPath, $driver) {
                    $orders->changeStatus($order, 'delivered', $request->user());
                    $delivery->update([
                        'state' => 'completed',
                        'delivered_at' => now(),
                        'proof_photo_path' => $photoPath,
                    ]);
                    // COD cash tracking: driver owes the merchant's net to the platform.
                    if ($order->payment_method === 'cod') {
                        $collect = (float) $order->total;
                        $driver->increment('cash_collected_total', $collect);
                        $driver->increment('cash_owed_to_platform', max(0, $collect - $order->delivery_fee - $order->driver_fee));
                        CashSettlement::create([
                            'driver_id' => $driver->id,
                            'amount' => max(0, $collect - $order->driver_fee),
                            'status' => 'pending',
                            'note' => "Order {$order->reference}",
                        ]);
                        $order->payment()->update(['status' => 'settled', 'amount_given' => $collect]);
                    }
                    $driver->increment('deliveries_count');
                    $driver->update(['availability' => 'online']);
                });

                return ApiResponse::success(['order_status' => 'delivered'], __('api.ok'));
        }
    }

    /** POST /deliveries/{delivery}/failed {reason} — e.g. client unreachable. */
    public function failed(Delivery $delivery, Request $request, OrderService $orders)
    {
        $driver = $this->driver($request);
        $this->authorizeOffer($delivery, $driver);
        $data = $request->validate(['reason' => 'required|string|max:255']);

        $orders->changeStatus($delivery->order, 'failed', $request->user(), $data['reason']);
        $delivery->update(['state' => 'cancelled', 'decline_reason' => $data['reason']]);
        $driver->update(['availability' => 'online']);

        return ApiResponse::success(null, __('api.ok'));
    }

    /** GET /cash — collected/owed balances + settlement history. */
    public function cash(Request $request)
    {
        $driver = $this->driver($request);
        $settlements = CashSettlement::where('driver_id', $driver->id)
            ->latest()->paginate(20);

        return ApiResponse::success([
            'cash_collected_total' => $driver->cash_collected_total,
            'cash_owed_to_platform' => $driver->cash_owed_to_platform,
            'settlements' => $settlements,
        ]);
    }

    /** GET /earnings?period=today|week|month */
    public function earnings(Request $request)
    {
        $driver = $this->driver($request);
        $since = match ($request->input('period', 'today')) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfDay(),
        };

        $q = Delivery::where('driver_id', $driver->id)
            ->where('state', 'completed')
            ->where('delivered_at', '>=', $since);

        return ApiResponse::success([
            'period' => $request->input('period', 'today'),
            'deliveries' => (clone $q)->count(),
            'fees' => round((float) (clone $q)->sum('fee'), 2),
            'tips' => round((float) (clone $q)->sum('tip'), 2),
        ]);
    }

    private function authorizeOffer(Delivery $delivery, Driver $driver): void
    {
        if ($delivery->driver_id !== $driver->id) {
            abort(403, __('api.not_your_offer'));
        }
    }

    private function offerPayload(Delivery $d): array
    {
        $o = $d->order;
        return [
            'delivery_id' => $d->id,
            'order_id' => $o->id,
            'reference' => $o->reference,
            'fee' => $d->fee,
            'offered_at' => $d->offered_at?->toIso8601String(),
            'expires_at' => $d->offer_expires_at?->toIso8601String(),
            'seconds_left' => $d->offer_expires_at ? max(0, now()->diffInSeconds($d->offer_expires_at, false)) : 0,
            'merchant' => [
                'name' => $o->merchant->name, 'address' => $o->merchant->address,
                'lat' => $o->merchant->lat, 'lng' => $o->merchant->lng,
            ],
            'dropoff' => [
                'label' => $o->address_snapshot['label'] ?? null,
                'landmark' => $o->address_snapshot['landmark'] ?? null,
                'lat' => $o->address_snapshot['lat'] ?? null,
                'lng' => $o->address_snapshot['lng'] ?? null,
                'phone' => $o->client?->phone,
            ],
            'items_count' => $o->items->sum('quantity'),
            'payment_method' => $o->payment_method,
            'total' => $o->total,
        ];
    }
}
