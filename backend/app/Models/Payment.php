<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float', 'amount_given' => 'float', 'change_due' => 'float'];

    public function order() { return $this->belongsTo(Order::class); }
}
