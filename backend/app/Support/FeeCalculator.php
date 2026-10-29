<?php

namespace App\Support;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;

/**
 * Central pricing logic. See docs/FEES.md.
 * delivery_fee   = zone.base_fee + per_km_fee * distance_km (merchant -> address)
 * commission     = commission_percent (merchant > category > global default) * subtotal
 * driver_fee     = driver_base_fee + driver_per_km_fee * distance
 * minimum order  = max(zone.minimum_order, config fallback)
 */
class FeeCalculator
{
    public static function distanceKm(Merchant $merchant, float $lat, float $lng): float
    {
        return Geo::roadDistanceKm($merchant->lat, $merchant->lng, $lat, $lng);
    }

    public static function deliveryFee(Merchant $merchant, float $distanceKm): float
    {
        $zone = $merchant->zone;
        $base = (float) ($zone?->base_fee ?? config('marsaly.fees.base_delivery_fee'));
        $perKm = (float) ($zone?->per_km_fee ?? config('marsaly.fees.per_km_fee'));
        return round($base + $perKm * $distanceKm, 2);
    }

    public static function minimumOrder(Merchant $merchant): float
    {
        return (float) ($merchant->zone?->minimum_order
            ?: config('marsaly.fees.minimum_order'));
    }

    public static function driverFee(float $distanceKm): float
    {
        return round(
            config('marsaly.fees.driver_base_fee') + config('marsaly.fees.driver_per_km_fee') * $distanceKm,
            2,
        );
    }

    public static function commission(Merchant $merchant, float $subtotal): array
    {
        $percent = $merchant->effectiveCommissionPercent();
        return ['percent' => $percent, 'amount' => round($subtotal * $percent / 100, 2)];
    }

    public static function discount(?PromoCode $promo, float $subtotal, float $deliveryFee): float
    {
        if (!$promo) return 0.0;
        $discount = match ($promo->type) {
            'percent'        => min($subtotal * (float) $promo->value / 100, (float) ($promo->max_discount ?? INF)),
            'fixed'          => min((float) $promo->value, $subtotal),
            'free_delivery'  => $deliveryFee,
            default          => 0.0,
        };
        return round(max(0.0, $discount), 2);
    }

    /** Full quote used at checkout & preview. */
    public static function quote(Merchant $merchant, float $subtotal, float $lat, float $lng, ?PromoCode $promo = null, ?User $user = null): array
    {
        $distance = self::distanceKm($merchant, $lat, $lng);
        $deliveryFee = self::deliveryFee($merchant, $distance);
        $discount = $promo && $promo->isValidFor($user?->id, $subtotal)
            ? self::discount($promo, $subtotal, $deliveryFee)
            : 0.0;
        $commission = self::commission($merchant, $subtotal);
        $total = round(max(0, $subtotal + $deliveryFee - $discount), 2);

        return [
            'subtotal'          => round($subtotal, 2),
            'distance_km'       => $distance,
            'delivery_fee'      => $deliveryFee,
            'discount_total'    => $discount,
            'total'             => $total,
            'minimum_order'     => self::minimumOrder($merchant),
            'meets_minimum'     => $subtotal >= self::minimumOrder($merchant),
            'commission_percent'=> $commission['percent'],
            'commission_amount' => $commission['amount'],
            'driver_fee'        => self::driverFee($distance),
        ];
    }
}
