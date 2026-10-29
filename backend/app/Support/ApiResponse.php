<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Consistent JSON error/success envelope for every API response:
 * { "success": bool, "data": {...}|null, "message": "...", "errors": {...}|null }
 */
class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data'    => null,
            'message' => $message,
            'errors'  => $errors ?: null,
        ], $status);
    }
}
