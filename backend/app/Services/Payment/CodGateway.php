<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;

class CodGateway implements PaymentGateway
{
    public function name(): string { return 'cod'; }
    public function requiresOnlineCapture(): bool { return false; }

    public function initiate(Order $order): array
    {
        return ['method' => 'cod', 'status' => 'pending_at_delivery'];
    }

    public function settle(Order $order): bool
    {
        // Cash is physically collected by the driver at delivery.
        return true;
    }

    public function refund(Order $order, float $amount): bool
    {
        // Nothing to reverse online for COD; finance records an adjustment.
        return true;
    }
}
