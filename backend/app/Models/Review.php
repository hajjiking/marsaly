<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['rating' => 'float', 'replied_at' => 'datetime'];

    public function order()    { return $this->belongsTo(Order::class); }
    public function user()     { return $this->belongsTo(User::class); }
    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function driver()   { return $this->belongsTo(Driver::class); }
}
