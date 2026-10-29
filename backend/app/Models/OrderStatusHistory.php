<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $guarded = ['id'];

    public function order() { return $this->belongsTo(Order::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
