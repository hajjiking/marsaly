<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Order;
use App\Services\Payment\CodGateway;

class PaymentService
{
    /** Resolve the gateway implementation for an order's payment method. */
    public function gatewayFor(Order|string $orderOrMethod): PaymentGateway
    {
        $method = is_string($orderOrMethod) ? $orderOrMethod : $orderOrMethod->payment_method;

        return match ($method) {
            // Future: 'cmi' => new CmiGateway(...),
            default  => new CodGateway(),
        };
    }

    public function markPaid(Order $order): void
    {
        $order->payment()->updateOrCreate(
            ['order_id' => $order->id],
            ['method' => $order->payment_method, 'amount' => $order->total, 'status' => 'settled'],
        );
        $order->update(['payment_status' => 'paid']);
    }

    public function refund(Order $order, float $amount): void
    {
        $this->gatewayFor($order)->refund($order, $amount);
        $order->update(['payment_status' => 'refunded']);
        $order->payment()->update(['status' => 'refunded']);
    }
}
