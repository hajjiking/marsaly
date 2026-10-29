<?php

namespace App\Support;

class Phone
{
    /** Normalize Moroccan numbers to E.164 (+212...). Accepts 06..., 6..., +212..., 212... */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) return null;
        $digits = preg_replace('/[^\d+]/', '', $phone);
        if (str_starts_with($digits, '+212')) {
            return $digits;
        }
        if (str_starts_with($digits, '212')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+212' . substr($digits, 1);
        }
        if (strlen($digits) === 9) {
            return '+212' . $digits;
        }
        return $digits;
    }

    public static function isValidMoroccan(?string $phone): bool
    {
        $n = self::normalize($phone);
        return (bool) preg_match('/^\+212[5-7]\d{8}$/', (string) $n);
    }
}
