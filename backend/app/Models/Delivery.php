<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'offered_at' => 'datetime', 'offer_expires_at' => 'datetime', 'accepted_at' => 'datetime',
        'arrived_merchant_at' => 'datetime', 'picked_up_at' => 'datetime', 'delivered_at' => 'datetime',
        'fee' => 'float', 'tip' => 'float',
    ];

    public function order()  { return $this->belongsTo(Order::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
}
