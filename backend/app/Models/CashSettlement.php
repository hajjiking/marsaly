<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashSettlement extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float', 'completed_at' => 'datetime'];

    public function driver()  { return $this->belongsTo(Driver::class); }
    public function receiver(){ return $this->belongsTo(User::class, 'received_by'); }
}
