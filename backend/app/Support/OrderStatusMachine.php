<?php

namespace App\Support;

/**
 * Server-side validation of the order lifecycle:
 * pending -> accepted -> preparing -> ready -> driver_assigned
 *         -> picked_up -> on_the_way -> delivered  (+ cancelled/rejected/failed)
 */
class OrderStatusMachine
{
    /** Extra guard: which roles may trigger each transition (admin can do all). */
    private const ROLE_GUARDS = [
        'accepted'        => ['merchant', 'admin'],
        'rejected'        => ['merchant', 'admin'],
        'preparing'       => ['merchant', 'admin'],
        'ready'           => ['merchant', 'admin'],
        'driver_assigned' => ['driver', 'admin'],
        'picked_up'       => ['driver', 'admin'],
        'on_the_way'      => ['driver', 'admin'],
        'delivered'       => ['driver', 'admin'],
        'cancelled'       => ['client', 'merchant', 'driver', 'admin'],
        'failed'          => ['driver', 'admin'],
    ];

    public static function canTransition(string $from, string $to): bool
    {
        return in_array($to, config("marsaly.orders.transitions.$from", []), true);
    }

    public static function roleCanTransition(string $role, string $to): bool
    {
        if ($role === 'admin') return true;
        return in_array($role, self::ROLE_GUARDS[$to] ?? [], true);
    }

    public static function assertTransition(string $from, string $to, string $role): void
    {
        if (!self::canTransition($from, $to)) {
            throw new \App\Exceptions\InvalidOrderTransitionException($from, $to);
        }
        if (!self::roleCanTransition($role, $to)) {
            abort(403, __('api.transition_forbidden'));
        }
    }
}
