<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * Abstraction over payment methods so card gateways (e.g. CMI Maroc) can be
 * plugged in later without touching order code. Cash-on-delivery is the
 * default implementation.
 */
interface PaymentGateway
{
    public function name(): string;                       // 'cod', 'cmi', ...

    /** Whether this method requires an online capture step at checkout. */
    public function requiresOnlineCapture(): bool;

    /** Called when an order using this method is created. */
    public function initiate(Order $order): array;        // data returned to client (redirect url etc.)

    /** Confirm/collect the payment (COD: no-op until delivery). */
    public function settle(Order $order): bool;

    /** Refund (used by admin refund/adjust flow). */
    public function refund(Order $order, float $amount): bool;
}
