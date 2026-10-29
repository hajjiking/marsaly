<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'address_snapshot' => 'array',
        'subtotal' => 'float', 'delivery_fee' => 'float', 'discount_total' => 'float',
        'total' => 'float', 'commission_percent' => 'float', 'commission_amount' => 'float',
        'driver_fee' => 'float', 'distance_km' => 'float', 'estimated_minutes' => 'float',
        'prep_time_minutes' => 'float', 'rating_merchant' => 'float', 'rating_driver' => 'float',
        'accepted_at' => 'datetime', 'ready_at' => 'datetime', 'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function client()   { return $this->belongsTo(User::class, 'user_id'); }
    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function driver()   { return $this->belongsTo(Driver::class); }
    public function items()    { return $this->hasMany(OrderItem::class); }
    public function statusHistory() { return $this->hasMany(OrderStatusHistory::class)->oldest(); }
    public function delivery() { return $this->hasOne(Delivery::class)->latestOfMany(); }
    public function payment()  { return $this->hasOne(Payment::class); }
    public function reviews()  { return $this->hasMany(Review::class); }

    public function isTerminal(): bool
    {
        return in_array($this->status, config('marsaly.orders.terminal_statuses'), true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, config('marsaly.orders.active_statuses'), true);
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'MRS-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (static::query()->where('reference', $ref)->exists());
        return $ref;
    }
}
