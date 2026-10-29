<?php

namespace App\Support;

/**
 * Distance helpers (haversine). No external routing API needed — good enough
 * for a small city like El Marsa and keeps us free of paid API keys.
 */
class Geo
{
    public const EARTH_RADIUS_KM = 6371.0;

    public static function distanceKm(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): float
    {
        if (in_array(null, [$lat1, $lng1, $lat2, $lng2], true)) {
            return 0.0;
        }
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round(self::EARTH_RADIUS_KM * 2 * atan2(sqrt($a), sqrt(1 - $a)), 3);
    }

    /** Road factor: straight-line distances underestimate real routes in the city. */
    public static function roadDistanceKm(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): float
    {
        return round(self::distanceKm($lat1, $lng1, $lat2, $lng2) * 1.3, 2);
    }
}
