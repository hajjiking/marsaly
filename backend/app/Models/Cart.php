<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'merchant_id', 'items', 'promo_code'];
    protected $casts = ['items' => 'array'];

    public function user()     { return $this->belongsTo(User::class); }
    public function merchant() { return $this->belongsTo(Merchant::class); }
}
