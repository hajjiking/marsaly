<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\Review;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

/**
 * Client rates merchant and/or driver after delivery. Updates rating averages.
 */
class ReviewController extends Controller
{
    public function store(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->status !== 'delivered') {
            return ApiResponse::error(__('api.order_locked'), 409);
        }

        $data = $request->validate([
            'merchant_rating' => ['nullable', 'integer', 'between:1,5'],
            'driver_rating'   => ['nullable', 'integer', 'between:1,5'],
            'comment'         => ['nullable', 'string', 'max:500'],
        ]);

        if (!$data['merchant_rating'] && !$data['driver_rating']) {
            return ApiResponse::error(__('validation.required', ['attribute' => 'rating']), 422);
        }

        $created = [];

        if (!empty($data['merchant_rating'])) {
            $review = Review::firstOrCreate(
                ['order_id' => $order->id, 'target' => 'merchant'],
                [
                    'user_id' => $request->user()->id,
                    'merchant_id' => $order->merchant_id,
                    'rating' => $data['merchant_rating'],
                    'comment' => $data['comment'] ?? null,
                ],
            );
            $this->recalcMerchantRating($order->merchant_id);
            $order->update(['rating_merchant' => $data['merchant_rating']]);
            $created[] = $review;
        }

        if (!empty($data['driver_rating']) && $order->driver_id) {
            $review = Review::firstOrCreate(
                ['order_id' => $order->id, 'target' => 'driver'],
                [
                    'user_id' => $request->user()->id,
                    'driver_id' => $order->driver_id,
                    'rating' => $data['driver_rating'],
                    'comment' => $data['comment'] ?? null,
                ],
            );
            $this->recalcDriverRating($order->driver_id);
            $order->update(['rating_driver' => $data['driver_rating']]);
            // Optional tip for the driver (kept on the delivery record).
            if ($request->filled('driver_tip')) {
                $request->validate(['driver_tip' => 'numeric|min:0|max:500']);
                $order->delivery?->update(['tip' => (float) $request->input('driver_tip')]);
            }
            $created[] = $review;
        }

        return ApiResponse::success($created, __('api.created'));
    }

    private function recalcMerchantRating(int $merchantId): void
    {
        $avg = Review::where('merchant_id', $merchantId)->where('target', 'merchant')->avg('rating');
        Merchant::where('id', $merchantId)->update(['rating_avg' => round((float) $avg, 2)]);
    }

    private function recalcDriverRating(int $driverId): void
    {
        $avg = Review::where('driver_id', $driverId)->where('target', 'driver')->avg('rating');
        Driver::where('id', $driverId)->update(['rating_avg' => round((float) $avg, 2)]);
    }
}
