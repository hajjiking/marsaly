<?php

namespace App\Http\Controllers\V1;

use App\Events\NewAdminEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PromoCode;
use App\Services\DriverAssignmentService;
use App\Services\OrderService;
use App\Support\ApiResponse;
use App\Support\FeeCalculator;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly DriverAssignmentService $assignment,
    ) {}

    /**
     * POST /api/v1/orders — checkout from the server-side cart.
     * Prices are recomputed on the server; the client only chooses address & payment method.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'address_id'     => ['required', Rule::exists('addresses', 'id')->where('user_id', $request->user()->id)],
            'payment_method' => ['required', Rule::in(config('marsaly.payments.methods'))],
            'client_note'    => ['nullable', 'string', 'max:280'],
        ]);

        $cart = Cart::where('user_id', $request->user()->id)->first();
        if (!$cart || empty($cart->items)) {
            return ApiResponse::error(__('api.cart_empty'), 422);
        }

        $merchant = Merchant::find($cart->merchant_id);
        $address = Address::find($data['address_id']);

        if (!$merchant || $merchant->status !== 'approved') {
            return ApiResponse::error(__('api.merchant_unavailable'), 422);
        }
        if (!$merchant->isOpenNow()) {
            return ApiResponse::error(__('api.merchant_closed'), 422);
        }

        $subtotal = round(collect($cart->items)->sum(
            fn ($i) => ((float) $i['unit_price'] + (float) $i['options_price']) * (int) $i['quantity']
        ), 2);

        if ($subtotal < FeeCalculator::minimumOrder($merchant)) {
            return ApiResponse::error(__('api.below_minimum', ['amount' => FeeCalculator::minimumOrder($merchant)]), 422);
        }

        $promo = $cart->promo_code ? PromoCode::where('code', $cart->promo_code)->first() : null;
        $quote = FeeCalculator::quote($merchant, $subtotal, (float) $address->lat, (float) $address->lng, $promo, $request->user());

        $order = DB::transaction(function () use ($request, $cart, $merchant, $address, $data, $quote, $promo) {
            $order = Order::create([
                'reference'      => $this->generateReference(),
                'user_id'        => $request->user()->id,
                'merchant_id'    => $merchant->id,
                'address_id'     => $address->id,
                'address_snapshot' => [
                    'label' => $address->label, 'contact_name' => $address->contact_name,
                    'contact_phone' => Phone::normalize($address->contact_phone),
                    'neighborhood' => $address->neighborhood, 'address_line' => $address->address_line,
                    'landmark' => $address->landmark, 'lat' => $address->lat, 'lng' => $address->lng,
                ],
                'status'          => 'pending',
                'payment_method'  => $data['payment_method'],
                'payment_status'  => 'unpaid',
                'subtotal'        => $quote['subtotal'],
                'delivery_fee'    => $quote['delivery_fee'],
                'discount_total'  => $quote['discount_total'],
                'total'           => $quote['total'],
                'commission_percent' => $quote['commission']['percent'],
                'commission_amount'  => $quote['commission']['amount'],
                'driver_fee'      => FeeCalculator::driverFee($quote['distance_km']),
                'distance_km'     => $quote['distance_km'],
                'estimated_minutes' => round(($merchant->prep_time_minutes ?? 20) + $quote['distance_km'] * 3, 1),
                'prep_time_minutes' => $merchant->prep_time_minutes,
                'promo_code'      => $promo?->code,
                'client_note'     => $data['client_note'] ?? null,
                'delivery_otp'    => (string) random_int(1000, 9999),
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'         => $order->id,
                    'product_id'       => $item['product_id'],
                    'product_name'     => $item['product_name'],
                    'unit_price'       => $item['unit_price'],
                    'options_price'    => $item['options_price'],
                    'quantity'         => $item['quantity'],
                    'selected_options' => $item['selected_options'] ?? null,
                    'note'             => $item['note'] ?? null,
                ]);
            }

            $promo?->increment('used_count');
            $cart->update(['items' => [], 'merchant_id' => null, 'promo_code' => null]);

            $order->statusHistory()->create([
                'from_status' => null,
                'to_status'   => 'pending',
                'actor_id'    => $request->user()->id,
                'actor_role'  => $request->user()->role->name,
            ]);

            return $order;
        });

        event(new NewAdminEvent('new_order', [
            'order_id'    => $order->id,
            'reference'   => $order->reference,
            'merchant_id' => $merchant->id,
            'total'       => $order->total,
        ]));

        // notify merchant owner
        if ($merchant->user) {
            app(\App\Services\NotificationService::class)->notify(
                $merchant->user, 'order_new', 'notifications.new_order',
                ['order_id' => $order->id, 'reference' => $order->reference]
            );
        }

        return ApiResponse::success(
            new OrderResource($order->load(['items', 'merchant'])),
            __('api.order_placed'),
            201
        );
    }

    /** GET /api/v1/orders — role-scoped list with status filter + pagination. */
    public function index(Request $request)
    {
        $user = $request->user();
        $q = Order::query()->with(['items', 'merchant:id,business_name', 'driver.user:id,name,phone']);

        match ($user->role?->name) {
            'client'   => $q->where('user_id', $user->id),
            'merchant' => $q->where('merchant_id', $user->merchant?->id ?? 0),
            'driver'   => $q->where('driver_id', $user->driver?->id ?? 0),
            default    => $q, // admin sees all
        };

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $q->whereIn('status', config('marsaly.orders.active_statuses'));
            } else {
                $q->where('status', $status);
            }
        }

        $orders = $q->latest()->paginate(min(50, $request->integer('per_page', 20)));

        return response()->json([
            'success' => true,
            'data'    => OrderResource::collection($orders->items()),
            'meta'    => [
                'page' => $orders->currentPage(), 'per_page' => $orders->perPage(),
                'total' => $orders->total(), 'last_page' => $orders->lastPage(),
            ],
        ]);
    }

    /** GET /api/v1/orders/{order} */
    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        return ApiResponse::success(
            new OrderResource($order->load(['items', 'statusHistory.actor', 'merchant.category', 'driver.user', 'delivery']))
        );
    }

    /** POST /api/v1/orders/{order}/cancel — allowed while not terminal (see state machine). */
    public function cancel(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $this->orders->changeStatus($order, 'cancelled', $request->user(), $data['reason'] ?? null);
        } catch (\App\Exceptions\InvalidOrderTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 409);
        }

        return ApiResponse::success(new OrderResource($order->fresh()), __('api.order_cancelled'));
    }

    /** PATCH /api/v1/orders/{order} — client can edit note/status only while pending. */
    public function update(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        if ($order->status !== 'pending') {
            return ApiResponse::error(__('api.order_locked'), 409);
        }
        $data = $request->validate([
            'client_note' => ['sometimes', 'nullable', 'string', 'max:280'],
            'address_id'  => ['sometimes', Rule::exists('addresses', 'id')->where('user_id', $request->user()->id)],
        ]);
        if (isset($data['address_id'])) {
            $address = Address::findOrFail($data['address_id']);
            $order->address_snapshot = [
                'label' => $address->label, 'contact_name' => $address->contact_name,
                'contact_phone' => Phone::normalize($address->contact_phone),
                'neighborhood' => $address->neighborhood, 'address_line' => $address->address_line,
                'landmark' => $address->landmark, 'lat' => $address->lat, 'lng' => $address->lng,
            ];
            $order->address_id = $address->id;
        }
        if (array_key_exists('client_note', $data)) {
            $order->client_note = $data['client_note'];
        }
        $order->save();
        return ApiResponse::success(new OrderResource($order->fresh()), __('api.updated'));
    }

    /** Reusable authorization: owner, its merchant/driver, or admin. */
    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();
        $ok = $user->hasRole('admin')
            || $order->user_id === $user->id
            || ($user->merchant && $order->merchant_id === $user->merchant->id)
            || ($user->driver && $order->driver_id === $user->driver->id);
        abort_unless($ok, 403);
    }

    private function generateReference(): string
    {
        do {
            $ref = 'MS' . now()->format('ymd') . strtoupper(bin2hex(random_bytes(3)));
        } while (Order::where('reference', $ref)->exists());
        return $ref;
    }
}
