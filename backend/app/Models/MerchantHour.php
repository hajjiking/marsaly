<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantHour extends Model
{
    protected $fillable = ['merchant_id', 'day_of_week', 'opens_at', 'closes_at', 'is_closed'];
    protected $casts = ['is_closed' => 'boolean'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
}
