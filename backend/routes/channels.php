<?php

use App\Models\Driver;
use App\Models\Merchant;
use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

/*
| Marsaly private broadcast channels (Laravel Reverb).
| Clients join their own order channel; merchants/drivers only their own;
| the admin channel requires the admin role.
*/

Broadcast::channel('private-order.{id}', function ($user, $id) {
    $order = Order::find($id);
    if (!$order) return false;
    if ($user->hasRole('admin')) return true;
    if ($user->id === $order->user_id) return true;
    if ($user->hasRole('merchant')) return (int) $user->merchant?->id === (int) $order->merchant_id;
    if ($user->hasRole('driver'))   return (int) $user->driver?->id === (int) $order->driver_id;
    return false;
});

Broadcast::channel('private-merchant.{id}', fn ($user, $id) =>
    $user->hasRole('admin') || ((int) $user->merchant?->id === (int) $id)
);

Broadcast::channel('private-driver.{id}', fn ($user, $id) =>
    $user->hasRole('admin') || ((int) $user->driver?->id === (int) $id)
);

Broadcast::channel('private-admin', fn ($user) => $user->hasRole('admin'));
