<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float', 'commission_deducted' => 'float', 'period_start' => 'date', 'period_end' => 'date', 'paid_at' => 'datetime'];

    public function merchant()   { return $this->belongsTo(Merchant::class); }
    public function processor()  { return $this->belongsTo(User::class, 'processed_by'); }
}
