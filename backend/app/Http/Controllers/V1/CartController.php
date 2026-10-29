<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\PromoCode;
use App\Support\ApiResponse;
use App\Support\FeeCalculator;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cartFor(Request $request): Cart
    {
        return Cart::firstOrCreate(['user_id' => $request->user()->id]);
    }

    /** GET /api/v1/cart */
    public function show(Request $request)
    {
        $cart = $this->cartFor($request)->load('merchant');
        return ApiResponse::success($this->summary($cart, $request));
    }

    /** POST /api/v1/cart/items  {merchant_id, product_id, quantity, selected_options:[{option_id,value_id}], note} */
    public function addItem(Request $request)
    {
        $data = $request->validate([
            'merchant_id'    => ['required', 'exists:merchants,id'],
            'product_id'     => ['required', 'exists:products,id'],
            'quantity'       => ['sometimes', 'integer', 'min:1', 'max:50'],
            'note'           => ['nullable', 'string', 'max:280'],
            'selected_options' => ['nullable', 'array'],
            'selected_options.*.option_id' => ['required_with:selected_options', 'exists:product_options,id'],
            'selected_options.*.value_id'  => ['required_with:selected_options', 'exists:product_option_values,id'],
        ]);

        $cart = $this->cartFor($request);
        if ($cart->merchant_id && $cart->merchant_id !== $data['merchant_id']) {
            return ApiResponse::error(__('api.cart_other_merchant'), 409);
        }

        $product = Product::with('options.values')->findOrFail($data['product_id']);
        if ($product->merchant_id !== $data['merchant_id'] || !$product->in_stock) {
            return ApiResponse::error(__('api.product_unavailable'), 422);
        }

        // validate + price options server-side (never trust client prices)
        $optionsPrice = 0.0;
        $selected = [];
        foreach ($data['selected_options'] ?? [] as $sel) {
            $option = $product->options->firstWhere('id', $sel['option_id']);
            $value  = $option?->values->firstWhere('id', $sel['value_id']);
            if (!$option || !$value) {
                return ApiResponse::error(__('api.invalid_option'), 422);
            }
            $optionsPrice += (float) $value->price_delta;
            $selected[] = [
                'option_id' => $option->id, 'option_name' => $option->name,
                'value_id' => $value->id, 'value_name' => $value->name,
                'price_delta' => (float) $value->price_delta,
            ];
        }

        $qty = (int) ($data['quantity'] ?? 1);
        $line = [
            'uid' => bin2hex(random_bytes(6)),
            'product_id'    => $product->id,
            'product_name'  => $product->name,
            'unit_price'    => (float) $product->price,
            'options_price' => round($optionsPrice, 2),
            'quantity'      => $qty,
            'selected_options' => $selected,
            'note'          => $data['note'] ?? null,
        ];

        $items = $cart->items ?? [];
        $items[] = $line;
        $cart->update(['merchant_id' => $data['merchant_id'], 'items' => array_values($items)]);

        return ApiResponse::success($this->summary($cart->fresh(), $request), __('api.added'), 201);
    }

    /** PATCH /api/v1/cart/items/{uid} {quantity} */
    public function updateItem(Request $request, string $uid)
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:50']]);
        $cart = $this->cartFor($request);
        $items = collect($cart->items ?? [])
            ->map(fn ($i) => $i['uid'] === $uid ? array_merge($i, ['quantity' => $data['quantity']]) : $i)
            ->filter(fn ($i) => $i['quantity'] > 0)
            ->values()->all();
        $cart->update(['items' => $items, 'merchant_id' => $items ? $cart->merchant_id : null]);
        return ApiResponse::success($this->summary($cart->fresh(), $request), __('api.updated'));
    }

    /** DELETE /api/v1/cart/items/{uid} */
    public function removeItem(Request $request, string $uid)
    {
        $cart = $this->cartFor($request);
        $items = collect($cart->items ?? [])->reject(fn ($i) => $i['uid'] === $uid)->values()->all();
        $cart->update(['items' => $items, 'merchant_id' => $items ? $cart->merchant_id : null]);
        return ApiResponse::success($this->summary($cart->fresh(), $request), __('api.removed'));
    }

    /** DELETE /api/v1/cart */
    public function clear(Request $request)
    {
        $cart = $this->cartFor($request);
        $cart->update(['items' => [], 'merchant_id' => null, 'promo_code' => null]);
        return ApiResponse::success($this->summary($cart->fresh(), $request), __('api.cart_cleared'));
    }

    /** POST /api/v1/cart/promo {code} */
    public function applyPromo(Request $request)
    {
        $code = strtoupper(trim((string) $request->input('code')));
        $cart = $this->cartFor($request);
        $subtotal = $this->subtotal($cart);
        $promo = PromoCode::where('code', $code)->first();
        if (!$promo || !$promo->isValidFor($request->user()->id, $subtotal)) {
            return ApiResponse::error(__('api.promo_invalid'), 422);
        }
        $cart->update(['promo_code' => $promo->code]);
        return ApiResponse::success($this->summary($cart->fresh(), $request), __('api.promo_applied'));
    }

    /** POST /api/v1/cart/quote {lat, lng} — delivery fee preview for an address pin */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);
        $cart = $this->cartFor($request);
        return ApiResponse::success($this->summary($cart, $request, (float) $data['lat'], (float) $data['lng']));
    }

    private function subtotal(Cart $cart): float
    {
        return round(collect($cart->items ?? [])->sum(
            fn ($i) => ((float) $i['unit_price'] + (float) $i['options_price']) * (int) $i['quantity']
        ), 2);
    }

    private function summary(Cart $cart, Request $request, ?float $lat = null, ?float $lng = null): array
    {
        $subtotal = $this->subtotal($cart);
        $promo = $cart->promo_code ? PromoCode::where('code', $cart->promo_code)->first() : null;
        $out = [
            'merchant_id' => $cart->merchant_id,
            'items'       => $cart->items,
            'items_count' => collect($cart->items ?? [])->sum(fn ($i) => (int) $i['quantity']),
            'subtotal'    => $subtotal,
            'promo_code'  => $cart->promo_code,
            'discount_total' => 0.0,
            'delivery_fee'   => null,
            'total'          => $subtotal,
            'minimum_order'  => null,
        ];
        if ($cart->merchant) {
            $m = $cart->merchant;
            $out['minimum_order'] = FeeCalculator::minimumOrder($m);
            if ($lat !== null && $lng !== null) {
                $dist = FeeCalculator::distanceKm($m, $lat, $lng);
                $fee = FeeCalculator::deliveryFee($m, $dist);
                $disc = $promo && $promo->isValidFor($request->user()->id, $subtotal)
                    ? FeeCalculator::discount($promo, $subtotal, $fee) : 0.0;
                $out['distance_km'] = $dist;
                $out['delivery_fee'] = $fee;
                $out['discount_total'] = $disc;
                $out['total'] = round(max(0, $subtotal + $fee - $disc), 2);
            }
        }
        return $out;
    }
}
